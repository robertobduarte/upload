<?php

require_once __DIR__ . '/../config.php';

abstract class Iupload {
		
    protected $mime = array();
	protected $tipo_arquivo;
	protected $tmp_name;
	protected $code;
	protected $tamanho_maximo_arquivo =  500000; // 50mb
    protected $tamanho_arquivo;
    protected $codigo_erro = array();
    
    protected $nome_original_arquivo; //nome original do arquivo
    protected $nome_arquivo; //nome que o arquivo original sera salvo
    protected $prefixo_nome; //prefixo do nome que o arquivo

    protected $caminho_relativo = ''; //caminho relativo do diretório onde é armazenado os arquivos
    protected $caminho_absoluto = ''; //caminho absoluto do diretório onde é armazenado os arquivos

	protected $diretorio; //caso seja necessário salvar o arquivo em um diretório específico dentro do caminho absoluto
    
    protected $caminho_relativo_final; //caminho_relativo + diretorio
    protected $caminho_absoluto_final; //caminho_absoluto + diretorio

	protected $retorno = array();
	
	/*
	parâmetros opcionais:
		caminho_relativo
		tamanho_maximo_arquivo,
		diretorio
		nome_arquivo,
		prefixo
	*/

	public function __construct( $dados ){
		
		$this->setPropriedade( $dados );
		$this->startFluxo();
	}
		

	protected function setPropriedade( $dados ){

		$this->tipo_arquivo = $_FILES['file']['type'];
        $this->tamanho_arquivo = round($_FILES['file']['size'] / 1024);
        $this->tmp_name = $_FILES['file']['tmp_name']; 
		$this->nome_original_arquivo = basename($_FILES['file']['name']);
		$this->code = $_FILES['file']['error'];

		//seta o tamanho máximo do arquivo. Caso não seja definido, seta para 50mb
		$this->tamanho_maximo_arquivo = ( isset( $dados['tamanho_maximo_arquivo'] ) &&  !empty( $dados['tamanho_maximo_arquivo'] ) )? $dados['tamanho_maximo_arquivo'] : $this->tamanho_maximo_arquivo;


		$this->caminho_relativo = ( isset( $dados['caminho_relativo'] ) && !empty( $dados['caminho_relativo'] ) ) ? $dados['caminho_relativo'] : $this->caminho_relativo;

		if( empty( $this->caminho_relativo ) ){

			$this->caminho_relativo = 'dominio';
		}

		$this->caminho_absoluto = $_SERVER['DOCUMENT_ROOT'] . '/' . APP . $this->caminho_relativo;
		

		$this->diretorio = ( isset( $dados['diretorio'] ) &&  !empty( $dados['diretorio'] ) )? $dados['diretorio'] : false;

		//concatenação do caminho RELATIVO com o diretório
		$this->caminho_relativo_final = ( !$this->diretorio )? $this->caminho_relativo . '/' : $this->caminho_relativo . '/' . $this->diretorio . '/';
		
		//concatenação do caminho ABSOLUTO com o diretório
		$this->caminho_absoluto_final = ( !$this->diretorio )? $this->caminho_absoluto . '/' : $this->caminho_absoluto . '/' . $this->diretorio . '/';
		
		//define um prefixo para o nome do arquivo, caso exista
		$this->prefixo_nome = ( isset( $dados['prefixo'] ) && !empty( $dados['prefixo']  ) )? $dados['prefixo'] : false;

		//seta o nome do arquivo para o veio como parâmetro. Caso não tenha definido, o nome do arquivo permanece com o mesmo nome original
		$this->nome_arquivo = ( isset( $dados['nome_arquivo'] ) &&  !empty( $dados['nome_arquivo'] ) )? $dados['nome_arquivo'] : $this->nome_original_arquivo;
		
		$this->nome_arquivo = ( $this->prefixo_nome )? $this->prefixo_nome.'_'.$this->nome_arquivo : $this->nome_arquivo; 

	}

	protected function startFluxo(){	

		if ( !$this->codeToMessage() ){

			return $this->retorno;
		}

		if ( !$this->checaOrigem() ){

			return $this->retorno;
		}
		
		if ( !$this->checaTamanhoArq() ){

			return $this->retorno;
		}

		if ( !$this->checaTipoArquivo() ){

			return $this->retorno;
		}

		if ( !$this->defineDiretorio() ){

			return $this->retorno;
		}

		$this->salvaArquivo();			
	}
	
	
	
	protected function defineDiretorio(){

		if( $this->diretorio ){

			if( !is_dir( $this->caminho_absoluto_final ) ){

	            if ( mkdir( $this->caminho_absoluto_final, 0755, true ) ){

					chmod( $this->caminho_absoluto_final, 0755 ); 

	            }else{

					$this->retorno['msg'] = 'Nao foi possivel criar o diretorio '.$this->caminho_absoluto_final;
					$this->retorno['cod'] = 0;	
					return false;				
	            }
        	}

		}

		return true;
	}


