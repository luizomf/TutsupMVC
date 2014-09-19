<?php
/**
 * TutsupDB - Classe para gerenciamento da base de dados
 *
 * @package TutsupMVC
 * @since 0.1
 */
class TutsupDB
{
	/** DB properties */
	public $host      = 'localhost', // Host da base de dados 
	       $db_name   = 'tutsup',    // Nome do banco de dados
	       $password  = '',          // Senha do usuário da base de dados
	       $user      = 'root',      // Usuário da base de dados
	       $charset   = 'utf8',      // Charset da base de dados
	       $pdo       = null,        // Nossa conexão com o BD
	       $error     = null,        // Configura o erro
	       $debug     = false,       // Mostra todos os erros 
	       $last_id   = null;        // Último ID inserido
	
	/**
	 * Construtor da classe
	 *
	 * @since 0.1
	 * @access public
	 * @param string $host     
	 * @param string $db_name
	 * @param string $password
	 * @param string $user
	 * @param string $charset
	 * @param string $debug
	 */
	public function __construct(
		$host     = null,
		$db_name  = null,
		$password = null,
		$user     = null,
		$charset  = null,
		$debug    = null
	) {
	
		// Configura as propriedades novamente.
		// Se você fez isso no início dessa classe, as constantes não serão
		// necessárias. Você escolhe...
		$this->host     = defined( 'HOSTNAME'    ) ? HOSTNAME    : $this->host;
		$this->db_name  = defined( 'DB_NAME'     ) ? DB_NAME     : $this->db_name;
		$this->password = defined( 'DB_PASSWORD' ) ? DB_PASSWORD : $this->password;
		$this->user     = defined( 'DB_USER'     ) ? DB_USER     : $this->user;
		$this->charset  = defined( 'DB_CHARSET'  ) ? DB_CHARSET  : $this->charset;
		$this->debug    = defined( 'DEBUG'       ) ? DEBUG       : $this->debug;
	
		// Conecta
		$this->connect();
		
	} // __construct
	
	/**
	 * Cria a conexão PDO
	 *
	 * @since 0.1
	 * @final
	 * @access protected
	 */
	final protected function connect() {
	
		/* Os detalhes da nossa conexão PDO */
		$pdo_details  = "mysql:host={$this->host};";
		$pdo_details .= "dbname={$this->db_name};";
		$pdo_details .= "charset={$this->charset};";
		 
		// Tenta conectar
		try {
		
			$this->pdo = new PDO($pdo_details, $this->user, $this->password);
			
			// Verifica se devemos debugar
			if ( $this->debug === true ) {
			
				// Configura o PDO ERROR MODE
				$this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
				
			}
			
			// Não precisamos mais dessas propriedades
			unset( $this->host     );
			unset( $this->db_name  );
			unset( $this->password );
			unset( $this->user     );
			unset( $this->charset  );
		
		} catch (PDOException $e) {
			
			// Verifica se devemos debugar
			if ( $this->debug === true ) {
			
				// Mostra a mensagem de erro
				echo "Erro: " . $e->getMessage();
				
			}
			
			// Kills the script
			die();
		} // catch
	} // connect
	
	/**
	 * query - Consulta PDO
	 *
	 * @since 0.1
	 * @access public
	 * @return object|bool Retorna a consulta ou falso
	 */
	public function query( $stmt, $data_array = null ) {
		
		// Prepara e executa
		$query      = $this->pdo->prepare( $stmt );
		$check_exec = $query->execute( $data_array );
		
		// Verifica se a consulta aconteceu
		if ( $check_exec ) {
			
			// Retorna a consulta
			return $query;
			
		} else {
		
			// Configura o erro
			$error       = $query->errorInfo();
			$this->error = $error[2];
			
			// Retorna falso
			return false;
			
		}
	}
	
