<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Database.php");
	require_once("lib/Module/FieldFactory.php");
	require_once("lib/Module/Moduleinstance.php");
	require_once("lib/editors/ModuleCreatorForm.php");
	require_once("lib/Form/FormAJAX.php");

	/// @return HTML for select options consisting of all non-hidden modulefields for this module
	function moduleFieldOptions($moduleID){
		$moduleFieldQuery = sprintf(
			"SELECT ModuleFieldID,Name
			FROM ModuleField
			WHERE Hidden='0' AND ModuleID='%s'",
			mysql_real_escape_string($moduleID));
		try{ $moduleFieldResult = Database::getInstance()->query($moduleFieldQuery); }
		catch(MySQLException $e){
			Audit::logError($e);
			return "Error: Database error";
		}

		$output = "";
		while($moduleFieldObj = $moduleFieldResult->fetch_object())
			$output .= "<option value='{$moduleFieldObj->ModuleFieldID}'>{$moduleFieldObj->Name}</option>";

		return $output;
	}

	function deleteOption($moduleFieldID, $label, $value){
		$optionQuery = sprintf(
			"DELETE FROM ModuleFieldOption
			WHERE ModuleFieldID='%s'
			AND OptionLabel='%s'
			AND OptionValue='%s'",
			mysql_real_escape_string($moduleFieldID),
			mysql_real_escape_string($label),
			mysql_real_escape_string($value));
		$optionResult = Database::getInstance()->query($optionQuery, 2, 1);
		return true;
	}

	function newModuleField($moduleCreatorID, $editModuleID, $moduleName){
		$moduleFieldQuery = sprintf(
					"INSERT INTO ModuleField
					(ModuleID, Type) VALUES ('%s', 'Text')",
					mysql_real_escape_string($editModuleID));
		$moduleFieldResult = Database::getInstance()->query($moduleFieldQuery, 2, 1);

		$moduleFieldID = Database::getInstance()->insertID();
		
		$newModuleCreatorForm = new ModuleCreatorForm($moduleCreatorID, $editModuleID, $moduleName, $moduleFieldID);
		$formHTML = $newModuleCreatorForm->toHTML();

		$moduleFieldQuery = sprintf(
					"DELETE FROM ModuleField
					WHERE ModuleFieldID='%s'",
					mysql_real_escape_string($moduleFieldID));
		$moduleFieldResult = Database::getInstance()->query($moduleFieldQuery, 2, 1);

		return $moduleFieldID . "#" . $formHTML;
	}

	function saveModule($moduleName, $create, $moduleID=NULL){
		if(!$moduleName)
			return "Name must be non-empty";

		$moduleQuery = sprintf(
					"SELECT *
					FROM Module
					WHERE Name='%s'",
					mysql_real_escape_string($moduleName));
		$moduleResult = Database::getInstance()->query($moduleQuery)->fetch_object();

		if($moduleResult && $moduleResult->ModuleID != $moduleID)
			return "Module already exists";

		if($create) {
			$moduleCreateQuery = sprintf(
					"INSERT INTO Module
					(`ModuleID`, `Name`, `Removable`, `Hidden`) VALUES
					(NULL, '%s', b'1', b'0')",
					mysql_real_escape_string($moduleName));
			$moduleCreateResult = Database::getInstance()->query($moduleCreateQuery);
			if ($moduleCreateResult)
				return "Failed to create module";
		}
		else {
			$moduleCreateQuery = sprintf(
					"UPDATE Module
					SET Name='%s'
					WHERE ModuleID='%s'",
					mysql_real_escape_string($moduleName),
					mysql_real_escape_string($moduleID));
			$moduleCreateResult = Database::getInstance()->query($moduleCreateQuery);
			if ($moduleCreateResult)
				return "Failed to update module name";
		}

		return false;
	}

	function deleteModuleField($moduleFieldID){
		if(!$moduleFieldID)
			return "Cannot remove null field";

		$moduleFieldQuery = sprintf(
					"DELETE FROM ModuleField
					WHERE ModuleFieldID='%s'",
					mysql_real_escape_string($moduleFieldID));
		$moduleFieldResult = Database::getInstance()->query($moduleFieldQuery);

		return true;
	}

	function deleteModule($moduleID){
		if(!$moduleID)
			return "Cannot remove null module";

		$moduleQuery = sprintf(
					"DELETE FROM Module
					WHERE ModuleID='%s'",
					mysql_real_escape_string($moduleID));
		$moduleResult = Database::getInstance()->query($moduleQuery);

		return true;
	}

	function submitModuleForm($formId, $moduleID, $moduleName, $moduleFieldID, $mcfIDs, $ids, $vals, $optionIDs, $optionValues) {
		//	Any general error text will be stored in first entry
		$errorResults = array(-1=>"");
		for($counter = 0; $counter < count($ids); $counter++){
			if($ids[$counter] != 'Type') {
				$checkResult = checkFieldValue($mcfIDs[$counter], $vals[$counter]);
				if($checkResult != "")
					$errorResults[ $mcfIDs[$counter] ] = $mcfIDs[$counter];
			}
		}
		if(count($errorResults) != 1)
			return "Field validation failed".implode("##", $errorResults);

		try {
			$module = Module::newModule($moduleName);
		}
		catch (MySQLException $e) {
			$module = Module::createModule($moduleID);
		}
		$moduleFieldQuery = sprintf(
			"SELECT *
			FROM ModuleField
			WHERE ModuleFieldID='%s'",
			mysql_real_escape_string($moduleFieldID));
		$moduleFieldResult = Database::getInstance()->query($moduleFieldQuery);

		if($moduleFieldResult->fetch_object()){
			for($i = 0; $i < count($ids); $i++) {
				$moduleFieldQuery = sprintf(
					"UPDATE ModuleField
					SET %s='%s'
					WHERE ModuleFieldID='%s'",
				mysql_real_escape_string($ids[$i]),
				mysql_real_escape_string($vals[$i]),
				mysql_real_escape_string($moduleFieldID));
				Database::getInstance()->query($moduleFieldQuery);
			}
		}
		else if($vals[array_search('Name', $ids)] != NULL &&
				$vals[array_search('Type', $ids)] != NULL  &&
				$vals[array_search('Label', $ids)] != NULL  &&
				$vals[array_search('Regex', $ids)] != NULL) {
			//"INSERT INTO ModuleFieldInstance
			//		(`ModuleFieldID`, `ModuleInstanceID`, `Value`) VALUES
			//		('%s', '%s', '%s')",
			$idList = "(ModuleID, ModuleFieldID";
			$valueList = "('" . $moduleID . "'" . ", '" . $moduleFieldID . "'";
			for($i = 0; $i < count($ids); $i++) {
				$idList .= ", " . mysql_real_escape_string($ids[$i]);
				$valueList .= ", '" . mysql_real_escape_string($vals[$i]) . "'";
			}
			$idList .= ")";
			$valueList .= ")";
			$moduleFieldQuery = sprintf(
				"INSERT INTO ModuleField
				%s VALUES
				%s",
				$idList,
				$valueList);
			Database::getInstance()->query($moduleFieldQuery);
		}

		//$optionsQuery = sprintf(
		//		"DELETE FROM ModuleFieldOption
		//		WHERE ModuleFieldID='%s'",
		//		mysql_real_escape_string($moduleFieldID));
		//$optionsResult = Database::getInstance()->query($optionsQuery);
		FieldFactory::createModuleField($moduleFieldID)->saveOptions($optionIDs, $optionValues);

		return true;
	}

	function reloadForm($formId, $moduleID, $moduleName, $moduleFieldID, $newType) {
		$typeQuery = sprintf(
			"SELECT Type
			FROM ModuleField
			WHERE ModuleFieldID='%s'",
			mysql_real_escape_string($moduleFieldID));
		$typeResult = Database::getInstance()->query($typeQuery)->fetch_object();

		if($typeResult) {
			$type = $typeResult->Type;
			$typeQuery = sprintf(
				"UPDATE ModuleField
				SET Type='%s'
				WHERE ModuleFieldID='%s'",
				mysql_real_escape_string($newType),
				mysql_real_escape_string($moduleFieldID));
			$typeResult = Database::getInstance()->query($typeQuery);

			$newModuleCreatorForm = new ModuleCreatorForm($formId, $moduleID, $moduleName, $moduleFieldID);
			$output = $newModuleCreatorForm->toHTML();

			$typeQuery = sprintf(
				"UPDATE ModuleField
				SET Type='%s'
				WHERE ModuleFieldID='%s'",
				mysql_real_escape_string($type),
				mysql_real_escape_string($moduleFieldID));
			$typeResult = Database::getInstance()->query($typeQuery);
		}
		else {
			$typeQuery = sprintf(
				"INSERT INTO ModuleField
				(ModuleFieldID, ModuleID, Type) VALUES
				('%s', %s, '%s')",
				mysql_real_escape_string($moduleFieldID),
				mysql_real_escape_string($moduleID),
				mysql_real_escape_string($newType));
			$typeResult = Database::getInstance()->query($typeQuery);

			$newModuleCreatorForm = new ModuleCreatorForm($formId, $moduleID, $moduleName, $moduleFieldID);
			$output = $newModuleCreatorForm->toHTML();

			$typeQuery = sprintf(
						"DELETE FROM ModuleField
						WHERE ModuleFieldID='%s'",
						mysql_real_escape_string($moduleFieldID));
			$typeResult = Database::getInstance()->query($typeQuery);
		}
		return $output;
	}

	if(isset($_POST['command'])){
		$command = $_POST['command'];
		if($command == 'submitModule'){
			$formID = isset($_POST["formID"]) && $_POST["formID"] != "" ? $_POST["formID"] : NULL;
			$moduleID = isset($_POST["moduleID"]) ? $_POST["moduleID"] : NULL;
			$moduleName = isset($_POST["moduleName"]) ? $_POST["moduleName"] : NULL;
			$mcfIDs = isset($_POST["mcfIDs"]) ? $_POST["mcfIDs"] : NULL;
			$ids = isset($_POST["ids"]) ? $_POST["ids"] : NULL;
			$values = isset($_POST["values"]) ? $_POST["values"] : NULL;
			$optionIDs = isset($_POST["optionIDs"]) ? $_POST["optionIDs"] : NULL;
			$optionValues = isset($_POST["optionValues"]) ? $_POST["optionValues"] : NULL;
			$moduleFieldID = isset($_POST["moduleFieldID"]) && $_POST["moduleFieldID"] != "" ? $moduleFieldID = $_POST["moduleFieldID"] : NULL;
			print(submitModuleForm($formID, $moduleID, $moduleName, $moduleFieldID, json_decode($mcfIDs), json_decode($ids), json_decode($values), json_decode($optionIDs), json_decode($optionValues)));
		}
		elseif($command == 'newModule'){
			$moduleName = isset($_POST["moduleName"]) ? $_POST["moduleName"] : NULL;
			print(saveModule($moduleName, true));
		}
		elseif($command == 'deleteModuleField'){
			$moduleFieldID = isset($_POST["moduleFieldID"]) ? $_POST["moduleFieldID"] : NULL;
			print(deleteModuleField($moduleFieldID));
		}
		elseif($command == 'deleteModule'){
			$moduleID = isset($_POST["moduleID"]) ? $_POST["moduleID"] : NULL;
			print(deleteModule($moduleID));
		}
		elseif($command == 'newModuleField'){
			$moduleCreatorID = isset($_POST["moduleCreatorID"]) ? $_POST["moduleCreatorID"] : NULL;
			$editModuleID = isset($_POST["editModuleID"]) ? $_POST["editModuleID"] : NULL;
			$moduleName = isset($_POST["moduleName"]) ? $_POST["moduleName"] : NULL;
			print(newModuleField($moduleCreatorID, $editModuleID, $moduleName));
		}
		elseif($command == 'reloadForm'){
			$formID = isset($_POST["formID"]) && $_POST["formID"] != "" ? $_POST["formID"] : NULL;
			$moduleID = isset($_POST["moduleID"]) ? $_POST["moduleID"] : NULL;
			$moduleName = isset($_POST["moduleName"]) ? $_POST["moduleName"] : NULL;
			$moduleFieldID = isset($_POST["moduleFieldID"]) && $_POST["moduleFieldID"] != "" ? $moduleFieldID = $_POST["moduleFieldID"] : NULL;
			$newType = isset($_POST["newType"]) ? $_POST["newType"] : NULL;
			print(reloadForm($formID, $moduleID, $moduleName, $moduleFieldID, $newType));
		}
		elseif($command == 'saveModuleName'){
			$moduleID = isset($_POST["moduleID"]) ? $_POST["moduleID"] : NULL;
			$moduleName = isset($_POST["moduleName"]) ? $_POST["moduleName"] : NULL;
			print(saveModule($moduleName, false, $moduleID));
		}
		elseif($command == 'deleteOption'){
			$moduleFieldID = isset($_POST["moduleFieldID"]) && $_POST["moduleFieldID"] != "" ? $moduleFieldID = $_POST["moduleFieldID"] : NULL;
			$label = isset($_POST["label"]) ? $_POST["label"] : NULL;
			$value = isset($_POST["value"]) ? $_POST["value"] : NULL;
			print(deleteOption($moduleFieldID, $label, $value));
		}
		elseif($command == "moduleFields"){
			$moduleID = Utils::getPostInt("moduleID");
			print(moduleFieldOptions($moduleID));
		}
	}
?>
