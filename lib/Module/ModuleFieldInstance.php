<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Module/ModuleField.php");
	require_once("lib/Module/FieldFactory.php");
	require_once("lib/Security/Security.php");

	/// The purpose of this class is to store, access, and save the current value 
	///  of a module field for some instance.
	/// One might ask why we do not use inheritance in conjuction with the ModuleField
	///  class?  The reason is we do not want to have to create a new ModuleField
	///  object for everytime we need to create a MFI (for example, for a Listing).
	class ModuleFieldInstance {
		private $currentValue_, $moduleInstanceID_, $moduleField_, $existsInDB_;

		public function  __construct($moduleFieldID, $moduleInstanceID){
			$this->moduleInstanceID_ = $moduleInstanceID;
			$this->moduleField_ = FieldFactory::createModuleField($moduleFieldID);
			$this->currentValue_ = $this->moduleField()->defaultValue();
			$this->existsInDB_ = false;
			$this->moduleInstanceID_ = $moduleInstanceID;

			if(isset($moduleInstanceID))
				$this->loadFromDB();
		}

		public function currentValueIs($currentValue){ $this->currentValue_ = $currentValue; }
		public function currentValue(){ return $this->currentValue_; }
		public function moduleField(){ return $this->moduleField_; }
		public function moduleFieldName(){ return $this->moduleField()->name(); }
		public function moduleFieldID(){ return $this->moduleField()->moduleFieldID(); }
		protected function moduleInstanceID(){ return $this->moduleInstanceID_; }
		public function option($label){ return $this->moduleField()->option($label); }
		public function validate($value){ return $this->moduleField()->validate($value); }
		
		/// This function simply wraps around the actual ModuleField
		public function toHTML($width, $height, $mutable, $includeLabel){
			$output = "";

			if($includeLabel)
				$output .= "<div class='FieldLabel'>" . $this->moduleField_->labelHTML() . "&nbsp;</div>\n";
			
			// We can only write to the file if we either have write permissions, or we are creating a new instance
			//  and have create permissions
			$writePermission = Security::privilege(new ModuleFieldPrivilege("Write", $this->moduleFieldID()), $this->moduleInstanceID())
					|| ($this->moduleInstanceID() == NULL &&
					Security::privilege(new ModuleFieldPrivilege("Create", $this->moduleFieldID())));

			
			$output .= "<div class='FieldContent'>" . 
					$this->moduleField_->toHTML($width, $height, $writePermission && $mutable,
												$this->currentValue(), $this->moduleInstanceID()) .
					"</div>";

			return $output;
		}
		public function listingHTML($width){
			return "<div class='FieldContent'>" . $this->moduleField_->listingHTML($width, $this->currentValue(), $this->moduleInstanceID()) . "</div>";
		}

		/// Saves the current value in this object to the database
		public function saveToDB(){
			$valueToSave = $this->moduleField()->saveToDB($this->currentValue(), $this->moduleInstanceID());
			if(!isset($valueToSave)) return ;
			//$valueToSave = Utils::encrypt($valueToSave);
			$iniArray = Utils::iniSettings();

			if($this->existsInDB_ && Security::privilege(new ModuleFieldPrivilege("Write", $this->moduleFieldID()), $this->moduleInstanceID())){
				$updateQuery = sprintf(
					"UPDATE ModuleFieldInstance
					SET Value=AES_ENCRYPT('%s', '%s')
					WHERE ModuleInstanceID='%s'
					AND ModuleFieldID='%s'",
					mysql_real_escape_string($valueToSave),
					mysql_real_escape_string($iniArray["passCode"]),
					mysql_real_escape_string($this->moduleInstanceID()),
					mysql_real_escape_string($this->moduleField()->moduleFieldID()));
				Database::getInstance()->query($updateQuery);
			}
			elseif(!$this->existsInDB_ && Security::privilege(new ModuleFieldPrivilege("Create", $this->moduleFieldID()), $this->moduleInstanceID())){
				$insertQuery = sprintf(
					"INSERT INTO ModuleFieldInstance
					(`ModuleFieldInstanceID`, `ModuleFieldID`, `ModuleInstanceID`, `Value`) VALUES
					(NULL, '%s', '%s', AES_ENCRYPT('%s', '%s'))",
					mysql_real_escape_string($this->moduleField()->moduleFieldID()),
					mysql_real_escape_string($this->moduleInstanceID()),
					mysql_real_escape_string($valueToSave),
					mysql_real_escape_string($iniArray["passCode"]));
				Database::getInstance()->query($insertQuery, 2, 1);
			}
		}

		/// Loads the current value for this object from the database
		public function loadFromDB(){
			$iniArray = Utils::iniSettings();
			
			// Only load the field if the user is allowed to read its value
			$moduleFieldInstanceQuery = sprintf(
				"SELECT AES_DECRYPT(Value, '%s') as Value
				FROM ModuleFieldInstance
				WHERE ModuleInstanceID='%s' AND ModuleFieldID='%s'",
				mysql_real_escape_string($iniArray["passCode"]),
				mysql_real_escape_string($this->moduleInstanceID()),
				mysql_real_escape_string($this->moduleField()->moduleFieldID()));
			$moduleFieldInstanceResult = Database::getInstance()->query($moduleFieldInstanceQuery);

			if($moduleFieldInstanceResult->num_rows == 1){
				$this->existsInDB_ = true;
				$moduleFieldValue = $moduleFieldInstanceResult->fetch_object()->Value;
				if(Security::privilege(new ModuleFieldPrivilege("Read", $this->moduleFieldID()), $this->moduleInstanceID())){
					//$moduleFieldValue = Utils::decrypt($moduleFieldValue);
					$this->currentValueIs($moduleFieldValue);
				}
			}
		}
	}
?>
