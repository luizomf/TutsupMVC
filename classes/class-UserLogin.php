<?php
/**
 * UserLogin - Manipula os dados de usuários
 *
 * Manipula os dados de usuários, faz login e logout, verifica permissões e 
 * redireciona página para usuários logados.
 *
 * @package TutsupMVC
 * @since 0.1
 */
class UserLogin
{
	/**
	 * Usuário logado ou não
	 *
	 * Verdadeiro se ele estiver logado.
	 *
	 * @public
	 * @access public
	 * @var bol
	 */
	public $logged_in;
	
	/**
	 * Dados do usuário
	 *
	 * @public
	 * @access public
	 * @var array
	 */
	public $userdata;
	
	/**
	 * Mensagem de erro para o formulário de login
	 *
	 * @public
	 * @access public
	 * @var string
	 */
	public $login_error;
	
	/**
	 * Verifica o login
	 *
	 * Configura as propriedades $logged_in e $login_error. Também
	 * configura o array do usuário em $userdata
	 */
	public function check_userlogin () {
	
		// Verifica se existe uma sessão com a chave userdata
		// Tem que ser um array e não pode ser HTTP POST
		if ( isset( $_SESSION['userdata'] )
			 && ! empty( $_SESSION['userdata'] )
			 && is_array( $_SESSION['userdata'] ) 
			 && ! isset( $_POST['userdata'] )
			) { 
			// Configura os dados do usuário
			$userdata = $_SESSION['userdata'];
			
			// Garante que não é HTTP POST
			$userdata['post'] = false;
		}
		
		// Verifica se existe um $_POST com a chave userdata
		// Tem que ser um array
		if ( isset( $_POST['userdata'] )
			 && ! empty( $_POST['userdata'] )
			 && is_array( $_POST['userdata'] ) 
			) {
			// Configura os dados do usuário
			$userdata = $_POST['userdata'];
			
			// Garante que é HTTP POST
			$userdata['post'] = true;
		}

		// Verifica se existe algum dado de usuário para conferir
		if ( ! isset( $userdata ) || ! is_array( $userdata ) ) {
		
			// Desconfigura qualquer sessão que possa existir sobre o usuário
			$this->logout();
		
			return;
		}

		// Passa os dados do post para uma variável
		if ( $userdata['post'] === true ) {
			$post = true;
		} else {
			$post = false;
		}
		
		// Remove a chave post do array userdata
		unset( $userdata['post'] );
		
		// Verifica se existe algo a conferir
		if ( empty( $userdata ) ) {
			$this->logged_in = false;
			$this->login_error = null;
		
			// Desconfigura qualquer sessão que possa existir sobre o usuário
			$this->logout();
		
			return;
		}
		
		// Extrai variáveis dos dados do usuário
		extract( $userdata );
		
		// Verifica se existe um usuário e senha
		if ( ! isset( $user ) || ! isset( $user_password ) ) {
			$this->logged_in = false;
			$this->login_error = null;
		
			// Desconfigura qualquer sessão que possa existir sobre o usuário
			$this->logout();
		
			return;
		}
		
		// Verifica se o usuário existe na base de dados
		$query = $this->db->query( 
			'SELECT * FROM users WHERE user = ? LIMIT 1', 
			array( $user ) 
		);
		
		// Verifica a consulta
		if ( ! $query ) {
			$this->logged_in = false;
			$this->login_error = 'Internal error.';
		
			// Desconfigura qualquer sessão que possa existir sobre o usuário
			$this->logout();
		
			return;
		}
		
		// Obtém os dados da base de usuário
		$fetch = $query->fetch(PDO::FETCH_ASSOC);
		
		// Obtém o ID do usuário
		$user_id = (int) $fetch['user_id'];
		
		// Verifica se o ID existe
		if ( empty( $user_id ) ){
			$this->logged_in = false;
			$this->login_error = 'User do not exists.';
		
			// Desconfigura qualquer sessão que possa existir sobre o usuário
			$this->logout();
		
			return;
		}
		
		// Confere se a senha enviada pelo usuário bate com o hash do BD
		if ( $this->phpass->CheckPassword( $user_password, $fetch['user_password'] ) ) {
			
			// Se for uma sessão, verifica se a sessão bate com a sessão do BD
			if ( session_id() != $fetch['user_session_id'] && ! $post ) { 
				$this->logged_in = false;
				$this->login_error = 'Wrong session ID.';
				
				// Desconfigura qualquer sessão que possa existir sobre o usuário
				$this->logout();
			
				return;
			}
			
			// Se for um post
			if ( $post ) {
				// Recria o ID da sessão
				session_regenerate_id();
				$session_id = session_id();
				
				// Envia os dados de usuário para a sessão
				$_SESSION['userdata'] = $fetch;
				
				// Atualiza a senha
				$_SESSION['userdata']['user_password'] = $user_password;
				
				// Atualiza o ID da sessão
				$_SESSION['userdata']['user_session_id'] = $session_id;
				
				// Atualiza o ID da sessão na base de dados
				$query = $this->db->query(
					'UPDATE users SET user_session_id = ? WHERE user_id = ?',
					array( $session_id, $user_id )
				);
			}
				
			// Obtém um array com as permissões de usuário
			$_SESSION['userdata']['user_permissions'] = unserialize( $fetch['user_permissions'] );

			// Configura a propriedade dizendo que o usuário está logado
			$this->logged_in = true;
			
			// Configura os dados do usuário para $this->userdata
			$this->userdata = $_SESSION['userdata'];
			
			// Verifica se existe uma URL para redirecionar o usuário
			if ( isset( $_SESSION['goto_url'] ) ) {
				// Passa a URL para uma variável
				$goto_url = urldecode( $_SESSION['goto_url'] );
				
				// Remove a sessão com a URL
				unset( $_SESSION['goto_url'] );
				
				// Redireciona para a página
				echo '<meta http-equiv="Refresh" content="0; url=' . $goto_url . '">';
				echo '<script type="text/javascript">window.location.href = "' . $goto_url . '";</script>';
				//header( 'location: ' . $goto_url );
			}
			
			return;
		} else {
			// O usuário não está logado
			$this->logged_in = false;
			
			// A senha não bateu
			$this->login_error = 'Password does not match.';
		
			// Remove tudo
			$this->logout();
		
			return;
		}
	}
	