	protected function salvaArquivo(){  
           
    	if( !move_uploaded_file( $_FILES['file']['tmp_name'], $this->caminho_absoluto_final . $this->nome_arquivo ) ){ 

			$this->retorno['msg'] = 'Erro ao gravar o arquivo: ' . $this->caminho_absoluto_final . $this->nome_arquivo;
			$this->retorno['cod'] = 0;
			return false;

       	}

		$this->retorno['msg'] = 'Arquivo armazenado com sucesso em ' . $this->caminho_absoluto_final . $this->nome_arquivo;
		$this->retorno['cod'] = 1;

	}
	

	protected function checaOrigem(){ //confere se o arquivo foi enviado por post

        if ( ! is_uploaded_file( $this->tmp_name ) ) {

			$this->retorno['msg'] = 'Arquivo suspeito. Tente novamente, se o problema persistir entre em contato';
			$this->retorno['cod'] = 0;	
			return false;

         }

         return true;
    }	

	protected function checaTamanhoArq(){

        if( $this->tamanho_arquivo > $this->tamanho_maximo_arquivo ){

            $this->retorno['msg'] = 'Arquivo muito grande. Tamanho maximo: '.$this->tamanho_maximo_arquivo;
			$this->retorno['cod'] = 0;
			return false;
        }

        return true;
    }
	

	protected function checaTipoArquivo(){ //verifica se a extensao do arquivo e valida.

		if( !empty( $this->mime ) ){

			if( !in_array( $this->tipo_arquivo, $this->mime ) ){ 

				$this->retorno['msg'] = 'Tipo de arquivo nao suportado: '.$this->tipo_arquivo;
				$this->retorno['cod'] = 0;
	            return false;
        	}

		}

		return true;
    }	
	

	protected function codeToMessage(){ 

        switch ($this->code) { 

            case UPLOAD_ERR_OK: 
                $this->retorno["cod"] = 1;
                $this->retorno["msg"] = 'Upload OK.';   
				return true;
                break; 

            case UPLOAD_ERR_INI_SIZE: 
                $this->retorno["cod"] = 0;
                $this->retorno["msg"] = 'ERRO! O arquivo enviado e muito grande. Tamanho maximo permitido: ' . $this->tamanho_maximo_arquivo;
				return false;
                break; 

            case UPLOAD_ERR_FORM_SIZE:
                $this->retorno["cod"] = 0;
                $this->retorno["msg"] = 'ERRO! O arquivo enviado e muito grande. Tamanho maximo permitido: ' . $this->tamanho_maximo_arquivo;
				return false;				
                break; 

            case UPLOAD_ERR_PARTIAL:                
                $this->retorno["cod"] = 0;
                $this->retorno["msg"] = 'ERRO! O arquivo foi apenas parcialmente carregado. Tente novamente.';  
                return false;
			    break; 

            case UPLOAD_ERR_NO_FILE:
                $this->retorno["cod"] = 0;
                $this->retorno["msg"] = 'ERRO! Nenhum arquivo foi enviado. Tente novamente.';
				return false;
                break; 

            case UPLOAD_ERR_NO_TMP_DIR:
                $this->retorno["cod"] = 0;
                $this->retorno["msg"] = 'ERRO! Pasta inexistente.';
				return false;
                break; 

            case UPLOAD_ERR_CANT_WRITE:
                $this->retorno["cod"] = 0;
                $this->retorno["msg"] = 'ERRO! Falha ao gravar arquivo em disco. Tente novamente.';
				return false;
                break; 

            case UPLOAD_ERR_EXTENSION:
                $this->retorno["cod"] = 0;
                $this->retorno["msg"] = 'ERRO! Extensão de arquivo invalido.'; 
				return false;
                break; 

            default:
                $this->retorno["cod"] = 0;
                $this->retorno["msg"] = 'ERRO! Tente novamente.'; 
				return false;
                break; 
        }         
    }
	

	
	public function getCaminho(){

		return $this->caminho_absoluto_final;
	}
	

	public function getDiretorio(){

		return $this->diretorio;
	}

	
	public function getNomeOriginal(){

		return $this->nome_original_arquivo;
	}
	

	public function getNomeSalvo(){

		return $this->nome_arquivo;
	}


	
	public function getMimeFile(){
		
		return $this->tipo_arquivo;
	}

	
	public function getInformacoesArquivo(){

		$informacoes = array(
							'caminho_relativo' => $this->caminho_relativo,
							'caminho_absoluto' => $this->caminho_absoluto,
							'caminho_relativo_final' => $this->caminho_relativo_final,
							'caminho_absoluto_final' => $this->caminho_absoluto_final,							
							'diretorio' => $this->diretorio,
							'prefixo_nome' => $this->prefixo_nome,							
							'nome_original_arquivo' => $this->nome_original_arquivo,
							'nome_arquivo' => $this->nome_arquivo,
							'extensao' => $this->tipo_arquivo,
							'tamanho' => $this->tamanho_arquivo,
							'nome_temporario' => $this->tmp_name,
							'code' => $this->code,
							'retorno' => $this->retorno
							);

		return $informacoes;
	}


} //fim da classe
?>