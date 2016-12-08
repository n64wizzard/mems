<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Security/Audit.php");
	require_once("lib/Module/ModuleField.php");

	class TextField extends ModuleField{
		static public function type(){ return "Text"; }
		public function toHTML($width, $height, $mutable, $currentValue, $moduleInstanceID){
			$output = "";
			if($mutable)
				$output .= "<input name='" . $this->name() .
					"' id='field_" . $this->moduleFieldID() .
					"' type='text' value='" . $currentValue .
					"' style='width:" . $width .
					"px;' class='ValidateField' />";
			else
				$output .= StaticField::html($width, $height, $currentValue);

			return $output;
		}
	}
?>
