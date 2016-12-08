<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Security/Audit.php");
	require_once("lib/fieldtypes/SelectField.php");
	require_once("lib/Form/FormField.php");

	/// This field provides a way to make logical connections between modules
	/// Options: ModuleID, PageName, ModuleFieldID
	// TODO: Over-ride initOptions, probably can use the uniqueList function heavily
	class PageInstanceLink extends ComboBoxField{
		static public function type(){ return "PageLink"; }
		
		/// @param $miid If included, will only return the unique value for that miid
		/// @return An array of MIID => Unique Value
		static public function uniqueList($moduleID, $miid=NULL){
			$selectOne = "";
			if(isset($miid))
				$selectOne = sprintf("AND ModuleInstanceID='%s'",
					mysql_real_escape_string($miid));
			$iniArray = Utils::iniSettings();
			// Look-up list of module instances
			$miidQuery = sprintf(
				"SELECT AES_DECRYPT(MFI.Value, '%s') AS UniqueValue,MFI.ModuleInstanceID
				FROM (SELECT * FROM ModuleField WHERE ModuleField.Unique=b'1') AS MF
					JOIN ModuleFieldInstance AS MFI
						ON MF.ModuleFieldID=MFI.ModuleFieldID
					JOIN (SELECT * FROM ModuleInstance WHERE ModuleID='%s' %s) AS M
						ON MFI.ModuleInstanceID=M.ModuleInstanceID",
				mysql_real_escape_string($iniArray["passCode"]),
				mysql_real_escape_string($moduleID),
				$selectOne);
			$miidResult = Database::getInstance()->query($miidQuery);

			$resultArray = array();
			while($miidObj = $miidResult->fetch_object())
				$resultArray[$miidObj->ModuleInstanceID] = $miidObj->UniqueValue;

			return $resultArray;
		}

		public function toHTML($width, $height, $mutable, $currentValue, $moduleInstanceID){
			$currentOptions = explode("##", $currentValue);
			$output = "";
			$moduleID = $this->option("ModuleID")->optionValue();

			if($mutable){
				$output .= "<select name='" . $this->name() .
					"' class='ValidateSelect' id='field_" . $this->moduleFieldID() .
					"' style='width:" . $width .
					"px; height:" . $height .
					"px;' multiple >\n";

				$uniqueArray = self::uniqueList($moduleID);
				foreach($uniqueArray as $unqiueMIID => $uniqueValue){
					$selected = in_array($unqiueMIID, $currentOptions) !== false ? "SELECTED" : "";
					$output .= "<option value='{$unqiueMIID}' {$selected}>{$uniqueValue}</option>\n";
				}
				$output .= "</select>\n";
			}
			else{
				$uniqueArray = self::uniqueList($moduleID);
				$pageName = $this->option("PageName")->optionValue();

				foreach($uniqueArray as $moduleInstanceID => $uniqueValue){
					if(in_array($moduleInstanceID, $currentOptions) !== false){
						$moduleInstance = ModuleInstance::createModuleInstance($moduleInstanceID);

						$moduleFieldIDOption = $this->option("ModuleFieldID");
						if(!isset($moduleFieldIDOption) || $moduleFieldIDOption->optionValue() == ""){
							$destination = "index.php?Page=" . $pageName . "&MIID=" . $currentValue;
							$output .= self::link ($width, $height, $destination, $uniqueValue);
						}
						else{
							// Otherwise, look-up the value we need
							$moduleFieldID = $moduleFieldIDOption->optionValue();
							$output .= StaticField::html($width, $height, $moduleInstance->moduleFieldInstance($moduleFieldID)->currentValue());
						}
					}
				}
			}

			return $output;
		}
		/// @return The HTML code used to display this field as a filter
		public function filterHTML($filterValues, $idPrefix){
			$output = "";
			// About the same as the toHTML()...
			$output .= "<select name='{$idPrefix}value'>\n";

			// Get ModuleID from option
			$moduleID = $this->option("ModuleID")->optionValue();
			$currentValue = isset($filterValues["value"]) ? $filterValues["value"] : "";

			$moduleIDs = self::uniqueList($moduleID);

			foreach($moduleIDs as $moduleID => $moduleName){
				$selected = $currentValue == $moduleID ? "SELECTED" : "";
				$output .= "<option value='$moduleID' {$selected}>$moduleName</option>\n";
			}
			$output .= "</select>\n";

			return $output;
		}
		static public function link($width, $height, $destination, $text){
			$output = "<div syle='float:right; width:{$width}px; height:{$height}px;'><a href=\"{$destination}\" >" . $text . "</a></div>";
			return $output;
		}
		/// Check that the stored MIID is valid (ie. exists)
		public function validate($value){
			$moduleID = $this->option("ModuleID")->optionValue();
			$uniqueArray = self::uniqueList($moduleID, $value);
			if(count($uniqueArray) > 0)
				return "";
			else
				return "Module Instance '{$value}' not found";
		}

		public function showOptions() {
			$output = "";
			$output .= <<<EOD
<div class='FieldLabel' style='padding-right:5px' ><label for='field_{$this->moduleFieldID()}_option1'>Page Name: </label></div>
<div class='FieldContent' style='padding-right:5px' ><select name='PageName' id='field_{$this->moduleFieldID()}_option1'>
EOD;
			$pageQuery = sprintf(
				"SELECT PageName,PageTitle
				FROM Page");
			$pageResult = Database::getInstance()->query($pageQuery);
			$pageNameOption = $this->option("PageName");
			while($pageObj = $pageResult->fetch_object()) {
				$output .= "<option value='{$pageObj->PageName}' ";
				if(isset($pageNameOption) && $pageNameOption->optionValue() == $pageObj->PageName)
					$output .= "selected='selected' ";
				$output .= ">{$pageObj->PageTitle}</option>";
			}

			$output .= <<<EOD
</select></div>
<div class='FieldLabel' style='padding-right:5px' ><label for='field_{$this->moduleFieldID()}_option2'>Module: </label></div>
<div class='FieldContent' style='padding-right:5px' ><select name='ModuleID' id='field_{$this->moduleFieldID()}_option2'>
EOD;
			$moduleQuery = sprintf(
				"SELECT ModuleID,Name
				FROM Module
				WHERE Hidden='0'");
			$moduleResult = Database::getInstance()->query($moduleQuery);
			$moduleIDOption = $this->option("ModuleID");
			while($moduleObj = $moduleResult->fetch_object()) {
				$output .= "<option value='{$moduleObj->ModuleID}' ";
				if(isset($moduleIDOption) && $moduleIDOption->optionValue() == $moduleObj->ModuleID)
					$output .= "selected='selected' ";
				$output .= ">{$moduleObj->Name}</option>";
			}

			$output .= <<<EOD
</select></div>
<div class='FieldLabel' style='padding-right:5px' ><label for='field_{$this->moduleFieldID()}_option3'>Module Field: </label></div>
<div class='FieldContent' style='padding-right:5px' ><select name='ModuleFieldID' id='field_{$this->moduleFieldID()}_option3'>
EOD;
			$moduleFieldQuery = sprintf(
				"SELECT ModuleFieldID,Name
				FROM ModuleField
				WHERE Hidden='0' AND ModuleID='%s'",
				mysql_real_escape_string(isset($moduleIDOption) ? $moduleIDOption->optionValue() : ""));
			$moduleFieldResult = Database::getInstance()->query($moduleFieldQuery);
			$moduleFieldIDOption = $this->option("ModuleFieldID");

			// TODO: extract these part to its own function, so we don't do the same thing both here and in ModuleCreatorAJAX
			while($moduleFieldObj = $moduleFieldResult->fetch_object()) {
				$output .= "<option value='{$moduleFieldObj->ModuleFieldID}' ";
				if(isset($moduleFieldIDOption) && $moduleFieldIDOption->optionValue() == $moduleFieldObj->ModuleFieldID)
					$output .= "selected='selected' ";
				$output .= ">{$moduleFieldObj->Name}</option>";
			}
			$output .= <<<EOD
</select></div><div style='clear:both;'></div>
<script type="text/javascript">
	$('#field_{$this->moduleFieldID()}_option2').change(function(){
		$.ajax({
			type: "POST",
			url: "lib/editors/ModuleCreatorAJAX.php",
			data: {
				"command": "moduleFields",
				"moduleID": $(this).val()
			},
			success: function(msg){
				if(msg.substr(0, 6) == "Error:")
					alert(msg);
				else{
					$('#field_{$this->moduleFieldID()}_option3').empty();
					$('#field_{$this->moduleFieldID()}_option3').append(msg);
				}
			}
		});
	});
</script>
EOD;

			return $output;
		}
		// TODO: This should not be needed, except because SelectField messes everything up
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
