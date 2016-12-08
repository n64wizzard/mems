<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("lib/Position.php");
	require_once("lib/Module/ModuleInstance.php");
	require_once("lib/Form/Form.php");
	require_once("lib/Form/FormField.php");

	class ModuleCreatorForm extends Form{
		protected $formName_, $moduleID_, $moduleName_, $moduleFieldID_, $moduleCreatorFieldID_;

		public function __construct($moduleCreatorID, $moduleID, $moduleName, $moduleFieldID=NULL){
			parent::__construct($moduleCreatorID);
			$this->moduleID_ = $moduleID;
			$this->moduleName_ = $moduleName;
			$this->moduleCreatorFieldID_ = $this->moduleFieldID();
			$this->moduleFieldID_ = $moduleFieldID;
			
			if($moduleFieldID == NULL) {
				$this->formName_ = "New Field";
			}

			foreach($this->formFields_ as $formField) {
				$moduleFieldInstance = $formField->moduleFieldInstance();
				$moduleField = $moduleFieldInstance->moduleField();

				if($moduleFieldID) {
					try {
						$valueQuery = sprintf(
							"SELECT %s
							FROM ModuleField
							WHERE ModuleFieldID='%s'",
							mysql_real_escape_string($moduleField->label()), $moduleFieldID);
						$valueArray = Database::getInstance()->query($valueQuery, 1, 1)->fetch_array();

						$value = $valueArray[0];
						$moduleFieldInstance->currentValueIs($value);

						if($moduleField->label() == "Label") {
							$this->formName_ = $value;
						}
					}
					catch(MySQLException $e) {

					}
				}

				if($moduleField->label() == "Type") {
					$types = Utils::getSubclassesOf("ModuleField");
					for ($i = 0; $i < count($types); $i++) {
						$types[$i] = $types[$i]::type();
					}
					$moduleField->optionsIs($types, true);
				}
			}
		}

		public function formName(){ return $this->formName_; }
		public function formID(){ return $this->formID_; }
		public function moduleID(){ return $this->moduleID_; }
		public function moduleName(){ return $this->moduleName_; }
		public function moduleFieldID(){ return $this->moduleFieldID_; }

		/// @return A string containing the HTML code of this form and all of its fields
		public function toHTML($editMode=false){
			$formID = $this->formID();
			$moduleID = $this->moduleID();
			$moduleName = $this->moduleName();
			$moduleFieldID = $this->moduleFieldID();
			$totalHeight = $this->totalFormHeight(20);

			$output = "";
			$output .= "<form onsubmit=\"moduleCreatorFormSubmit('$formID', '$moduleID', '$moduleName', '$moduleFieldID'); return false;\" method='post' class='moduleCreatorForm' id='form_{$formID}_moduleField_{$moduleFieldID}'>\n";
			$output .= "<div id='formContainer' style='height:{$totalHeight};' >";

			// Iterate over fields to create the HTML form
			foreach($this->formFields_ as $formField){
                $unModifiedOutput = $formField->toHTML() . "\n";
				$modifiedOutput = str_replace("id='field_", "id='modulefield_{$moduleFieldID}_field_", $unModifiedOutput);
				if($modifiedOutput === $unModifiedOutput) 
					$modifiedOutput = str_replace("id=\"field_", "id=\"modulefield_{$moduleFieldID}_field_", $unModifiedOutput);

				$output .= $modifiedOutput;
			}

			$output .= <<<EOD
<div style='clear:both;'></div>
</div>
<div style='clear:both;'>
<div id="form_result_{$formID}" style="display:none"></div>
</div>
</form>
EOD;
			
			if($moduleFieldID) {
				$output .= "<div id='field_options_" . $moduleFieldID . "' >";
				$output .= FieldFactory::createModuleField($moduleFieldID)->showOptions();
				$output .= "</div><div style='clear:both;'></div>";
			}

			if(Security::privilege(new ModulePrivilege("DeleteField", $this->moduleID())))
				$output .= "<div id='deleteField_{$this->moduleFieldID()}' onclick='if(confirm(\"Delete field?\")){deleteModuleField({$this->moduleFieldID()})};' class='DeleteModuleField'>Delete Field</div>";

			$output .= <<<EOD
<script type="text/javascript">
	$("#deleteField_{$this->moduleFieldID()}").button();
	jQuery.data(document.body, "moduleInstanceID", '{$this->moduleInstance()->moduleInstanceID()}');
	jQuery.data(document.body, "moduleID", '{$moduleID}');
	jQuery.data(document.body, "moduleName", '{$moduleName}');
</script>
EOD;
			return $output;
		}
	}
?>
