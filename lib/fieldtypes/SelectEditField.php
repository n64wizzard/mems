<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Security/Audit.php");
	require_once("lib/Module/ModuleField.php");
	require_once("lib/Form/FormField.php");

	/// Drop-down box with user-specified entries
	/// Has the same functionality as the Select field in terms of filtering
	/// Pre-populated options will be pulled from previous DB entries
	class SelectEditField extends ModuleField{
		static public function type(){ return "SelectEdit"; }
		protected $MULTIPLE = false;
		public function toHTML($width, $height, $mutable, $currentValue, $moduleInstanceID){
			$output = "";
			$currentOptions = explode("##", $currentValue);
			$multiple = $this->MULTIPLE ? "multiple" : "";

			$iniArray = Utils::iniSettings();
			
			// Get previously used entries from the database
			$optionsQuery = sprintf(
				"SELECT DISTINCT AES_DECRYPT(MFI.Value, '%s') AS Value
				FROM (SELECT * FROM ModuleField WHERE ModuleFieldID='{$this->moduleFieldID()}') AS MF
					JOIN ModuleFieldInstance AS MFI
						ON MF.ModuleFieldID=MFI.ModuleFieldID",
				mysql_real_escape_string($iniArray["passCode"]));
			$optionsResult = Database::getInstance()->query($optionsQuery);
			
			if(!$mutable && !$this->MULTIPLE)
				$output .= StaticField::html($width, $height, $currentValue);
			else{
				$disabled = $mutable ? "" : " disabled='disabled' ";
				$output .= "<select name='" . $this->name() .
					"' id='field_" . $this->moduleFieldID() .
					"' style='width:" . $width .
					"px; height:" . $height .
					"px;' class='ValidateSelect' {$multiple} {$disabled}>";

				$allBlank = true;
				while($optionObj = $optionsResult->fetch_object()){
					$selected = $currentValue == $optionObj->Value || in_array($optionObj->Value, $currentOptions) ? "SELECTED" : "";

					if($mutable || $selected != "")
						$output .= "<option value='{$optionObj->Value}' {$selected}>{$optionObj->Value}</option>";
					if($allBlank && $optionObj->Value != "")
						$allBlank = !$allBlank;
				}

				$output .= <<<EOD
</select>
<script type="text/javascript">
	$("#field_" + {$this->moduleFieldID()} + "").jec();
</script>
EOD;
			}

			return $output;
		}
		public function filterHTML($filterValues, $idPrefix){
			$output = "";
			$iniArray = Utils::iniSettings();
			$optionsQuery = sprintf(
				"SELECT DISTINCT AES_DECRYPT(MFI.Value, '%s')
				FROM (SELECT * FROM ModuleField WHERE ModuleFieldID='{$this->moduleFieldID()}') AS MF
					JOIN ModuleFieldInstance AS MFI
						ON MF.ModuleFieldID=MFI.ModuleFieldID",
				mysql_real_escape_string($iniArray["passCode"]));
			$optionsResult = Database::getInstance()->query($optionsQuery);
			
			// Should be essentially the same drop-down as the toHTML()...
			// TODO: Try and extract/moularize the following few lines with the toHTML()
			$output .= "<select name='{$idPrefix}value'>";
			while($optionObj = $optionsResult->fetch_object()){
				$selected = isset($filterValues["value"]) && $filterValues["value"] == $optionObj->Value ? "SELECTED" : "";
				$output .= "<option value='{$optionObj->Value}' {$selected}>{$optionObj->Value}</option>";
			}
			$output .= "</select>";
			return $output;
		}
	}
?>
