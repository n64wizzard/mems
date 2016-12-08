<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Security/Audit.php");
	require_once("lib/Module/ModuleField.php");
	require_once("lib/Form/FormField.php");
	
    class TextAreaField extends ModuleField{
		static public function type(){ return "TextArea"; }
		public function toHTML($width, $height, $mutable, $currentValue, $moduleInstanceID){
			$output = "";
			if($mutable)
				$output .= "<textarea name='" . $this->name() .
					"' id='field_" . $this->moduleFieldID() .
					"' style='width:" . $width .
					"px; height:" . $height .
					"px;' class='ValidateField' >" . $currentValue . "</textarea>";
			else
				$output .= StaticField::html($width, $height, $currentValue);

			return $output;
		}
	}
?>
