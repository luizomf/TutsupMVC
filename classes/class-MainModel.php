<?php
/**
 * MainModel - Modelo geral
 *
 * 
 *
 * @package TutsupMVC
 * @since 0.1
 */
class MainModel
{
	/**
	 * $form_data
	 *
	 * Os dados de formulários de envio.
	 *
	 * @access public
	 */	
	public $form_data;

	/**
	 * $form_msg
	 *
	 * As mensagens de feedback para formulários.
	 *
	 * @access public
	 */	
	public $form_msg;

	/**
	 * $form_confirma
	 *
	 * Mensagem de confirmação para apagar dados de formulários
	 *
	 * @access public
	 */
	public $form_confirma;

	/**
	 * $db
	 *
	 * O objeto da nossa conexão PDO
	 *
	 * @access public
	 */
	public $db;

	/**
	 * $controller
	 *
	 * O controller que gerou esse modelo
	 *
	 * @access public
	 */
	public $controller;

	/**
	 * $parametros
	 *
	 * Parâmetros da URL
	 *
	 * @access public
	 */
	public $parametros;

	/**
	 * $userdata
	 *
	 * Dados do usuário
	 *
	 * @access public
	 */
	public $userdata;
	
	/**
	 * Inverte datas 
	 *
	 * Obtém a data e inverte seu valor.
	 * De: d-m-Y H:i:s para Y-m-d H:i:s ou vice-versa.
	 *
	 * @since 0.1
	 * @access public
	 * @param string $data A data
	 */
	public function inverte_data( $data = null ) {
	
		// Configura uma variável para receber a nova data
		$nova_data = null;
		
		// Se a data for enviada
		if ( $data ) {
		
			// Explode a data por -, /, : ou espaço
			$data = preg_split('/\-|\/|\s|:/', $data);
			
			// Remove os espaços do começo e do fim dos valores
			$data = array_map( 'trim', $data );
			
			// Cria a data invertida
			$nova_data .= chk_array( $data, 2 ) . '-';
			$nova_data .= chk_array( $data, 1 ) . '-';
			$nova_data .= chk_array( $data, 0 );
			
			// Configura a hora
			if ( chk_array( $data, 3 ) ) {
				$nova_data .= ' ' . chk_array( $data, 3 );
			}
			
			// Configura os minutos
			if ( chk_array( $data, 4 ) ) {
				$nova_data .= ':' . chk_array( $data, 4 );
			}
			
			// Configura os segundos
			if ( chk_array( $data, 5 ) ) {
				$nova_data .= ':' . chk_array( $data, 5 );
			}
		}
		
		// Retorna a nova data
		return $nova_data;
	
	} // inverte_data

} // MainModel