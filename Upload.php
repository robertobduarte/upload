<?php 

//require __DIR__ . '/Iupload.php';


class Upload extends Iupload {
	
	function __construct( $dados ){

		$this->mime = array('image/png', 'image/jpg', 'image/jpeg');
		$this->caminho_relativo = $dados['caminho_relativo'];

		parent::__construct( $dados );
	}

}


?>
