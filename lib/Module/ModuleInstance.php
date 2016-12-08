<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("lib/Module/Module.php");
	require_once("lib/Module/ModuleFieldInstance.php");
	require_once("lib/Security/Security.php");

	/// Module Instances are not Subclasses from Module for the same reasoning as MFIs
	///  and Module Fields: we do not want to create a new Module object, for each
	///  individual Module Instance.
	class ModuleInstance{
		private static $moduleInstances = array();
		public static function createModuleInstance($moduleInstanceID, $moduleID=NULL){
			if(!isset($moduleInstances[$moduleInstanceID]))
				$moduleInstances[$moduleInstanceID] = new ModuleInstance($moduleInstanceID, $moduleID);

			return $moduleInstances[$moduleInstanceID];
		}

		/// Load and create a module instance from the DB
		static public function newModuleInstance($moduleID){
			if(Security::privilege(new ModulePrivilege("CreateInstance", $moduleID))){
				$moduleInstanceQuery = sprintf(
					"INSERT INTO ModuleInstance
					SET ModuleID='%s'",
					mysql_real_escape_string($moduleID));
				Database::getInstance()->query($moduleInstanceQuery, 2, 1);
				$moduleInstanceID = Database::getInstance()->insertID();
			}
			else
				throw new PrivilegeException("Insufficient privileges to create a new instance");

			return new ModuleInstance($moduleInstanceID, $moduleID);
		}
		private $moduleInstanceID_, 
				$moduleFieldInstances_,
				$module_,
				$hasFields_;	// Whether this module has any fields that this user can view

		public function  __construct($moduleInstanceID, $moduleID=NULL) {
			$this->moduleInstanceID_ = $moduleInstanceID;

			if(!isset($moduleID)){
				$moduleInstanceQuery = sprintf(
						"SELECT ModuleID
						FROM ModuleInstance
						WHERE ModuleInstanceID='%s'",
						mysql_real_escape_string($moduleInstanceID));
				$moduleInstanceObj = Database::getInstance()->query($moduleInstanceQuery, 1, 1)->fetch_object();
				$moduleID = $moduleInstanceObj->ModuleID;
			}

			$this->module_ = Module::createModule($moduleID);
			$this->hasFields_ = false;

			$this->moduleFieldInstances_ = array();
			foreach($this->module()->moduleFields() as $moduleField){
				$this->moduleFieldInstances_[$moduleField->moduleFieldID()] = new ModuleFieldInstance($moduleField->moduleFieldID(), $moduleInstanceID);

				if(!$this->hasFields_ && Security::privilege(new ModuleFieldPrivilege("Read", $moduleField->moduleFieldID()), $moduleInstanceID))
					$this->hasFields_ = true;
			}
		}
		public function hasFields(){ return $this->hasFields_; }
		public function moduleInstanceID(){ return $this->moduleInstanceID_; }

		/// Depending on the input parameter used, looks-up a MFI by either its ID or name
		/// @return The ModuleFieldInstance object
		public function moduleFieldInstance($moduleFieldID, $moduleFieldName=NULL){
			if(isset($moduleFieldID)){
				if(isset($this->moduleFieldInstances_[$moduleFieldID]))
					return $this->moduleFieldInstances_[$moduleFieldID];
				else
					throw new InvalidArgumentException("ModuleFieldID not found: " . $moduleFieldID);
			}
			elseif(isset($moduleFieldName)){
				foreach($this->moduleFieldInstances_ as $moduleFieldInstance)
					if($moduleFieldInstance->moduleFieldName() == $moduleFieldName)
						return $moduleFieldInstance;
			}
			else
				throw new InvalidArgumentException("ModuleFieldInstance() requires one non-null argument");
		}
		public function module(){ return $this->module_; }
		public function moduleID(){ return $this->module()->moduleID(); }

		/// Checks all applicable values in the database to make sure we don't duplicate one which is supposed to be unique
		/// @return If a duplicate is found, returns its ID, otherwise returns NULL
		protected function duplicateUniqueFieldValue(){
			$uniqueFieldID = $this->module()->uniqueFieldID();
			$uniqueFieldInstanceQuery = sprintf(
					"SELECT ModuleInstanceID
					FROM ModuleFieldInstance
					WHERE ModuleFieldID='%s' AND Value='%s'",
					mysql_real_escape_string($uniqueFieldID),
					mysql_real_escape_string($this->moduleFieldInstance($uniqueFieldID)->currentValue()));
			$uniqueFieldInstanceResult = Database::getInstance()->query($uniqueFieldInstanceQuery);
			
			if($uniqueFieldInstanceResult->num_rows >= 1){
				$moduleInstanceID = $uniqueFieldInstanceResult->fetch_object()->ModuleInstanceID;
				if($moduleInstanceID != $this->moduleInstanceID())
					return $moduleInstanceID;
			}

			return NULL;
		}
		
		/// Saves all ModuleFieldInstances to the database
		public function saveToDB(){
			$uniqueFieldID = $this->duplicateUniqueFieldValue();
			if(isset($uniqueFieldID))
				throw new UniqueFieldException("Unique value already found in database.  ModuleInstanceID={$uniqueFieldID}");
			foreach($this->moduleFieldInstances_ as $moduleFieldInstance)
				$moduleFieldInstance->saveToDB($this->moduleInstanceID());
		}

		/// Updates all corresponding ModuleField values.
		public function moduleFieldValuesAre($fieldsToModify){
			foreach($fieldsToModify as $moduleFieldID => $value){
				if(!isset($this->moduleFieldInstances_[$moduleFieldID]))
					$this->moduleFieldInstances_[$moduleFieldID] = new ModuleFieldInstance($moduleFieldID, $this->moduleInstanceID());
				$this->moduleFieldInstances_[$moduleFieldID]->currentValueIs($value);
			}
		}
		/// @return All current unique field values for some module
		static public function uniqueFieldValues($moduleID){
			$output = array();
			$moduleQuery = sprintf(
					"SELECT ModuleInstanceID,Value
					FROM ModuleFieldInstance
					WHERE ModuleFieldID='%s'",
					mysql_real_escape_string(Module::uniqueFieldID(moduleID)));
			$queryResult = Database::getInstance()->query($moduleQuery);
			while($moduleFieldObj = $queryResult->fetch_object()){
				$output[$moduleFieldObj->ModuleInstanceID] = $moduleFieldObj->Value;
			}

			return $output;
		}

		/// Removes a module instance from the DB
		public static function deleteInstance($moduleInstanceID){
			$deleteQuery = sprintf(
				"DELETE
				FROM ModuleInstance
					WHERE ModuleInstanceID='%s'",
				mysql_real_escape_string($moduleInstanceID));
			try{ $deleteQuery = Database::getInstance()->query($deleteQuery, 2, 1); }
			catch(MySQLException $e){
				Audit::logError($e);
				return "Error deleting ModuleInstance from DB";
			}
			return "";
		}
	}
?>