	/**
	 * Logout
	 *
	 * Desconfigura tudo do usuárui.
	 *
	 * @param bool $redirect Se verdadeiro, redireciona para a página de login
	 * @final
	 */
	protected function logout( $redirect = false ) {
		// Remove all data from $_SESSION['userdata']
		$_SESSION['userdata'] = array();
		
		// Only to make sure (it isn't really needed)
		unset( $_SESSION['userdata'] );
		
		// Regenerates the session ID
		session_regenerate_id();
		
		if ( $redirect === true ) {
			// Send the user to the login page
			$this->goto_login();
		}
	}
	
	/**
	 * Vai para a página de login
	 */
	protected function goto_login() {
		// Verifica se a URL da HOME está configurada
		if ( defined( 'HOME_URI' ) ) {
			// Configura a URL de login
			$login_uri  = HOME_URI . '/login/';
			
			// A página em que o usuário estava
			$_SESSION['goto_url'] = urlencode( $_SERVER['REQUEST_URI'] );
			
			// Redireciona
			echo '<meta http-equiv="Refresh" content="0; url=' . $login_uri . '">';
			echo '<script type="text/javascript">window.location.href = "' . $login_uri . '";</script>';
			// header('location: ' . $login_uri);
		}
		
		return;
	}
	
	/**
	 * Envia para uma página qualquer
	 *
	 * @final
	 */
	final protected function goto_page( $page_uri = null ) {
		if ( isset( $_GET['url'] ) && ! empty( $_GET['url'] ) && ! $page_uri ) {
			// Configura a URL
			$page_uri  = urldecode( $_GET['url'] );
		}
		
		if ( $page_uri ) { 
			// Redireciona
			echo '<meta http-equiv="Refresh" content="0; url=' . $page_uri . '">';
			echo '<script type="text/javascript">window.location.href = "' . $page_uri . '";</script>';
			//header('location: ' . $page_uri);
			return;
		}
	}
	
	/**
	 * Verifica permissões
	 *
	 * @param string $required A permissão requerida
	 * @param array $user_permissions As permissões do usuário
	 * @final
	 */
	final protected function check_permissions( 
		$required = 'any', 
		$user_permissions = array('any')
	) {
		if ( ! is_array( $user_permissions ) ) {
			return;
		}

		// Se o usuário não tiver permissão
		if ( ! in_array( $required, $user_permissions ) ) {
			// Retorna falso
			return false;
		} else {
			return true;
		}
	}
}