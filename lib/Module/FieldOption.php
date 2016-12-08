<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Security/Audit.php");
	require_once("lib/Database.php");

	/// Database table: ModuleFieldOption
	class FieldOption{
		private $optionLabel_, $optionValue_, $moduleFieldID_;

		/// Looks-up and loads a MFO from the DB
		public static function createFieldOption($fieldOptionID){
			$moduleFieldOptionsQuery = sprintf(
				"SELECT *
				FROM ModuleFieldOption
				WHERE ModuleFieldOptionID='%s'",
				mysql_real_escape_string($fieldOptionID));
			$moduleFieldOptionObj = Database::getInstance()->query($moduleFieldOptionsQuery)->fetch_object();
			return new FieldOption($moduleFieldOptionObj->ModuleFieldID,
									$moduleFieldOptionObj->OptionLabel,
									$moduleFieldOptionObj->OptionValue);
		}
		public function __construct($moduleFieldID, $optionLabel, $optionValue){
			$this->moduleFieldID_ = $moduleFieldID;
			$this->optionLabel_ = $optionLabel;
			$this->optionValue_ = $optionValue;
		}
		public function optionLabel(){ return $this->optionLabel_; }
		public function optionValue(){ return $this->optionValue_; }
	}
?>
