<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Security/Audit.php");
	require_once("lib/Module/ModuleField.php");
	require_once("lib/Form/FormField.php");
	
    // PENDING: Decide whether or not to include entries with undefined values
    // A CheckBoxField is a check box
    class CheckBoxField extends ModuleField{
		static public function type(){ return "CheckBox"; }
		public function toHTML($width, $height, $mutable, $currentValue, $moduleInstanceID){
			$result = "";
			$disabled = "";
			if(!$mutable)
				$disabled = "disabled";

			$result .= "<input class='ValidateField' name='" . $this->name() .
				"' id='field_" . $this->moduleFieldID() .
				"' type='checkbox" . ($currentValue == "true" ? "' checked='checked" : "") .
				"' $disabled />";

			return $result;
		}
        /// @return The HTML code used to display this field as a filter
		public function filterHTML($filterValues, $idPrefix){
			//{$this->moduleFieldID()}
			$currValue = isset($filterValues["value"]) ? $filterValues["value"] : "";
            $output = "";
			$output .= "<input name='" . $idPrefix . "value' type='checkbox'" . ($currValue == "true" ? " checked='checked'" : "") . " />\n";
			return $output;
		}
        /// @return The subset of a WHERE clause to enforce this filter
		public function filterSQL($filterValues){
            $output = "";
			$iniArray = Utils::iniSettings();
            if(array_key_exists('value', $filterValues)){
                $output = sprintf(
                    "(MFI.ModuleFieldID='%s' AND AES_DECRYPT(MFI.Value, '%s')='true')",
                    mysql_real_escape_string($this->moduleFieldID()),
					mysql_real_escape_string($iniArray["passCode"]));
            }
            else{
                $output = sprintf(
                    "(MFI.ModuleFieldID='%s' AND AES_DECRYPT(MFI.Value, '%s')<>'true')",
                    mysql_real_escape_string($this->moduleFieldID()),
					mysql_real_escape_string($iniArray["passCode"]));
            }
            return $output;
		}
    }
?>
