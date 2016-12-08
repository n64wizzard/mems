<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Security/Audit.php");
	require_once("lib/Security/Security.php");
	require_once("lib/fieldtypes/SelectField.php");

	/// Select Roles allows us to choose one or more roles.
	/// There is one option: SaveUserRole.  But as it is only used by the Member module, it does not make sense,
	///  to expose it as an option the user can choose.
	class SelectRoles extends ComboBoxField{
		static public function type(){ return "SelectRoles"; }
		private $saveUserRoles_;	/// Whether we should save the selected roles into the UserRoles Table.
		public static function initOptions($moduleFieldID){
			$options = array();

			foreach(Security::roleList(true) as $roleID => $roleName)
				$options[] = new FieldOption($moduleFieldID, $roleName, $roleID);

			return $options;
		}
		public function  __construct($name, $label, $type, $description, $unique, $regex, $defaultValue, $options, $hidden, $moduleFieldID = NULL, $moduleID = NULL) {
			parent::__construct($name, $label, $type, $description, $unique, $regex, $defaultValue, $options, $hidden, $moduleFieldID, $moduleID);
			$fieldSettings = parent::initOptions($moduleFieldID);
				$this->saveUserRoles_ = false;
				foreach($fieldSettings as $fieldSetting)
					if($fieldSetting->optionLabel() == "SaveUserRoles")
						$this->saveUserRoles_ = true;
		}
		public function toHTML($width, $height, $mutable, $currentValue, $moduleInstanceID){
			$output = "";
			$mutable &= (Security::privilege(new GeneralPrivilege("ChangeRoles")) || !$this->saveUserRoles_);
			$disabled = $mutable ? "" : " disabled='disabled' ";
			$output .= "<select name='" . $this->name() .
				"' id='field_" . $this->moduleFieldID() .
				"' style='width:" . $width .
				"px; height:" . $height .
				"px;' class='ValidateSelect' multiple {$disabled}>\n";

			$currentOptions = explode("##", $currentValue);
			if($this->saveUserRoles_)
				$currentOptions = array_keys(Security::roleList(true, $moduleInstanceID));

			foreach($this->options_ as $option){
				$selected = in_array($option->optionValue(), $currentOptions) !== false ? "SELECTED" : "";
				if($mutable || $selected != "")
					$output .= "<option value='{$option->optionValue()}' {$selected}>{$option->optionLabel()}</option>\n";
			}
			$output .= "</select>";
			return $output;
		}

		/// If 'SaveUserRoles' is set, then we also save the change to the UserRole table
		public function saveToDB($newValue, $moduleInstanceID){
			if(!$this->saveUserRoles_)
				return $newValue;

			if(!Security::privilege(new GeneralPrivilege("ChangeRoles")))
				return "";

			$newRoleIDs = explode("##", $newValue);

			$deleteRolesSQL = "";
			foreach($newRoleIDs as $newRoleID)
				$deleteRolesSQL .= "RoleID != '$newRoleID' AND ";
			// Delete roles that the user no longer has
			$deleteUserRolesQuery = sprintf(
				"DELETE FROM UserRole
				WHERE UserMIID='%s' AND (%s)",
				mysql_real_escape_string($moduleInstanceID),
				substr($deleteRolesSQL, 0, -5));
			$deleteUserRolesResult = Database::getInstance()->query($deleteUserRolesQuery);

			$rolesRemaining = count($newRoleIDs) - Database::getInstance()->numAffectedRows();
			if($rolesRemaining != 0 && $newValue != ""){
				$newRolesSQL = "";
				foreach($newRoleIDs as $newRole)
					$newRolesSQL .= "('" . mysql_real_escape_string($newRole) . "', '$moduleInstanceID'),";
				// Add new roles that the user has acquired
				$insertQuery = sprintf(
					"INSERT INTO UserRole
					(`RoleID`, `UserMIID`) VALUES
					%s
					ON DUPLICATE KEY UPDATE `RoleID`=`RoleID`",
					substr($newRolesSQL, 0, -1));
				// We can't enforce an exact affected-row count here, as duplicates can
				//  mess it up.  We could instead use "INSERT IGNORE" syntax, but then
				//  we will not be made aware of other errors (such as foreign key constraint).
				Database::getInstance()->query($insertQuery);
			}

			return $newValue;
		}
		
		// PENDING: This should not be here either, except due to subclassing from SelectField...
		public function showOptions() {
			return "";
		}
	}
?>
