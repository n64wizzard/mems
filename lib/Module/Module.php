<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("lib/Module/FieldFactory.php");
	
	/// Exception to be thrown if a duplicate unique field is found
	class UniqueFieldException extends CustomException {}

	// TODO: Option to only load specified fields of a ModuleInstance
	// TODO: Move all static functions to a new 'ModuleFactory' class
	class Module{
		static private $modules_;
		
		/// Creates a Module object (Descriptor design-methodology)
		static public function createModule($moduleID){
			if(!isset(self::$modules_))
				self::$modules_ = array();
			if(array_key_exists($moduleID, self::$modules_))
				return self::$modules_[$moduleID];

			$newModule = new Module($moduleID);
			self::$modules_[$moduleID] = $newModule;
			return $newModule;
		}

		/// Loads and creates a new Module object from the DB
		static public function newModule($name){
			$moduleQuery = sprintf(
					"INSERT INTO Module
					('ModuleID', 'Name', 'Removable') VALUES
					('NULL', '%s', '1')",
					mysql_real_escape_string($name));
			$moduleObj = Database::getInstance()->query($moduleQuery, 2, 1)->fetch_object();

			$newModule = new Module($moduleObj->ModuleID);
			self::$modules_[$moduleID] = $newModule;
			return $newModule;
		}
		
		/// @return The ModuleFieldID of the unique field in the Module
		static public function uniqueModuleFieldID($moduleID){
			$uniqueFieldQuery = sprintf(
					"SELECT ModuleFieldID
					FROM ModuleField
					WHERE ModuleID='%s' AND ModuleField.Unique='1'",
					mysql_real_escape_string($moduleID));
			$uniqueFieldObj = Database::getInstance()->query($uniqueFieldQuery, 1, 1)->fetch_object();
			return $uniqueFieldObj->ModuleFieldID;
		}

		// @return An array of all ModuleID => ModuleName
		static public function moduleNames(){
			$moduleNames = array();
			$moduleQuery = sprintf(
					"SELECT Name,ModuleID
					FROM Module
					WHERE Hidden=b'0'");
			$moduleResult = Database::getInstance()->query($moduleQuery);
			while($moduleObj = $moduleResult->fetch_object())
				$moduleNames[$moduleObj->ModuleID] = $moduleObj->Name;
			return $moduleNames;
		}

		private $name_, $removable_, $moduleID_, $moduleFields_;

		// TODO: create a 'CreateModule' static function that does DB access
		private function  __construct($moduleID) {
			$moduleQuery = sprintf(
					"SELECT Name,Removable
					FROM Module
					WHERE ModuleID='%s'",
					mysql_real_escape_string($moduleID));
			$moduleObj = Database::getInstance()->query($moduleQuery, 1, 1)->fetch_object();

			$this->name_ = $moduleObj->Name;
			$this->removable_ = $moduleObj->Removable;
			$this->moduleID_ = $moduleID;

			$moduleFieldsQuery = sprintf(
				"SELECT ModuleFieldID
				FROM ModuleField
				WHERE ModuleID='%s'",
				mysql_real_escape_string($moduleID));
			$moduleFieldsResult = Database::getInstance()->query($moduleFieldsQuery);

			$this->moduleFields_ = array();
			while($moduleFieldObj = $moduleFieldsResult->fetch_object()){
				$moduleFieldID = $moduleFieldObj->ModuleFieldID;
				$this->moduleFields_[$moduleFieldID] = FieldFactory::createModuleField($moduleFieldID);
            }
		}
		public function moduleID(){ return $this->moduleID_; }
		public function name(){ return $this->name_; }
		public function moduleField($moduleFieldID) { return $this->moduleFields_[$moduleFieldID]; }
		public function moduleFields(){ return $this->moduleFields_; }
		
		/// @return The ModuleFieldID of the unique field in the Module
		public function uniqueFieldID(){
			$uniqueID = NULL;
			foreach($this->moduleFields() as $moduleField)
				if($moduleField->unique()){
					if(isset($uniqueID))
						throw new UniqueFieldException("Multiple unique fields found");
					else
						$uniqueID = $moduleField->moduleFieldID();
				}

			return $uniqueID;
		}
	}
?>
