<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Security/Audit.php");
	require_once("lib/Security/Security.php");
	require_once("lib/fieldtypes/SelectField.php");

	/// This class allows a user to "associate" themselves with another module instance.
	///  Eg. A user registering for an event.
	/// Options: RoleFieldID(ModuleFieldID), MaxRegistrations(Int), AddText(String), DropText(String)
	// If the field is mutable, it appears as a combo-box, if only readable, then as a button to register and a list of
	//  current registrants.  Otherwise, nothing is shown.
	class SelectMembers extends ComboBoxField{
		static public function type(){ return "SelectMembers"; }
		private $roleFieldID_,	/// ID of a connected user roles field (NULL otherwise)
				$maxRegistration_ = "0",	/// Maximum number of people that can be chosen for this field
				$dropText_ = "Drop",
				$addText_ = "Register",
				$deleteOnDrop_ = false;
		
		public static function initOptions($moduleFieldID){
			$options = array();
			$uniqueArray = PageInstanceLink::uniqueList(1);
			foreach($uniqueArray as $unqiueMIID => $uniqueValue)
				$options[] = new FieldOption($moduleFieldID, $uniqueValue, $unqiueMIID);

			return $options;
		}
		public function roleFieldID(){ return $this->roleFieldID_; }
		public function maxRegistration(){ return $this->maxRegistration_; }
		public function dropText(){ return $this->dropText_; }
		public function addText(){ return $this->addText_; }
		public function deleteOnDrop(){ return $this->deleteOnDrop_; }
		
		public function defaultValue(){
			if(isset($this->roleFieldID_) || Security::privilege(new ModuleFieldPrivilege("Write", $this->moduleFieldID())))
				return $this->defaultValue_;
			else
				return Security::userMIID();
		}

		public function  __construct($name, $label, $type, $description, $unique, $regex, $defaultValue, $options, $hidden, $moduleFieldID = NULL, $moduleID = NULL) {
			parent::__construct($name, $label, $type, $description, $unique, $regex, $defaultValue, $options, $hidden, $moduleFieldID, $moduleID);
			$this->roleFieldID_ = NULL;

			$fieldSettings = parent::initOptions($moduleFieldID);
			foreach($fieldSettings as $fieldSetting){
				if($fieldSetting->optionLabel() == "RoleFieldID")
					$this->roleFieldID_ = $fieldSetting->optionValue();
				elseif($fieldSetting->optionLabel() == "MaxRegistrations")
					$this->maxRegistration_ = $fieldSetting->optionValue();
				elseif($fieldSetting->optionLabel() == "DropText")
					$this->dropText_ = $fieldSetting->optionValue();
				elseif($fieldSetting->optionLabel() == "AddText")
					$this->addText_ = $fieldSetting->optionValue();
				elseif($fieldSetting->optionLabel() == "DeleteOnDrop")
					$this->deleteOnDrop_ = $fieldSetting->optionValue();
			}
		}
		
		public function toHTML($width, $height, $mutable, $currentValue, $moduleInstanceID){
			$currentOptions = explode("##", $currentValue);
			$output = "";

			// If we are allowed to edit the field, show the entire list, if we are in the
			//  process of creating a new instance, just show the current user ID.
			if($mutable){
				$output .= "<select name='" . $this->name() .
					"' class='ValidateSelect' id='field_" . $this->moduleFieldID() .
					"' style='width:" . $width .
					"px; height:" . $height .
					"px;' multiple>\n";

				
				foreach($this->options_ as $option){
					$selected = in_array($option->optionValue(), $currentOptions) !== false ? "SELECTED" : "";
					if(Security::privilege(new ModuleFieldPrivilege("Write", $this->moduleFieldID()), $moduleInstanceID) || (!isset($moduleInstanceID) && Security::userMIID() == $option->optionValue()))
						$output .= "<option value='{$option->optionValue()}' {$selected}>{$option->optionLabel()}</option>\n";
				}
				$output .= "</select>";
			}
			elseif(isset($moduleInstanceID)){	// We don't want to show the buttons until the instance has been created
				$output .= "<div style='width:{$width}px;height:{$height}px;'>";

				$approved = $this->roleFieldID() === NULL;	// If there is no role field, we are always approved
				if(isset($moduleInstanceID))
					$moduleInstance = ModuleInstance::createModuleInstance($moduleInstanceID);
				else
					$approved = true;
				
				if(!$approved){	// We need to check that we are a valid choice given the value in the Role field
					$rolesFieldInstance = $moduleInstance->moduleFieldInstance($this->roleFieldID());
					$allowedRoleIDs = explode("##", $rolesFieldInstance->currentValue());

					foreach(Security::roleList() as $roleID => $roleName)
						if(array_search($roleID, $allowedRoleIDs) !== false)
							$approved = true;
					if(!$approved)
						$output .= "You are not authorized to work this position.";
				}
				if($approved){
					// Show the add or drop button depending on our current registration status
					if($currentValue == "" || count($currentOptions) < $this->maxRegistration()){
						$userMIID = Security::userMIID();
						$output .= "<div id='field_{$this->moduleFieldID()}' class='SelectMemberField'>{$this->addText()}</div>\n";
					}
					elseif(in_array(Security::userMIID(), $currentOptions) !== false){
						$userMIID = Security::userMIID();
						$output .= "<div id='field_{$this->moduleFieldID()}' class='SelectMemberField'>{$this->dropText()}</div>\n";
					}
				}

				// Show a list of everyone who has already signed-up
				if($currentValue != ""){
					if(in_array(Security::userMIID(), $currentOptions) !== false)
						$output .= "<br/>";
					foreach($currentOptions as $registrant)
						$output .= UserData::userName($registrant) . "<br/>";

					$output = substr($output, 0, -5);
				}
				
				$output .= "</div>";
			}
			// If we are creating a new instance and we are unable to write to this field,
			//  but we can "create" it, set our user name as the current value (but don't)
			//  let us change it
			elseif(Security::privilege(new ModuleFieldPrivilege("Create", $this->moduleFieldID()))){
				$output .= "<input name='" . $this->name() .
					"' id='field_" . $this->moduleFieldID() .
					"' type='text' value='" . Security::userMIID() .
					"' style='display:none;' />";
				$output .= "<div style='width:{$width}px;height:{$height}px;'>";
				$output .= UserData::userName(Security::userMIID());
				$output .= "</div>";
			}

			return $output;
		}
		public function listingHTML($width, $currentValue, $moduleInstanceID){
			$currentOptions = explode("##", $currentValue);
			$output = "<div style='width:{$width}px;height:22px;'>";
			if($currentValue != ""){
				foreach($currentOptions as $registrant)
					$output .= UserData::userName($registrant) . ", ";
				$output = substr($output, 0, -2);
			}
			$output .= "</div>\n";
			return $output;
		}
		
		public function showOptions() {
			$output = "";
			$output .= <<<EOD
<div class='FieldLabel' style='padding-right:5px' ><label for='field_{$this->moduleFieldID()}_option1'>Role Field: </label></div>
<div class='FieldContent' style='padding-right:5px' ><select name='RoleField' id='field_{$this->moduleFieldID()}_option1'>
EOD;
			$roleFieldQuery = sprintf(
				"SELECT ModuleFieldID, Name
				FROM ModuleField
				WHERE Type='SelectRoles' AND ModuleID='%s'",
				mysql_real_escape_string($this->moduleID()));
			$roleFieldResult = Database::getInstance()->query($roleFieldQuery);
			$roleFieldID = $this->roleFieldID();
			$output .= "<option value=''></option>";
			while($roleFieldObj = $roleFieldResult->fetch_object()) {
				$output .= "<option value='{$roleFieldObj->ModuleFieldID}' ";
				if(isset($roleFieldID) && $roleFieldID == $roleFieldObj->ModuleFieldID)
					$output .= "selected='selected' ";
				$output .= ">{$roleFieldObj->Name}</option>";
			}

			$output .= <<<EOD
</select></div>
EOD;

			$output .= <<<EOD
<div class='FieldLabel' style='padding-right:5px' ><label for='field_{$this->moduleFieldID()}_option2'>Max Registrations: </label></div>
<div class='FieldContent' style='padding-right:5px' ><input name='MaxRegistrations' id='field_{$this->moduleFieldID()}_option2' type='text' value='{$this->maxRegistration()}' /></div>
<script type="text/javascript">
	$("#field_{$this->moduleFieldID()}_option2").keyup(function(){
		this.value = this.value.replace(/[^0-9]*/g,'');
	});
</script>
EOD;

			
			$deleteOnDrop = $this->deleteOnDrop() ? "checked='checked'" : '';
			$output .= <<<EOD
<div class='FieldLabel' style='padding-right:5px' ><label for='field_{$this->moduleFieldID()}_option5'>Delete Instance On Drop: </label></div>
<div class='FieldContent' style='padding-right:5px' ><input name='DeleteOnDrop' id='field_{$this->moduleFieldID()}_option5' type='checkbox' $deleteOnDrop /></div>
EOD;

			$output .= <<<EOD
<div class='FieldLabel' style='clear:both;padding-right:5px' ><label for='field_{$this->moduleFieldID()}_option3'>Add Text: </label></div>
<div class='FieldContent' style='padding-right:5px' ><input name='AddText' id='field_{$this->moduleFieldID()}_option3' type='text' value='{$this->addText()}' /></div>
EOD;

			$output .= <<<EOD
<div class='FieldLabel' style='padding-right:5px' ><label for='field_{$this->moduleFieldID()}_option4'>Drop Text: </label></div>
<div class='FieldContent' style='padding-right:5px' ><input name='DropText' id='field_{$this->moduleFieldID()}_option4' type='text' value='{$this->dropText()}' /></div>
EOD;

			return $output;
		}

		// TODO: This should not be needed, see the comment in SelectField
		public function saveOptions($optionIDs, $optionValues) {
			for($i = 0; $i < count($optionIDs); $i++) {
				try {
					$optionsQuery = sprintf(
							"UPDATE ModuleFieldOption
							SET OptionValue='%s'
							WHERE ModuleFieldID='%s'
							AND OptionLabel='%s'",
							mysql_real_escape_string($optionValues[$i]),
							mysql_real_escape_string($this->moduleFieldID()),
							mysql_real_escape_string($optionIDs[$i]));
					$optionsResult = Database::getInstance()->query($optionsQuery, 2, 1);
				}
				catch(MySQLException $e) {
					$optionsQuery = sprintf(
							"INSERT INTO ModuleFieldOption
							(ModuleFieldID, OptionLabel, OptionValue)
							VALUES ('%s', '%s', '%s')",
							mysql_real_escape_string($this->moduleFieldID()),
							mysql_real_escape_string($optionIDs[$i]),
							mysql_real_escape_string($optionValues[$i]));
					$optionsResult = Database::getInstance()->query($optionsQuery);
				}
			}
		}
	}
?>
