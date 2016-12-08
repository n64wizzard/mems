<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/fieldtypes/FieldTypes.php");
	require_once("lib/Utils.php");

	/// Factory-type class that is in charge of creating new module fields
	final class FieldFactory{
		static private $moduleFields_;

		/// Creates a ModuleField object of the appropriate type (Descriptor design-methodology)
		///	 If one already exists, just return it
		static public function createModuleField($moduleFieldID){
			if(!isset(self::$moduleFields_))
				self::$moduleFields_ = array();
			if(array_key_exists($moduleFieldID, self::$moduleFields_) !== false)
				return self::$moduleFields_[$moduleFieldID];

			$moduleFieldTypeQuery = sprintf(
				"SELECT Type
				FROM ModuleField
				WHERE ModuleFieldID='%s'",
				mysql_real_escape_string($moduleFieldID));
			$type = Database::getInstance()->query($moduleFieldTypeQuery, 1, 1)->fetch_object()->Type;

			$newModuleField = NULL;
			$fieldTypes = Utils::getSubclassesOf("ModuleField");
			
			foreach($fieldTypes as $fieldType)
				if($type == $fieldType::type()){
					$moduleFieldQuery = sprintf(
						"SELECT *
						FROM ModuleField
						WHERE ModuleFieldID='%s'",
						mysql_real_escape_string($moduleFieldID));
					$moduleFieldObj = Database::getInstance()->query($moduleFieldQuery, 1, 1)->fetch_object();

					$newModuleField =  new $fieldType($moduleFieldObj->Name, $moduleFieldObj->Label, $moduleFieldObj->Type,
							$moduleFieldObj->Description, $moduleFieldObj->Unique,
							$moduleFieldObj->Regex, $moduleFieldObj->DefaultValue, $fieldType::initOptions($moduleFieldID),
							$moduleFieldObj->Hidden, $moduleFieldID, $moduleFieldObj->ModuleID);
				}

			if(isset($newModuleField)){
				self::$moduleFields_[$moduleFieldID] = $newModuleField;
				return $newModuleField;
			}
			
			throw new InvalidArgumentException("ModuleField Type not found: $type");
		}
	}
?>
