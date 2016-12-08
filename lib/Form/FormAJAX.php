<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Database.php");
	require_once("lib/module/FieldFactory.php");
	require_once("lib/module/Moduleinstance.php");
	
	/// Function called from AJAX to check for field value appropriateness
	/// @return Empty string if success, otherwise an error message
	function checkFieldValue($moduleFieldID, $value){
		try{
			$formField = FieldFactory::createModuleField($moduleFieldID);
			return $formField->validate($value);
		}
		catch(Exception $e){
			Audit::logError($e);
			return "An error has occurred while processing your request.";
		}
	}

	/// @param $ids The ids that correspond to the values in $values
	/// @param $values An array of values to submit
	/// @return String with "Success" upon success, otherwise an imploded array of errors
	function submitForm($formID, $moduleInstanceID, $ids, $values){
		// TODO: Associative array: FieldID => Error Text
		//	Any general error text will be stored in first entry
		$errorResults = array(-1=>"");
		for($counter = 0; $counter < count($ids); $counter++){
			$checkResult = checkFieldValue($ids[$counter], $values[$counter]);
			if($checkResult != "")
				$errorResults[ $ids[$counter] ] = $ids[$counter];
		}
		if(count($errorResults) != 1)
			return "Field validation failed" . implode("##", $errorResults);

		$fieldsToInsert = array();
		for($counter = 0; $counter < count($ids); $counter++)
			$fieldsToInsert[ $ids[$counter] ] = $values[$counter] == null ? "" : $values[$counter];
			
		if(!isset($moduleInstanceID)){	// Creating a new instance...
			$moduleQuery = sprintf(
				"SELECT ModuleID
				FROM Form JOIN Page ON Form.PageName=Page.PageName
				WHERE FormID='%s'",
				mysql_real_escape_string($formID));
			$moduleObj = Database::getInstance()->query($moduleQuery, 1, 1)->fetch_object();

			// Since we have to create both a module instance and module field instances,
			//  we want to make it an all-or-nothing kind of deal
			Database::getInstance()->startTransaction();
			try{ $moduleInstance = ModuleInstance::newModuleInstance($moduleObj->ModuleID); }
			catch(PrivilegeException $e){
				Audit::logError($e);
				Database::getInstance()->rollback();
				return $e->getMessage() . "##";
			}

			$moduleInstance->moduleFieldValuesAre($fieldsToInsert);

			try{ $moduleInstance->saveToDB(); }
			catch(MySQLException $e){
				Audit::logError($e);
				Database::getInstance()->rollback();
				return "Error saving fields to DB##";
			}
			catch(UniqueFieldException $e){ return $e->getMessage(); }
			
			Database::getInstance()->commit();
        }
        else{	// Updating an existing instance...
			$moduleInstance = ModuleInstance::createModuleInstance($moduleInstanceID);
			$moduleInstance->moduleFieldValuesAre($fieldsToInsert);
			Database::getInstance()->startTransaction();
			try{ $moduleInstance->saveToDB(); }
			catch(MySQLException $e){
				Audit::logError($e);
				Database::getInstance()->rollback();
				return "Error saving fields to DB##";
			}
			catch(UniqueFieldException $e){ return $e->getMessage(); }
			Database::getInstance()->commit();
        }
		return "Success";
	}

	if(isset($_POST['command'])){
		$command = $_POST['command'];
		if($command == 'checkFieldValue'){
			$value = isset($_POST["value"]) && $_POST["value"] != "" ? $_POST["value"] : NULL;
			$moduleFieldID = Utils::getPostInt("moduleFieldID");
			print(checkFieldValue($moduleFieldID, $value));
		}
		elseif($command == 'submit'){
			$formID = Utils::getPostInt("formID");
			$ids = isset($_POST["ids"]) ? $_POST["ids"] : NULL;
			$values = isset($_POST["values"]) ? $_POST["values"] : NULL;
			$moduleInstanceID = Utils::getPostInt("moduleInstanceID");
			print(submitForm($formID, $moduleInstanceID, json_decode($ids), json_decode($values)));
		}
	}
?>
