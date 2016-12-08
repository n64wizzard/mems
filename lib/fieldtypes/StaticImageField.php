<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Security/Audit.php");
	require_once("lib/Module/ModuleField.php");
	require_once("lib/Form/FormField.php");

	/// A StaticImageField is effectively an uneditable image
	class StaticImageField extends ModuleField{
		static public function type(){ return "StaticImage"; }
		
		// We ignore the mutable field, for by definition static fields cannot be edited
		public function toHTML($width, $height, $mutable, $currentValue, $moduleInstanceID){
			$output = "<img src='$currentValue' syle='float:right;' width='{$width}px' height='{$height}px' />";
			return $output;
		}
	}
?>
