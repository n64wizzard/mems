<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Exception.php");

	class MySQLException extends CustomException {}
	final class Database{
		private $mysqlConnection_, $databaseName_, $hostName_, $username_, $password_;
		static $singletonInstance = null;

		private function hostName(){ return $this->hostName_; }
		public function databaseName(){ return $this->databaseName_; }
		private function username(){ return $this->username_; }
		private function password(){ return $this->password_; }
		private function mysqlConnection(){ return $this->mysqlConnection_; }

		protected function __construct($selectDB=true){
			$this->mysqlConnection_ = NULL;

			if(($iniArray = parse_ini_file("config.ini.php")) === false){
				throw new RuntimeException("INI file not found");
				return ;
			}

			$this->hostName_ = $iniArray["hostName"];
			$this->databaseName_ = $iniArray["databaseName"];
			$this->username_ = $iniArray["userName"];
			$this->password_ = $iniArray["password"];

			$this->connect();
			if($selectDB)
				$this->selectDatabase();
		}
		final private function __clone() {}

		/// @return The database object, or creates it if it does not exist
		final static public function getInstance($selectDB=true){
			if(!$selectDB)
				return new Database(false);
			if(!isset(self::$singletonInstance))
				self::$singletonInstance = new Database(true);
			return self::$singletonInstance;
		}
		
		/// Connect to the database
		private function connect(){
			$this->mysqlConnection_ = mysqli_init();
			if(!$this->mysqlConnection())
				 throw new MySQLException("Init failed");

			if($this->mysqlConnection()->real_connect($this->hostName(),
											$this->username(),
											$this->password(),
											NULL,
											NULL,
											NULL,
											MYSQLI_CLIENT_FOUND_ROWS) === false)
				throw new MySQLException("Connect failed");
		}

		public function selectDatabase(){
			if($this->mysqlConnection()->select_db($this->databaseName()) === false)
				throw new MySQLException("Select Database failed: {$this->databaseName()}");
		}
		
		/// @param $queryType 1 for SELECT, 2 for DELETE, INSERT, REPLACE, or UPDATE
		/// @param $rowsExpected Number of rows expected to be returned or affected in the database
		static public function databaseQuery($sqlQuery, $dbConnection, $queryType=NULL, $rowsExpected=NULL){
			if($dbConnection->real_query($sqlQuery) === false){
				$message  = 'Invalid mysql query: ' . $dbConnection->error . "\n";
				$message .= 'Whole query: ' . $sqlQuery;
				throw new MySQLException($message);
			}
			$mysqliResult = $dbConnection->store_result();
			
			$message = NULL;
			if($queryType == 1){
				$numRowsReturned = $mysqliResult->num_rows;
				if($rowsExpected != $numRowsReturned)
					$message = "Query was expected to return $rowsExpected row(s), only returned $numRowsReturned.\n";
			}
			elseif($queryType == 2){
				$numRowsAffected = $dbConnection->affected_rows;
				if($rowsExpected != $numRowsAffected)
					$message = "Query was expected to affect $rowsExpected row(s), only affected $numRowsAffected.\n";
			}
			if(isset($message)){
				$message .= "Whole query: " . $sqlQuery;
				throw new MySQLException($message);
			}

			return $mysqliResult;
		}

		/// @return The number of affected rows for the last query.  Note that this function
		///  only applies to INSERT,UPDATE,DELETE,etc. queries
		public function numAffectedRows(){ return $this->mysqlConnection()->affected_rows; }

		/// @return The primary key value of the last query (must be an INSERT query)
		public function insertID(){ return $this->mysqlConnection()->insert_id; }

		/// Processes a query
		public function query($query, $queryType=NULL, $rowsExpected=NULL){
			return Database::databaseQuery($query, $this->mysqlConnection(), $queryType, $rowsExpected);
		}

		/// Commits a transaction; must call startTransaction first
		public function commit(){
			$this->mysqlConnection()->commit();
			$this->mysqlConnection()->autocommit(true);
		}

		/// Starts a transaction, must eventually call commit() or rollback().
		public function startTransaction(){
			$this->mysqlConnection()->autocommit(false);
		}

		/// Undoes all queries since startTransaction was called
		public function rollback(){
			$this->mysqlConnection()->rollback();
			$this->mysqlConnection()->autocommit(true);
		}
	}
?>