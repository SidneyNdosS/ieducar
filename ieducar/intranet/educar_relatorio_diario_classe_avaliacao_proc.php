<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	*																	     *
	*	@author Prefeitura Municipal de Itaja�								 *
	*	@updated 29/03/2007													 *
	*   Pacote: i-PLB Software P�blico Livre e Brasileiro					 *
	*																		 *
	*	Copyright (C) 2006	PMI - Prefeitura Municipal de Itaja�			 *
	*						ctima@itajai.sc.gov.br					    	 *
	*																		 *
	*	Este  programa  �  software livre, voc� pode redistribu�-lo e/ou	 *
	*	modific�-lo sob os termos da Licen�a P�blica Geral GNU, conforme	 *
	*	publicada pela Free  Software  Foundation,  tanto  a vers�o 2 da	 *
	*	Licen�a   como  (a  seu  crit�rio)  qualquer  vers�o  mais  nova.	 *
	*																		 *
	*	Este programa  � distribu�do na expectativa de ser �til, mas SEM	 *
	*	QUALQUER GARANTIA. Sem mesmo a garantia impl�cita de COMERCIALI-	 *
	*	ZA��O  ou  de ADEQUA��O A QUALQUER PROP�SITO EM PARTICULAR. Con-	 *
	*	sulte  a  Licen�a  P�blica  Geral  GNU para obter mais detalhes.	 *
	*																		 *
	*	Voc�  deve  ter  recebido uma c�pia da Licen�a P�blica Geral GNU	 *
	*	junto  com  este  programa. Se n�o, escreva para a Free Software	 *
	*	Foundation,  Inc.,  59  Temple  Place,  Suite  330,  Boston,  MA	 *
	*	02111-1307, USA.													 *
	*																		 *
	* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
require_once ("include/clsBase.inc.php");
require_once ("include/clsCadastro.inc.php");
require_once ("include/clsBanco.inc.php");
require_once( "include/pmieducar/geral.inc.php" );
require_once ("include/clsPDF.inc.php");

class clsIndexBase extends clsBase
{
	function Formular()
	{
		$this->SetTitulo( "{$this->_instituicao} i-Educar - Di�rio de Classe - Avalia&ccedil;&otilde;es" );
		$this->processoAp = "670";
		$this->renderMenu = false;
		$this->renderMenuSuspenso = false;
	}
}

class indice extends clsCadastro
{


	/**
	 * Referencia pega da session para o idpes do usuario atual
	 *
	 * @var int
	 */
	var $pessoa_logada;


	var $ref_cod_instituicao;
	var $ref_cod_escola;
	var $ref_cod_serie;
	var $ref_cod_turma;

	var $ano;
	var $mes;

	var $nm_escola;
	var $nm_instituicao;
	var $ref_cod_curso;
	var $sequencial;
	var $pdf;
	var $pagina_atual = 1;
	var $total_paginas = 1;
	var $nm_professor;
	var $nm_turma;
	var $nm_serie;
	var $nm_disciplina;
	var $avaliacao_globalizada;

	var $page_y = 139;

	var $get_file;

	var $cursos = array();

	var $get_link;

	var $total;

	//var $array_disciplinas = array();

	var $ref_cod_modulo;

	var $numero_registros;
	var $em_branco;

	var $meses_do_ano = array(
							 "1" => "JANEIRO"
							,"2" => "FEVEREIRO"
							,"3" => "MAR�O"
							,"4" => "ABRIL"
							,"5" => "MAIO"
							,"6" => "JUNHO"
							,"7" => "JULHO"
							,"8" => "AGOSTO"
							,"9" => "SETEMBRO"
							,"10" => "OUTUBRO"
							,"11" => "NOVEMBRO"
							,"12" => "DEZEMBRO"
						);