	/**
	 * insert - Insere valores
	 *
	 * Insere os valores e tenta retornar o último id enviado
	 *
	 * @since 0.1
	 * @access public
	 * @param string $table O nome da tabela
	 * @param array ... Ilimitado número de arrays com chaves e valores
	 * @return object|bool Retorna a consulta ou falso
	 */
	public function insert( $table ) {
		// Configura o array de colunas
		$cols = array();
		
		// Configura o valor inicial do modelo
		$place_holders = '(';
		
		// Configura o array de valores
		$values = array();
		
		// O $j will assegura que colunas serão configuradas apenas uma vez
		$j = 1;
		
		// Obtém os argumentos enviados
		$data = func_get_args();
		
		// É preciso enviar pelo menos um array de chaves e valores
		if ( ! isset( $data[1] ) || ! is_array( $data[1] ) ) {
			return;
		}
		
		// Faz um laço nos argumentos
		for ( $i = 1; $i < count( $data ); $i++ ) {
		
			// Obtém as chaves como colunas e valores como valores
			foreach ( $data[$i] as $col => $val ) {
			
				// A primeira volta do laço configura as colunas
				if ( $i === 1 ) {
					$cols[] = "`$col`";
				}
				
				if ( $j <> $i ) {
					// Configura os divisores
					$place_holders .= '), (';
				}
				
				// Configura os place holders do PDO
				$place_holders .= '?, ';
				
				// Configura os valores que vamos enviar
				$values[] = $val;
				
				$j = $i;
			}
			
			// Remove os caracteres extra dos place holders
			$place_holders = substr( $place_holders, 0, strlen( $place_holders ) - 2 );
		}
		
		// Separa as colunas por vírgula
		$cols = implode(', ', $cols);
		
		// Cria a declaração para enviar ao PDO
		$stmt = "INSERT INTO `$table` ( $cols ) VALUES $place_holders) ";
		
		// Insere os valores
		$insert = $this->query( $stmt, $values );
		
		// Verifica se a consulta foi realizada com sucesso
		if ( $insert ) {
			
			// Verifica se temos o último ID enviado
			if ( method_exists( $this->pdo, 'lastInsertId' ) 
				&& $this->pdo->lastInsertId() 
			) {
				// Configura o último ID
				$this->last_id = $this->pdo->lastInsertId();
			}
			
			// Retorna a consulta
			return $insert;
		}
		
		// The end :)
		return;
	} // insert
	
	/**
	 * Update simples
	 *
	 * Atualiza uma linha da tabela baseada em um campo
	 *
	 * @since 0.1
	 * @access protected
	 * @param string $table Nome da tabela
	 * @param string $where_field WHERE $where_field = $where_field_value
	 * @param string $where_field_value WHERE $where_field = $where_field_value
	 * @param array $values Um array com os novos valores
	 * @return object|bool Retorna a consulta ou falso
	 */
	public function update( $table, $where_field, $where_field_value, $values ) {
		// Você tem que enviar todos os parâmetros
		if ( empty($table) || empty($where_field) || empty($where_field_value)  ) {
			return;
		}
		
		// Começa a declaração
		$stmt = " UPDATE `$table` SET ";
		
		// Configura o array de valores
		$set = array();
		
		// Configura a declaração do WHERE campo=valor
		$where = " WHERE `$where_field` = ? ";
		
		// Você precisa enviar um array com valores
		if ( ! is_array( $values ) ) {
			return;
		}
		
		// Configura as colunas a atualizar
		foreach ( $values as $column => $value ) {
			$set[] = " `$column` = ?";
		}
		
		// Separa as colunas por vírgula
		$set = implode(', ', $set);
		
		// Concatena a declaração
		$stmt .= $set . $where;
		
		// Configura o valor do campo que vamos buscar
		$values[] = $where_field_value;
		
		// Garante apenas números nas chaves do array
		$values = array_values($values);
				
		// Atualiza
		$update = $this->query( $stmt, $values );
		
		// Verifica se a consulta está OK
		if ( $update ) {
			// Retorna a consulta
			return $update;
		}
		
		// The end :)
		return;
	} // update

	/**
	 * Delete
	 *
	 * Deleta uma linha da tabela
	 *
	 * @since 0.1
	 * @access protected
	 * @param string $table Nome da tabela
	 * @param string $where_field WHERE $where_field = $where_field_value
	 * @param string $where_field_value WHERE $where_field = $where_field_value
	 * @return object|bool Retorna a consulta ou falso
	 */
	public function delete( $table, $where_field, $where_field_value ) {
		// Você precisa enviar todos os parâmetros
		if ( empty($table) || empty($where_field) || empty($where_field_value)  ) {
			return;
		}
		
		// Inicia a declaração
		$stmt = " DELETE FROM `$table` ";

		// Configura a declaração WHERE campo=valor
		$where = " WHERE `$where_field` = ? ";
		
		// Concatena tudo
		$stmt .= $where;
		
		// O valor que vamos buscar para apagar
		$values = array( $where_field_value );

		// Apaga
		$delete = $this->query( $stmt, $values );
		
		// Verifica se a consulta está OK
		if ( $delete ) {
			// Retorna a consulta
			return $delete;
		}
		
		// The end :)
		return;
	} // delete
	
} // Class TutsupDB