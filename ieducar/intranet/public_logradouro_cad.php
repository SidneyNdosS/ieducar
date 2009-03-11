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
require_once( "include/public/geral.inc.php" );
require_once( "include/urbano/clsUrbanoTipoLogradouro.inc.php" );

class clsIndexBase extends clsBase
{
	function Formular()
	{
		$this->SetTitulo( "{$this->_instituicao} Logradouro" );
		$this->processoAp = "757";
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

	var $idlog;
	var $idtlog;
	var $nome;
	var $idmun;
	var $geom;
	var $ident_oficial;
	var $idpes_rev;
	var $data_rev;
	var $origem_gravacao;
	var $idpes_cad;
	var $data_cad;
	var $operacao;
	var $idsis_rev;
	var $idsis_cad;
	
	var $idpais;
	var $sigla_uf;

	function Inicializar()
	{
		$retorno = "Novo";
		@session_start();
		$this->pessoa_logada = $_SESSION['id_pessoa'];
		@session_write_close();

		$this->idlog=$_GET["idlog"];


		if( is_numeric( $this->idlog ) )
		{
			$obj_logradouro = new clsPublicLogradouro();
			$lst_logradouro = $obj_logradouro->lista( null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, $this->idlog );
			if( $lst_logradouro )
			{
				$registro = $lst_logradouro[0];
			}
			
			if( $registro )
			{
				foreach( $registro AS $campo => $val )	// passa todos os valores obtidos no registro para atributos do objeto
					$this->$campo = $val;


//				$this->fexcluir = true;

				$retorno = "Editar";
			}
		}
		$this->url_cancelar = ($retorno == "Editar") ? "public_logradouro_det.php?idlog={$registro["idlog"]}" : "public_logradouro_lst.php";
		$this->nome_url_cancelar = "Cancelar";
		return $retorno;
	}

	function Gerar()
	{
		// primary keys
		$this->campoOculto( "idlog", $this->idlog );

		// foreign keys
		$opcoes = array( "" => "Selecione" );
		if( class_exists( "clsPais" ) )
		{
			$objTemp = new clsPais();
			$lista = $objTemp->lista( false, false, false, false, false, "nome ASC" );
			if ( is_array( $lista ) && count( $lista ) ) 
			{
				foreach ( $lista as $registro ) 
				{
					$opcoes["{$registro['idpais']}"] = "{$registro['nome']}";
				}
			}
		}
		else
		{
			echo "<!--\nErro\nClasse clsPais nao encontrada\n-->";
			$opcoes = array( "" => "Erro na geracao" );
		}
		$this->campoLista( "idpais", "Pais", $opcoes, $this->idpais );

		$opcoes = array( "" => "Selecione" );
		if( class_exists( "clsUf" ) )
		{
			if( $this->idpais ) 
			{
				$objTemp = new clsUf();
				$lista = $objTemp->lista( false, false, $this->idpais, false, false, "nome ASC" );
				if ( is_array( $lista ) && count( $lista ) ) 
				{
					foreach ( $lista as $registro ) 
					{
						$opcoes["{$registro['sigla_uf']}"] = "{$registro['nome']}";
					}
				}
			}
		}
		else
		{
			echo "<!--\nErro\nClasse clsUf nao encontrada\n-->";
			$opcoes = array( "" => "Erro na geracao" );
		}
		$this->campoLista( "sigla_uf", "Estado", $opcoes, $this->sigla_uf );

		$opcoes = array( "" => "Selecione" );
		if( class_exists( "clsMunicipio" ) )
		{
			if( $this->sigla_uf ) 
			{
				$objTemp = new clsMunicipio();
				$lista = $objTemp->lista( false, $this->sigla_uf, false, false, false, false, false, false, false, false, false, "nome ASC" );
				if ( is_array( $lista ) && count( $lista ) ) 
				{
					foreach ( $lista as $registro ) 
					{
						$opcoes["{$registro['idmun']}"] = "{$registro['nome']}";
					}
				}
			}
		}
		else
		{
			echo "<!--\nErro\nClasse clsMunicipio nao encontrada\n-->";
			$opcoes = array( "" => "Erro na geracao" );
		}
		$this->campoLista( "idmun", "Munic&iacute;pio", $opcoes, $this->idmun );
		
		
		$opcoes = array( "" => "Selecione" );
		if( class_exists( "clsUrbanoTipoLogradouro" ) )
		{
			$objTemp = new clsUrbanoTipoLogradouro();
			$objTemp->setOrderby( "descricao ASC" );
			$lista = $objTemp->lista();
			if ( is_array( $lista ) && count( $lista ) ) 
			{
				foreach ( $lista as $registro ) 
				{
					$opcoes["{$registro['idtlog']}"] = "{$registro['descricao']}";
				}
			}
		}
		else
		{
			echo "<!--\nErro\nClasse clsUrbanoTipoLogradouro nao encontrada\n-->";
			$opcoes = array( "" => "Erro na geracao" );
		}
		$this->campoLista( "idtlog", "Tipo de Logradouro", $opcoes, $this->idtlog );
		
		$this->campoTexto( "nome", "Nome", $this->nome, 30, 150, true );
	}

	function Novo()
	{
		@session_start();
		 $this->pessoa_logada = $_SESSION['id_pessoa'];
		@session_write_close();

		$obj = new clsPublicLogradouro( null, $this->idtlog, $this->nome, $this->idmun, null, 'S', null, null, 'U', $this->pessoa_logada, null, 'I', null, 9 );
		$cadastrou = $obj->cadastra();
		if( $cadastrou )
		{
			$this->mensagem .= "Cadastro efetuado com sucesso.<br>";
			header( "Location: public_logradouro_lst.php" );
			die();
			return true;
		}

		$this->mensagem = "Cadastro n&atilde;o realizado.<br>";
		echo "<!--\nErro ao cadastrar clsPublicLogradouro\nvalores obrigatorios\nis_string( $this->idtlog ) && is_string( $this->nome ) && is_numeric( $this->idmun ) && is_string( $this->origem_gravacao ) && is_string( $this->operacao ) && is_numeric( $this->idsis_cad )\n-->";
		return false;
	}

	function Editar()
	{
		@session_start();
		 $this->pessoa_logada = $_SESSION['id_pessoa'];
		@session_write_close();

		$obj = new clsPublicLogradouro( $this->idlog, $this->idtlog, $this->nome, $this->idmun, null, 'S', $this->pessoa_logada, null, 'U', null, null, 'I', null, 9 );
		$editou = $obj->edita();
		if( $editou )
		{
			$this->mensagem .= "Edi&ccedil;&atilde;o efetuada com sucesso.<br>";
			header( "Location: public_logradouro_lst.php" );
			die();
			return true;
		}

		$this->mensagem = "Edi&ccedil;&atilde;o n&atilde;o realizada.<br>";
		echo "<!--\nErro ao editar clsPublicLogradouro\nvalores obrigatorios\nif( is_numeric( $this->idlog ) )\n-->";
		return false;
	}

	function Excluir()
	{
		@session_start();
		 $this->pessoa_logada = $_SESSION['id_pessoa'];
		@session_write_close();

		$obj = new clsPublicLogradouro( $this->idlog, $this->idtlog, $this->nome, $this->idmun, null, 'S', $this->pessoa_logada );
		$excluiu = $obj->excluir();
		if( $excluiu )
		{
			$this->mensagem .= "Exclus&atilde;o efetuada com sucesso.<br>";
			header( "Location: public_logradouro_lst.php" );
			die();
			return true;
		}

		$this->mensagem = "Exclus&atilde;o n&atilde;o realizada.<br>";
		echo "<!--\nErro ao excluir clsPublicLogradouro\nvalores obrigatorios\nif( is_numeric( $this->idlog ) )\n-->";
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
<script>

document.getElementById('idpais').onchange = function()
{
	var campoPais = document.getElementById('idpais').value;

	var campoUf= document.getElementById('sigla_uf');
	campoUf.length = 1;
	campoUf.disabled = true;
	campoUf.options[0].text = 'Carregando estado...';

	var xml_uf = new ajax( getUf );
	xml_uf.envia( "public_uf_xml.php?pais="+campoPais );
}

function getUf( xml_uf )
{
	var campoUf = document.getElementById('sigla_uf');
	var DOM_array = xml_uf.getElementsByTagName( "uf" );

	if(DOM_array.length)
	{
		campoUf.length = 1;
		campoUf.options[0].text = 'Selecione um estado';
		campoUf.disabled = false;

		for( var i = 0; i < DOM_array.length; i++ )
		{
			campoUf.options[campoUf.options.length] = new Option( DOM_array[i].firstChild.data, DOM_array[i].getAttribute("sigla_uf"),false,false);
		}
	}
	else
	{
		campoUf.options[0].text = 'O pais n�o possui nenhum estado';
	}
}

document.getElementById('sigla_uf').onchange = function()
{
	var campoUf = document.getElementById('sigla_uf').value;

	var campoMunicipio= document.getElementById('idmun');
	campoMunicipio.length = 1;
	campoMunicipio.disabled = true;
	campoMunicipio.options[0].text = 'Carregando munic�pio...';

	var xml_municipio = new ajax( getMunicipio );
	xml_municipio.envia( "public_municipio_xml.php?uf="+campoUf );
}

function getMunicipio( xml_municipio )
{
	var campoMunicipio = document.getElementById('idmun');
	var DOM_array = xml_municipio.getElementsByTagName( "municipio" );

	if(DOM_array.length)
	{
		campoMunicipio.length = 1;
		campoMunicipio.options[0].text = 'Selecione um munic�pio';
		campoMunicipio.disabled = false;

		for( var i = 0; i < DOM_array.length; i++ )
		{
			campoMunicipio.options[campoMunicipio.options.length] = new Option( DOM_array[i].firstChild.data, DOM_array[i].getAttribute("idmun"),false,false);
		}
	}
	else
	{
		campoMunicipio.options[0].text = 'O estado n�o possui nenhum munic�pio';
	}
}

</script>