	function renderHTML()
	{

		if($_POST){
			foreach ($_POST as $key => $value) {
				$this->$key = $value;

			}
		}

		if($this->ref_ref_cod_serie)
			$this->ref_cod_serie = $this->ref_ref_cod_serie;

		$fonte = 'arial';
		$corTexto = '#000000';

		if(empty($this->ref_cod_turma))
		{
	     	echo '<script>
	     			alert("Erro ao gerar relat�rio!\nNenhuma turma selecionada!");
	     			window.parent.fechaExpansivel(\'div_dinamico_\'+(window.parent.DOM_divs.length-1));
	     		</script>';
	     	return true;
		}


		if($this->ref_cod_escola){

			$obj_escola = new clsPmieducarEscola($this->ref_cod_escola);
			$det_escola = $obj_escola->detalhe();
			$this->nm_escola = $det_escola['nome'];

			$obj_instituicao = new clsPmieducarInstituicao($det_escola['ref_cod_instituicao']);
			$det_instituicao = $obj_instituicao->detalhe();
			$this->nm_instituicao = $det_instituicao['nm_instituicao'];

		}

	     $obj_calendario = new clsPmieducarEscolaAnoLetivo();
	     $lista_calendario = $obj_calendario->lista($this->ref_cod_escola,$this->ano,null,null,null,null,null,null,null,1,null);

	     $obj_turma = new clsPmieducarTurma($this->ref_cod_turma);
	     $det_turma = $obj_turma->detalhe();
	     $this->nm_turma = $det_turma['nm_turma'];

	     $obj_serie = new clsPmieducarSerie($this->ref_cod_serie);
	     $det_serie = $obj_serie->detalhe();
	     $this->nm_serie = $det_serie['nm_serie'];

		 $obj_pessoa = new clsPessoa_($det_turma["ref_cod_regente"]);
		 $det = $obj_pessoa->detalhe();
		 $this->nm_professor = $det["nome"];

	     if(!$lista_calendario)
	     {
	     	echo '<script>
	     			alert("Escola n�o possui calend�rio definido para este ano");
	     			window.parent.fechaExpansivel(\'div_dinamico_\'+(window.parent.DOM_divs.length-1));
	     		</script>';
	     	return true;
	     }

		$prox_mes = $this->mes + 1;
		$this->pdf = new clsPDF("Di�rio de Classe - {$this->ano}", "Di�rio de Classe - {$this->meses_do_ano[$this->mes]} e {$this->meses_do_ano[$prox_mes]} de {$this->ano}", "A4", "", false, false);

		//$this->pdf->largura  = 842.0;
  		//$this->pdf->altura = 595.0;

		$altura_linha = 15;
		$inicio_escrita_y = 175;
		$altura_pagina = 760;


		$obj = new clsPmieducarSerie();
		$obj->setOrderby('cod_serie,etapa_curso');
		$lista_serie_curso = $obj->lista(null,null,null,$this->ref_cod_curso,null,null,null,null,null,null,null,null,1,$this->ref_cod_instituicao);

		$obj_curso = new clsPmieducarCurso($this->ref_cod_curso);
		$det_curso = $obj_curso->detalhe();

		$obj_curso = new clsPmieducarCurso($this->ref_cod_curso);
		$det_curso = $obj_curso->detalhe();

		//if($det_curso['falta_ch_globalizada'])
		if($det_curso['falta_ch_globalizada'] && $det_curso['avaliacao_globalizada'] == 't')
		{
			if(!$this->em_branco)
			{
				$obj_matricula_turma = new clsPmieducarMatriculaTurma();
				$obj_matricula_turma->setOrderby("nome_aluno");
			    $lista_matricula = $obj_matricula_turma->lista(null,$this->ref_cod_turma,null,null,null,null,null,null,1,$this->ref_cod_serie,$this->ref_cod_curso,$this->ref_cod_escola,$this->ref_cod_instituicao,null,null,null,null,null,$this->ano,null,true,null,null,true);
			}
			if($lista_matricula || $this->em_branco)
			{
				$this->pdf->OpenPage();
				$this->addCabecalho();
				$num_aluno = 1;

				if($this->em_branco)
				{
					$lista_matricula = array();
					$this->numero_registros = $this->numero_registros? $this->numero_registros : 20;
					for ($i = 0 ; $i < $this->numero_registros; $i++)
					{
						$lista_matricula[] = '';
					}
				}

			    foreach ($lista_matricula as $matricula)
			    {

					if($this->page_y > $altura_pagina)
					{
						$this->desenhaLinhasVertical();
						$this->pdf->ClosePage();
						$this->pdf->OpenPage();
						$this->page_y = 139;
						$this->addCabecalho();


					}

			    	//$obj_matricula = new clsPmieducarMatricula($matricula['ref_cod_matricula']);
			    	//$det_matricula = $obj_matricula->detalhe();

			    	//$obj_aluno = new clsPmieducarAluno();

			    	//$det_aluno = array_shift($obj_aluno->lista($det_matricula['ref_cod_aluno']));

			    	$this->pdf->quadrado_relativo( 30, $this->page_y , 540, $altura_linha);
			    	$this->pdf->escreve_relativo(sprintf("%02d",$num_aluno) , 38 ,$this->page_y + 4,30, 15, $fonte, 7, $corTexto, 'left' );
			    	$this->pdf->escreve_relativo($matricula['nome_aluno'] , 55 ,$this->page_y + 4,160, 15, $fonte, 7, $corTexto, 'left' );
					$num_aluno++;
			    	$this->page_y +=$altura_linha;



			    }

		    	$this->desenhaLinhasVertical();

				$this->rodape();
				$this->pdf->ClosePage();
			}
			else
			{

		     	echo '<script>
		     			alert("Turma n�o possui matriculas");
		     			window.parent.fechaExpansivel(\'div_dinamico_\'+(window.parent.DOM_divs.length-1));
		     		</script>';

		     		return;
			}


			//header( "location: " . $this->pdf->GetLink() );
			$this->pdf->CloseFile();
			$this->get_link = $this->pdf->GetLink();

		}
		else
		{
			/**
			 * CARGA HORARIA NAO GLOBALIZADA
			 * GERAR UMA PAGINA PARA CADA DISICIPLINA
			 */
			
			//$obj_turma_disc = new clsPmieducarTurmaDisciplina();
			$obj_turma_disc = new clsPmieducarDisciplinaSerie();
			$obj_turma_disc->setCamposLista("ref_cod_disciplina");
			$lst_turma_disc = $obj_turma_disc->lista(null,$this->ref_cod_serie,1);
			if($lst_turma_disc)
			{
				foreach ($lst_turma_disc as $disciplina) {
					$obj_disc = new clsPmieducarDisciplina($disciplina);
					$det_disc = $obj_disc->detalhe();
					$this->nm_disciplina = $det_disc['nm_disciplina'];
					$this->page_y = 139;

					/**
					 * numero de semanas dos meses
					 */
					$obj_quadro = new clsPmieducarQuadroHorario();
					$obj_quadro->setCamposLista("cod_quadro_horario");
					$quadro_horario = $obj_quadro->lista(null,null,null,$this->ref_cod_turma, null, null, null, null,1);
					if(!$quadro_horario &&  $det_curso['avaliacao_globalizada'] == 't')
					{
						echo '<script>alert(\'Turma n�o possui quadro de hor�rios\'); window.location = "educar_relatorio_diario_classe.php";</script>';
						break;
					}

					$obj_quadro_horarios = new clsPmieducarQuadroHorarioHorarios();
					$obj_quadro_horarios->setCamposLista("dia_semana");
					$obj_quadro_horarios->setOrderby("1 asc");

					$lista_quadro_horarios = $obj_quadro_horarios->lista($quadro_horario,$this->ref_cod_serie,$this->ref_cod_escola,$disciplina,null,null,null,null,null,null,null,null,null,null,null,null,null,null,1);

					if(!$this->em_branco)
					{
					    $obj_matricula_turma = new clsPmieducarMatriculaTurma();
					    $obj_matricula_turma->setOrderby("nome_ascii");
					    $lista_matricula = $obj_matricula_turma->lista(null,$this->ref_cod_turma,null,null,null,null,null,null,1,$this->ref_cod_serie,$this->ref_cod_curso,$this->ref_cod_escola,$this->ref_cod_instituicao,null,null,array( 1, 2, 3 ),null,null,$this->ano,null,true,null,null,true);
					}
					$num_aluno = 1;
					if($lista_matricula || $this->em_branco)
					{
						$this->pdf->OpenPage();
						$this->addCabecalho();

						if($this->em_branco)
						{
							$lista_matricula = array();
							$this->numero_registros = $this->numero_registros? $this->numero_registros : 20;
							for ($i = 0 ; $i < $this->numero_registros; $i++)
							{
								$lista_matricula[] = '';
							}
						}
					    foreach ($lista_matricula as $matricula)
					    {

							if($this->page_y > $altura_pagina)
							{
								$this->desenhaLinhasVertical();
								$this->pdf->ClosePage();
								$this->pdf->OpenPage();
								$this->page_y = 139;
								$this->addCabecalho();


							}

					    	//$obj_matricula = new clsPmieducarMatricula($matricula['ref_cod_matricula']);
					    	//$det_matricula = $obj_matricula->detalhe();

					    	//$obj_aluno = new clsPmieducarAluno();
					    	//$det_aluno = array_shift($obj_aluno->lista($det_matricula['ref_cod_aluno']));
							
					    	$this->pdf->quadrado_relativo( 30, $this->page_y , 540, $altura_linha);
					    	$this->pdf->escreve_relativo($num_aluno , 38 ,$this->page_y + 4,30, 15, $fonte, 7, $corTexto, 'left' );
					    	$this->pdf->escreve_relativo($matricula['nome_aluno'] , 55 ,$this->page_y + 4,160, 15, $fonte, 7, $corTexto, 'left' );

							$num_aluno++;
					    	$this->page_y +=$altura_linha;



					    }
						$this->desenhaLinhasVertical();
						$this->rodape();
						$this->pdf->ClosePage();
					}
					else
					{

				     	echo '<script>
				     			alert("Turma n�o possui matriculas");
				     			window.parent.fechaExpansivel(\'div_dinamico_\'+(window.parent.DOM_divs.length-1));
				     		</script>';

				     		return;
					}


				}
				/**
				 * gera diario de clase de avaliacoes
				 */
				$this->pdf->CloseFile();
				$this->get_link = $this->pdf->GetLink();
			}
			else
			{

				echo '<script>
				     			alert("A S�rie n�o possui disciplinas");
				     			window.parent.fechaExpansivel(\'div_dinamico_\'+(window.parent.DOM_divs.length-1));
				     		</script>';

				return;
			}


			//header( "location: " . $this->pdf->GetLink() );
			
		}

		echo "<script>window.onload=function(){parent.EscondeDiv('LoadImprimir');window.location='download.php?filename=".$this->get_link."'}</script>";

		echo "<html><center>Se o download n�o iniciar automaticamente <br /><a target='blank' href='" . $this->get_link  . "' style='font-size: 16px; color: #000000; text-decoration: underline;'>clique aqui!</a><br><br>
			<span style='font-size: 10px;'>Para visualizar os arquivos PDF, � necess�rio instalar o Adobe Acrobat Reader.<br>

			Clique na Imagem para Baixar o instalador<br><br>
			<a href=\"http://www.adobe.com.br/products/acrobat/readstep2.html\" target=\"new\"><br><img src=\"imagens/acrobat.gif\" width=\"88\" height=\"31\" border=\"0\"></a>
			</span>
			</center>";
	}

	function addCabecalho()
	{
		// variavel que controla a altura atual das caixas
		$altura = 30;
		$fonte = 'arial';
		$corTexto = '#000000';


		// cabecalho
		$this->pdf->quadrado_relativo( 30, $altura, 540, 85 );
		$this->pdf->InsertJpng( "gif", "imagens/brasao.gif", 50, 95, 0.30 );

		//titulo principal
		$this->pdf->escreve_relativo( "PREFEITURA COBRA TECNOLOGIA", 30, 30, 782, 80, $fonte, 18, $corTexto, 'center' );

		//dados escola
		$this->pdf->escreve_relativo( "Institui��o:$this->nm_instituicao", 120, 52, 300, 80, $fonte, 7, $corTexto, 'left' );
		$this->pdf->escreve_relativo( "Escola:{$this->nm_escola}",132, 64, 300, 80, $fonte, 7, $corTexto, 'left' );
		$dif = 0;
		if($this->nm_professor)
			$this->pdf->escreve_relativo( "Prof.Regente:{$this->nm_professor}",111, 76, 300, 80, $fonte, 7, $corTexto, 'left' );
		else
			$dif = 12;

		$this->pdf->escreve_relativo( "S�rie:{$this->nm_serie}",138, 88  - $dif, 300, 80, $fonte, 7, $corTexto, 'left' );
		$this->pdf->escreve_relativo( "Turma:{$this->nm_turma}",134, 100 - $dif, 300, 80, $fonte, 7, $corTexto, 'left' );

		//titulo
		//if($this->nm_disciplina)
		//	$this->nm_disciplina = "$this->nm_disciplina";
		$this->pdf->escreve_relativo( "Di�rio de Classe - {$this->nm_disciplina}", 30, 75, 782, 80, $fonte, 12, $corTexto, 'center' );

		$obj_modulo = new clsPmieducarModulo($this->ref_cod_modulo);
		$det_modulo = $obj_modulo->detalhe();
		//Data
		//$mes2 = $this->mes + 1;
		//$this->pdf->escreve_relativo( (($this->meses_do_ano[$this->mes]))." e ".(($this->meses_do_ano[$mes2]))." de {$this->ano}", 45, 100, 782, 80, $fonte, 10, $corTexto, 'center' );
		//$this->pdf->escreve_relativo( (($this->meses_do_ano[$this->mes]))." e ".(($this->meses_do_ano[$mes2]))." de {$this->ano}", 45, 100, 782, 80, $fonte, 10, $corTexto, 'center' );

		$this->pdf->linha_relativa(201,125,0,14);
		$this->pdf->linha_relativa(201,125,369,0);
		$this->pdf->escreve_relativo( "Avalia��es", 195, 128, 350, 80, $fonte, 7, $corTexto, 'center' );
		$this->pdf->linha_relativa(543,125,0,14);
		$this->pdf->linha_relativa(30,139,0,20);
		//$this->pdf->linha_relativa(50,139,0,20);
		$this->pdf->linha_relativa(30,139,513,0);
		$this->pdf->escreve_relativo( "M�dia", 538, 137, 35, 80, $fonte, 7, $corTexto, 'center' );

		$this->pdf->escreve_relativo( "N�",36, 145, 100, 80, $fonte, 7, $corTexto, 'left' );
		$this->pdf->escreve_relativo( "Nome",110, 145, 100, 80, $fonte, 7, $corTexto, 'left' );

    	$this->page_y +=19;
	    $this->pdf->escreve_relativo( "Dias de aula: {$this->total}", 715, 100, 535, 80, $fonte, 10, $corTexto, 'left' );
	}

	function desenhaLinhasVertical()
	{
		$corTexto = '#000000';
			/**
			 *
			 */

			$this->total = 10;
				$largura_anos = 380;

				if($this->total >= 1)
				{

					$incremental = floor($largura_anos/ ($this->total )) ;

				}else {

					$incremental = 1;
				}

				$reta_ano_x = 200 ;


				$resto = $largura_anos - ($incremental * $this->total);

				for($linha = 0;$linha <$this->total;$linha++)
				{

					if(( $resto > 0) /*|| ($linha + 1 == $total && $resto >= 1) */|| $linha == 0)
					{
						$reta_ano_x++;
						$resto--;
					}

					$this->pdf->linha_relativa($reta_ano_x,139,0,$this->page_y - 139);


					$reta_ano_x += $incremental;

				}

				$this->pdf->linha_relativa(50,139,0,$this->page_y - 139);
				$this->pdf->linha_relativa(812,125,0,$this->page_y - 139);


			$this->pdf->linha_relativa(570,125,0,$this->page_y - 139);
			//$this->pdf->escreve_relativo( "Faltas",40, 128, 100, 80, $fonte, 7, $corTexto, 'left' );


			/**
			 *
			 */
	}

	function rodape()
	{
		$corTexto = '#000000';
		$fonte = 'arial';
		$dataAtual = date("d/m/Y");
		$this->pdf->escreve_relativo( "Data: $dataAtual", 36,795, 100, 50, $fonte, 7, $corTexto, 'left' );

		//$this->pdf->escreve_relativo( "Assinatura do Diretor(a)", 68,520, 100, 50, $fonte, 7, $corTexto, 'left' );
		$this->pdf->escreve_relativo( "Assinatura do Professor(a)", 677,520, 100, 50, $fonte, 7, $corTexto, 'left' );
		//$this->pdf->linha_relativa(52,517,130,0);
		$this->pdf->linha_relativa(660,517,130,0);
	}

	function Editar()
	{
		return false;
	}

	function Excluir()
	{
		return false;
	}

}

// cria uma extensao da classe base
$pagina = new clsIndexBase();
// cria o conteudo
$miolo = new indice();
// adiciona o conteudo na clsBase
$pagina->addForm( $miolo );
// gera o html
$pagina->MakeAll();


?>