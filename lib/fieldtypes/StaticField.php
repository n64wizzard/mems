<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Security/Audit.php");
	require_once("lib/Module/ModuleField.php");
	require_once("lib/Form/FormField.php");
	
	/// A StaticField is effectively a ModuleField for which no one has permission to edit
	class StaticField extends ModuleField{
		static public function type(){ return "Static"; }
		
		// We ignore the mutable field, for by definition static fields cannot be edited
		public function toHTML($width, $height, $mutable, $currentValue, $moduleInstanceID){
			return self::html($width, $height, $currentValue);
		}
		
		static public function html($width, $height, $currentValue){
			$output = "<div syle='float:right;width:{$width}px;height:{$height}px;'>" . $currentValue . "</div>";
			return $output;
		}
	}
?>
