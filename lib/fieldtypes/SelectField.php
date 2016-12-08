<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Security/Audit.php");
	require_once("lib/Module/ModuleField.php");
	require_once("lib/Form/FormField.php");
	
	/// The Select field, aka, the drop-down menu, which only allows a single selection
	///  Field Options are the entries in the list
	class SelectField extends ModuleField{
		static public function type(){ return "Select"; }
		protected $MULTIPLE = false;	/// Only a single option can be selected at a time
		public function toHTML($width, $height, $mutable, $currentValue, $moduleInstanceID){
			$output = "";
			$currentOptions = explode("##", $currentValue);
			$multiple = $this->MULTIPLE ? "multiple" : "";

			// If we can't edit it and we have only a single value, just print out the associated option label
			if(!$mutable && !$this->MULTIPLE){
				foreach($this->options_ as $option)
					if($currentValue == $option->optionValue())
						$output .= StaticField::html($width, $height, $option->optionLabel());
			}
			else{
				$disabled = $mutable ? "" : " disabled='disabled' ";
				$output .= "<select name='" . $this->name() .
					"' id='field_" . $this->moduleFieldID() .
					"' style='width:" . $width .
					"px; height:" . $height .
					"px;' class='ValidateSelect' {$multiple} {$disabled}>\n";
					
				foreach($this->options_ as $option){
					$selected = in_array($option->optionValue(), $currentOptions) !== false ? "SELECTED" : "";

					if($mutable || $selected != "")
						$output .= "<option value='{$option->optionValue()}' {$selected}>{$option->optionLabel()}</option>\n";
				}
				$output .= "</select>\n";
			}

			return $output;
		}

		/// @return A comma-separated list of the labels for the current option values
		public function filterHTML($filterValues, $idPrefix){
			$output = "";
			// Should be essentially the same drop-down as the toHTML()...
			$output .= "<select name='{$idPrefix}value'>\n";
				foreach($this->options_ as $option){
					$selected = isset($filterValues["value"]) && $filterValues["value"] == $option->optionValue() ? "SELECTED" : "";
					$output .= "<option value='{$option->optionValue()}' {$selected}>{$option->optionLabel()}</option>\n";
				}
			$output .= "</select>\n";
			return $output;
		}
		public function validate($value){
			foreach($this->options_ as $option)
				if($option->optionValue() == $value)
					return "";

			return "Option value '{$value}' not found";
		}

		public function showOptions() {
				$output = "";

				$output .= <<<EOD
<div class='FieldLabel' ><label for='field_{$this->moduleFieldID()}_option1'>Options</label></div>
<div class='FieldContent' style='padding-right:5px' ><select name='Options' id='field_{$this->moduleFieldID()}_option1'>
EOD;

				foreach($this->options_ as $option)
					$output .= "<option value='{$option->optionValue()}'>{$option->optionLabel()}</option>\n";

				$output .= <<<EOD
</select></div>\n
<input onclick='addOption("{$this->moduleFieldID()}")' name='addOption' type='Submit' value='Add another new option' />
<input onclick='deleteOption("{$this->moduleFieldID()}")' name='deleteOption' type='Submit' value='Delete this option' /><br/>
EOD;

				return $output;
		}

		// TODO: Modify the showOptions() function so this specialized version is no longer needed
		//  Simply create a "create new" dialog, which adds hidden input fields, and visible text/delete button
		public function saveOptions($optionIDs, $optionValues) {
			for($i = 0; $i < count($optionIDs); $i++) {
				if($optionIDs[$i] == 'OptionLabel' && $optionIDs[$i + 1] == 'OptionValue') {
					$optionsQuery = sprintf(
							"SELECT *
							FROM ModuleFieldOption
							WHERE ModuleFieldID='%s'
							AND OptionLabel='%s'",
							mysql_real_escape_string($this->moduleFieldID()),
							mysql_real_escape_string($optionValues[$i]));
					$optionsResult = Database::getInstance()->query($optionsQuery)->fetch_object();
					if($optionsResult) {
						$optionsQuery = sprintf(
								"UPDATE ModuleFieldOption
								SET OptionValue='%s'
								WHERE ModuleFieldID='%s'
								AND OptionLabel='%s'",
								mysql_real_escape_string($optionValues[$i + 1]),
								mysql_real_escape_string($this->moduleFieldID()),
								mysql_real_escape_string($optionValues[$i]));
						$optionsResult = Database::getInstance()->query($optionsQuery, 2, 1);
					}
					else {
						$optionsQuery = sprintf(
								"INSERT INTO ModuleFieldOption
								(ModuleFieldID, OptionLabel, OptionValue)
								VALUES ('%s', '%s', '%s')",
								mysql_real_escape_string($this->moduleFieldID()),
								mysql_real_escape_string($optionValues[$i]),
								mysql_real_escape_string($optionValues[$i + 1]));
						$optionsResult = Database::getInstance()->query($optionsQuery);
					}
				}
			}
		}
	}

	// Select field where multiple options can be selected
	class ComboBoxField extends SelectField{
		static public function type(){ return "ComboBox"; }
		protected $MULTIPLE = true;

		// Check that all selected items (deliminate by '##') are valid
		public function validate($value){
			$allValues = explode("##", $value);
			foreach($this->options_ as $option){
				$key = array_search($option->optionValue(), $allValues);
				if($key !== false)
					unset($allValues[$key]);
			}
			if(count($allValues) == 0 || $value == "")
				return "";
			else
				return "Option value '{$value}' not found";
		}
	}
?>
