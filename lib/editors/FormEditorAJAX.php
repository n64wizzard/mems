<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Database.php");
	require_once("lib/Security/Security.php");
	require_once("lib/Utils.php");
	require_once("lib/editors/FormEditorPage.php");
	require_once("lib/module/Module.php");

	/// Deletes a form from the DB, along with all Form Fields, etc.
	/// @return Empty string upon succes, otherwise an error string
	function deleteForm($formID, $moduleID){
		if(Security::privilege(new ModulePrivilege("DeleteForm", $moduleID))){
			$formQuery = sprintf(
				"DELETE Form FROM Form JOIN Page ON Page.PageName=Form.PageName
				WHERE FormID='%s' AND Removable=b'1'",
				mysql_real_escape_string($formID));
			try{ Database::getInstance()->query($formQuery, 2, 1); }
			catch(MySQLException $e){
				Audit::logError($e);
				return "Database Error $e";
			}
			return "";
		}
		else{
			Audit::logError(new PrivilegeException("Insufficient privileges to delete Form: " . $formID));
			return "Insufficient privileges to delete Form";
		}
	}

	/// The dialog box that appears after clicking the 'Create New Form' button
	///  on the Forms page.
	/// @return the HTML for the dialog box
	function newFormDialog($formName='', $formTitle='', $currModuleID='',
							$forceLogin='', $formDesc='', $formID=''){
		$output = "";
		$moduleOptions = "";
		foreach(Module::moduleNames() as $moduleID => $moduleName)
			// If we are editing an existing form, we don't want to allow a module change
			if((Security::privilege(new ModulePrivilege("CreateForm", $moduleID)) && $currModuleID=="") ||
					$currModuleID != "")
				$moduleOptions .= "<option value='$moduleID'>$moduleName</option>\n";

		$output .= <<<EOD
<form onsubmit="$(\'#editFormSubmitButton').click(); return false;">
<label for="formName">Enter the new Form Name:</label>
<input type="text" size="30" maxlength="20" id="formName" value='$formName' /><br/><br/>
<label for="formTitle">Form Title:</label>
<input type="text" size="30" maxlength="20" id="formTitle" value='$formTitle' /><br/><br/>
<label for="moduleID">Module:</label>
<select id="moduleID">
$moduleOptions
</select>
<br/><br/>
<label for="forceLogin">Seconds since last logon (Optional):</label>
<input type="text" size="30" maxlength="20" id="forceLogin" value='$forceLogin' />
<br/><br/>
<label for="formDesc">Description (optional):</label>
<textarea type="text" rows="3" cols="30" id="formDesc">$formDesc</textarea><br/><br/>
<div id="editFormSubmitButton">Submit</div>
<input type="hidden" id="formID" value='$formID'/>
</form>
<script type="text/javascript">
	submitFormScript("editFormSubmitButton", '$formID');
</script>
EOD;
		return $output;
	}

	/// @return The HTML contents for a dialog box to edit existing form settings
	function editFormDialog($formID){
		$formQuery = sprintf(
			"SELECT FormID, Page.ModuleID, Page.PageName, Page.Description, Page.ForceLogin, Page.PageTitle
			FROM Form JOIN Page
				ON Form.PageName=Page.PageName
			WHERE FormID='%s'",
			mysql_real_escape_string($formID));
		try{ $formObj = Database::getInstance()->query($formQuery, 2, 1)->fetch_object(); }
		catch(MySQLException $e){
			Audit::logError($e);
			return "Error loading existing form properties.";
		}

		return newFormDialog($formObj->PageName, $formObj->PageTitle, $formObj->ModuleID, 
				$formObj->ForceLogin, $formObj->Description, $formObj->FormID);
	}

	/// Submits the attributes available from the newFormDialog()
	/// @return An empty string upon success, otherwise an error message
	function submitFormSettings($formID, $moduleID){
		$formName = isset($_POST["formName"]) && $_POST["formName"] != "" ? $_POST["formName"] : NULL;
		$formDesc = isset($_POST["formDesc"]) ? $_POST["formDesc"] : "";
		$formTitle = isset($_POST["formTitle"]) ? $_POST["formTitle"] : $formName;
		$forceLogin = isset($_POST["forceLogin"]) && $_POST["forceLogin"] != "" ? $_POST["forceLogin"] : "3600";

		try{
			if(isset($forceLogin) && preg_match('/^[0-9]{1,40}$/', $forceLogin) != 1)
				throw new InvalidArgumentException("Invalid forceLogin value: $forceLogin");
			if(isset($formName) && preg_match('/^[0-9A-Za-z]{1,20}$/', $formName) != 1)
				throw new InvalidArgumentException("Invalid formName: $formName");
			if(!isset($formName))
				throw new InvalidArgumentException("No Form Name submitted");
		}
		catch(InvalidArgumentException $e){
			Audit::logError($e);
			return $e->getMessage();
		}

		if(isset($formID) && Security::privilege(new ModulePrivilege("EditForm", $moduleID))){
			$updateQuery = sprintf(
				"UPDATE Page JOIN Form ON Page.PageName=Form.PageName
				SET Page.PageName='%s', Description='%s', PageTitle='%s', ForceLogin='%s'
				WHERE FormID='%s'",
				mysql_real_escape_string($formName),
				mysql_real_escape_string($formDesc),
				mysql_real_escape_string($formTitle),
				mysql_real_escape_string($forceLogin),
				mysql_real_escape_string($formID));
			try{ 
				Database::getInstance()->query($updateQuery);
			}
			catch(MySQLException $e){
				Audit::logError($e);
				return "Database Error during update $e";
			}
		}
		elseif(!isset($formID) && Security::privilege(new ModulePrivilege("CreateForm", $moduleID))){
			Database::getInstance()->startTransaction();
			$insertPageQuery = sprintf(
				"INSERT INTO Page
				(`PageName`, `Description`, `PageTitle`, `ForceLogin`, `ModuleID`, `Removable`) VALUES
				('%s', '%s', '%s', '%s', '%s', b'1')",
				mysql_real_escape_string($formName),
				mysql_real_escape_string($formDesc),
				mysql_real_escape_string($formTitle),
				mysql_real_escape_string($forceLogin),
				mysql_real_escape_string($moduleID));
			$insertFormQuery = sprintf(
				"INSERT INTO Form
				(`PageName`, `FormID`) VALUES
				('%s', '%s')",
				mysql_real_escape_string($formName),
				mysql_real_escape_string($formID));
			try{
				Database::getInstance()->query($insertPageQuery, 2, 1);
				Database::getInstance()->query($insertFormQuery, 2, 1);
			}
			catch(MySQLException $e){
				Audit::logError($e);
				Database::getInstance()->rollback();
				return "Database Error during creation";
			}
			Database::getInstance()->commit();
		}
		else{
			Audit::logError(new PrivilegeException("Insufficient privileges to create/edit a Form"));
			return "Insufficient privileges";
		}

		return "";
	}

	/// Submits the form fields for a particular form
	/// @param $_POST["data"] In URL encoded JSON form
	/// @return An empty string upon success, otherwise an error string
	function submitForm($formID){
		$form = new Form($formID);
		$currFormFields = $form->formFields();
		
		$fieldsToDelete = array();
		foreach($currFormFields as $formFieldID => $formField)
			$fieldsToDelete[$formFieldID] = true;

		$newFormFields = json_decode(rawurldecode($_POST['data']));
		
		foreach($newFormFields as $JSONField){
			$formFieldData = json_decode($JSONField);

			// If the field already exists, and we're just modifying it
			if(isset($fieldsToDelete[$formFieldData->formFieldID])){
				$formFieldID = $formFieldData->formFieldID;
				$formField = $currFormFields[$formFieldID];

				$formField->mutableIs($formFieldData->mutable);
				$formField->positionIs(new Position($formFieldData->left, $formFieldData->top,
						$formFieldData->width, $formFieldData->height));
				$formField->includeLabelIs($formFieldData->includeLabel);

				$fieldsToDelete[$formFieldID] = false;
			}
			// If we are creating a new field
			else{
				$position = new Position($formFieldData->left, $formFieldData->top, 
						$formFieldData->width, $formFieldData->height);
				$newFormField = new FormField(false, $formFieldData->mutable, $position,
						new ModuleFieldInstance($formFieldData->moduleFieldID, NULL),
						$formFieldData->mutable,  $formFieldData->includeLabel, true);

				$form->formFieldIs($newFormField);
			}
		}

		try{
			foreach($fieldsToDelete as $formFieldID => $delete)
				if($delete)
					$currFormFields[$formFieldID]->removeFromDB($form->moduleID());

			$form->saveToDB();
		}
		catch(MySQLException $e){
			Audit::logError($e);
			return "Error saving changes to the databse";
		}

		return "";
	}

	/// Creates the HTML for a new form field with the given attributes
	function formFieldHTML($formFieldID, $moduleFieldID, $height, $width, $left, $top, $mutable, $includeLabel){
			$moduleFieldInstance = new ModuleFieldInstance($moduleFieldID, NULL);
			$formField = new FormField($formFieldID, true, new Position($left, $top, $width, $height),
								$moduleFieldInstance, $mutable, $includeLabel, true);

		return $formField->toHTML();
	}

	$formID = Utils::getPostInt("formID");
	$moduleID = Utils::getPostInt("moduleID");

	if(isset($_POST['command'])){
		$command = $_POST['command'];
		if($command == 'deleteForm')
			print(deleteForm($formID, $moduleID));
		elseif($command == 'newFormDialog')
			print(newFormDialog());
		elseif($command == 'submitFormSettings')
			print(submitFormSettings($formID, $moduleID));
		elseif($command == 'submitForm')
			print(submitForm($formID));
		elseif($command == 'editFormDialog')
			print(editFormDialog($formID));
		elseif($command == 'formFieldHTML'){
			$formFieldID = Utils::getPostInt("formFieldID");
			$moduleFieldID = Utils::getPostInt("moduleFieldID");
			$height = Utils::getPostInt("height");
			$width = Utils::getPostInt("width");
			$left = Utils::getPostInt("left", true);
			$top = Utils::getPostInt("top", true);
			$mutable = Utils::getPostInt("mutable", false, 1);
			$includeLabel = Utils::getPostInt("includeLabel", false, 1);
			print(formFieldHTML($formFieldID, $moduleFieldID, $height, $width, $left, $top, $mutable, $includeLabel));
		}
	}
?>
