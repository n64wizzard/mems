<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Security/Audit.php");
	require_once("lib/Module/ModuleField.php");
	require_once("lib/Form/FormField.php");
	
	/// Passwords can only be created, never modified or viewed
	class PasswordField extends ModuleField{
		static public function type(){ return "Password"; }
		public function toHTML($width, $height, $mutable, $currentValue, $moduleInstanceID){
			$output = "";
			if($mutable)
				$output .= "<input name='" . $this->name() .
					"' id='field_" . $this->moduleFieldID() .
					"' type='password' value='' style='width:" . $width .
					"px;' class='ValidateField' />";
			else
				$output .= StaticField::html($width, $height, $mutable, "Hidden");

			return $output;
		}

		/// @return The empty string: we do not want people to try and filter by passwords
		public function filterHTML($filterValues, $idPrefix){
			return "";
		}

		/// @return The empty string: we do not want people to try and filter by passwords
		public function filterSQL($filterValues){
			return "true";
		}

		/// @return The hashed version of $newValue
		public function saveToDB($newValue, $moduleInstanceID){
			// Return hashed form of the password, only if we aren't already dealing with a password
			return strlen($newValue) <= 30 ? Utils::hashPassword($newValue) : $newValue;
		}
	}
?>
