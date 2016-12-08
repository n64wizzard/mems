<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Security/Audit.php");
	require_once("lib/Module/ModuleField.php");

	/// This field is similar to the PageLink, but instead it just displays the the
	///  foreign field in a non-mutable form.
	// TODO: This field is not complete/has not yet been tested
	/// options: PageLinkID(ModuleFieldID for the PageLink field), ForeignModuleFieldID
	class TextField extends ModuleField{
		static public function type(){ return "Foreign"; }
		public function toHTML($width, $height, $mutable, $currentValue, $moduleInstanceID){
			$output = "";
			$pageLinkIDOption = $this->option("PageLinkID");
			$foreignModuleFieldIDOption = $this->option("ForeignModuleFieldID");

			if(isset($moduleInstanceID) && isset($pageLinkIDOption) && isset($foreignModuleFieldIDOption)){
				$moduleInstance = ModuleInstance::createModuleInstance($moduleInstanceID);
				$pageLinkField = $moduleInstance->moduleFieldInstance($pageLinkIDOption->optionValue());
				$foreignModuleInstance = ModuleInstance::createModuleInstance($pageLinkField->currentValue());
				$foreignModuleField = $foreignModuleInstance->moduleFieldInstance($foreignModuleFieldIDOption->optionValue());
				
				$currentValue = $foreignModuleField->currentValue();
			}

			$output .= StaticField::html($width, $height, $currentValue);

			return $output;
		}
		public function showOptions() {
			$output = "";
			$pageLinkIDOption = $this->option("PageLinkID");
			$foreignModuleFieldIDOption = $this->option("ForeignModuleFieldID");
			
			$output .= <<<EOD
<div class='FieldLabel' style='padding-right:5px' ><label for='field_{$this->moduleFieldID()}_option1'>Page Link Field: </label></div>
<div class='FieldContent' style='padding-right:5px' ><select name='PageLinkID' id='field_{$this->moduleFieldID()}_option1'>
EOD;
			$pageLinkQuery = sprintf(
				"SELECT ModuleFieldID, Name
				FROM ModuleField
				WHERE Type='PageLink' AND ModuleID='%s'",
				mysql_real_escape_string($this->moduleID()));
			$pageLinkResult = Database::getInstance()->query($pageLinkQuery);
			while($roleFieldObj = $pageLinkResult->fetch_object()) {
				$output .= "<option value='{$roleFieldObj->ModuleFieldID}' ";
				if(isset($pageLinkIDOption) && $pageLinkIDOption->optionValue() == $roleFieldObj->ModuleFieldID)
					$output .= "selected='selected' ";
				$output .= ">{$roleFieldObj->Name}</option>";
			}

			$output .= <<<EOD
</select></div>
EOD;

			// Look-up the value stored elsewhere on this page in the ModuleID option for the pagelinkID
			// Then create a list of its fields here using an AJAX function
$output .= <<<EOD
<div class='FieldLabel' style='padding-right:5px' ><label for='field_{$this->moduleFieldID()}_option2'>Foreign Field: </label></div>
<div class='FieldContent' style='padding-right:5px' ><select name='ForeignModuleFieldID' id='field_{$this->moduleFieldID()}_option2'>
</select></div>
<script type="text/javascript">
	$(document).ready(function(){
		foreignModuleFieldOptions();
	});
	$("#field_{$this->moduleFieldID()}_option1").change(function(){
		foreignModuleFieldOptions();
	});
	function foreignModuleFieldOptions(){
		$('#field_{$this->moduleFieldID()}_option2').change(function(){
			$.ajax({
				type: "POST",
				url: "lib/editors/ModuleCreatorAJAX.php",
				data: {
					"command": "moduleFields",
					"moduleID": $('#field_{$this->moduleFieldID()}_option1').val()
				},
				success: function(msg){
					if(msg.substr(0, 6) == "Error:")
						alert(msg);
					else{
						$('#field_{$this->moduleFieldID()}_option2').empty();
						$('#field_{$this->moduleFieldID()}_option2').append(msg);
					}
				}
			});
		});
	}
</script>
EOD;
			return $output;
		}
	}
?>
