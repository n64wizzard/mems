<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Security/Audit.php");
	require_once("lib/Position.php");
	require_once("lib/Module/ModuleField.php");
	
	class FormField {
		private $mutable_, /// 0: Field cannot be edited(static text); 1: editable as specified form type
				$dbMutable_,	/// What the value of mutable is stored in the database
				$formFieldID_,
				$position_,
				$moduleFieldInstance_,
				$includeLabel_,
				$removable_;

		/// Looks-up a form field that exists in the DB, and populates a FormField object
		public static function createFormField($formFieldID, $moduleFieldInstance){
			$formFieldQuery = sprintf(
				"SELECT *
				FROM FormField
				WHERE FormFieldID='%s'",
				mysql_real_escape_string($formFieldID));
			$formFieldObj = Database::getInstance()->query($formFieldQuery, 1, 1)->fetch_object();

			$position = new Position($formFieldObj->Pos_Left, $formFieldObj->Pos_Top,
												$formFieldObj->Pos_Width, $formFieldObj->Pos_Height);

			return new FormField($formFieldID, $formFieldObj->Mutable, $position, 
								$moduleFieldInstance, $formFieldObj->Mutable, 
								$formFieldObj->IncludeLabel, $formFieldObj->Removable);
		}
		function __construct($formFieldID, $mutable, $position, $moduleFieldInstance, $dbMutable, $includeLabel, $removable){
			$this->mutable_ = $mutable;
			$this->dbMutable_ = $dbMutable;
			$this->formFieldID_ = $formFieldID;
			$this->position_ = $position;
			$this->moduleFieldInstance_ = $moduleFieldInstance;
			$this->includeLabel_ = $includeLabel;
			$this->removable_ = $removable;
		}

		public function mutable(){ return $this->mutable_; }
		public function mutableIs($mutable){ $this->mutable_ = $mutable; }

		public function moduleFieldInstance(){ return $this->moduleFieldInstance_; }
		public function formFieldID(){ return $this->formFieldID_; }

		public function position(){ return $this->position_; }
		public function positionIs($position){ $this->position_ = $position; }

		public function removable(){ return $this->removable_; }

		private function includeLabel(){ return $this->includeLabel_; }
		public function includeLabelIs($includeLabel){ $this->includeLabel_ = $includeLabel; }

		public function toHTML(){
			$output = "";
			$innerHTML = $this->moduleFieldInstance()->toHTML(
							$this->position()->width(),
							$this->position()->height(),
							$this->mutable(),
							$this->includeLabel());
			$divID = "modulefield_" . $this->moduleFieldInstance()->moduleFieldID();
			$output .= $this->position()->toHTML($divID, $innerHTML);

			$randomID = rand();
			$output .= "<div id='$randomID'></div>\n";

			// Some of the following data is used to make field validation and submission
			//  easier and more reliable.  Other data is stored for use in the
			//  Form Editor.
			$output .= <<<EOD
<script type="text/javascript">
	var currField = $("#$randomID").prev();
	currField.data("mutable", '{$this->dbMutable_}');
	currField.data("moduleFieldID", '{$this->moduleFieldInstance()->moduleFieldID()}');
	currField.data("moduleFieldName", '{$this->moduleFieldInstance()->moduleFieldName()}');
	currField.data("formFieldID", '{$this->formFieldID()}');
	currField.data("includeLabel", '{$this->includeLabel()}');
	currField.data("removable", '{$this->removable()}');
</script>
EOD;
			return $output;
		}

		/// Removes this formfield from the database.
		public function removeFromDB($moduleID){
			if(!Security::privilege(new ModulePrivilege("EditForm", $moduleID)))
				throw new PrivilegeException("EditForm in FormField::removeFromDB");

			if(!$this->removable())
				throw new PrivilegeException("Attempt to remove an 'unremovable' Form Field");

			$deleteQuery = sprintf(
				"DELETE FROM FormField
				WHERE FormFieldID='%s'",
				mysql_real_escape_string($this->formFieldID()));
			Database::getInstance()->query($deleteQuery, 2, 1);
		}

		/// Saves all attributes of this FormField to the DB
		public function saveToDB($formID, $moduleID){
			if(!Security::privilege(new ModulePrivilege("EditForm", $moduleID)))
				throw new PrivilegeException("EditForm in FormField::saveToDB");

			if($this->formFieldID_ !== false){
				$updateQuery = sprintf(
					"UPDATE FormField
					SET Pos_Top='%s', Mutable='%s', IncludeLabel=b'%s',
						Pos_Left='%s', Pos_Width='%s', Pos_Height='%s'
					WHERE FormFieldID='%s'",
					mysql_real_escape_string($this->position()->top()),
					mysql_real_escape_string($this->mutable()),
					mysql_real_escape_string($this->includeLabel()),
					mysql_real_escape_string($this->position()->left()),
					mysql_real_escape_string($this->position()->width()),
					mysql_real_escape_string($this->position()->height()),
					mysql_real_escape_string($this->formFieldID()));
				Database::getInstance()->query($updateQuery);
			}
			else{
				$insertQuery = sprintf(
					"INSERT INTO FormField
					(`ModuleFieldID`, `FormID`, `Pos_Top`, `Pos_Left`, `Pos_Width`, `Pos_Height`, `Mutable`, `Removable`, `IncludeLabel`) VALUES
					('%s', '%s', '%s', '%s', '%s', '%s', '%s', b'1', b'%s')",
					mysql_real_escape_string($this->moduleFieldInstance()->moduleFieldID()),
					mysql_real_escape_string($formID),
					mysql_real_escape_string($this->position()->top()),
					mysql_real_escape_string($this->position()->left()),
					mysql_real_escape_string($this->position()->width()),
					mysql_real_escape_string($this->position()->height()),
					mysql_real_escape_string($this->mutable()),
					mysql_real_escape_string($this->includeLabel()));
				Database::getInstance()->query($insertQuery, 2, 1);
			}
		}
	}
?>
