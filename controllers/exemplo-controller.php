<?php
class ExemploController extends MainController
{
	// URL: dominio.com/exemplo/
	public function index() {
	
		// Carrega o modelo
		$modelo = $this->load_model('exemplo/exemplo-model');
		
		// Carrega o view
		require_once ABSPATH . '/views/exemplo/exemplo-view.php';
	}
	
	// URL: dominio.com/exemplo/outra-acao
	public function OutraAcao() {
		// Inclua seus models e views aqui
	}
}