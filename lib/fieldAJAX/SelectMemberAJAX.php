<?php
	/// This file contains AJAX functions related to the SelectMembers field
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Database.php");
	require_once("lib/Module/FieldFactory.php");
	require_once("lib/Module/Moduleinstance.php");

	/// Operations resulting from clicking on either the 'add' or 'drop' buttons
	///  of the select member field
	/// @return Empty string upon success, otherwise an error string
	function selectMemberAction($moduleInstanceID, $moduleFieldID){
		$userMIID = Security::userMIID();
		$userRoleList = Security::roleList();
		Security::disableSecurityIs(true);

		// Check that user meets connected SelectRoles requirements
		try{ $moduleInstance = ModuleInstance::createModuleInstance($moduleInstanceID); }
		catch(MySQLException $e){
			Audit::logError($moduleFieldInstance);
			return "Error finding ModuleInstance in database";
		}
		
		try{ $moduleFieldInstance = $moduleInstance->moduleFieldInstance($moduleFieldID); }
		catch(InvalidArgumentException $e){
			Audit::logError($e);
			return "Invalid Module Field ID";
		}

		$currentValues = $moduleFieldInstance->currentValue() == "" ? array() : explode("##", $moduleFieldInstance->currentValue());
		$alreadyRegistered = array_search($userMIID, $currentValues);

		$roleFieldID = $moduleFieldInstance->moduleField()->roleFieldID();
		$maxReg = $moduleFieldInstance->moduleField()->maxRegistration();
		$deleteOnDrop = $moduleFieldInstance->moduleField()->deleteOnDrop();

		// If we are dropping the event
		if($alreadyRegistered !== false){
			unset($currentValues[$alreadyRegistered]);
			if(count($currentValues) == 0 && $deleteOnDrop == true){
				ModuleInstance::deleteInstance ($moduleInstanceID);
				return "";
			}

			$moduleFieldInstance->currentValueIs(implode("##", $currentValues));
			try{ $moduleFieldInstance->saveToDB(); }
			catch(MySQLException $e){
				Audit::logError($moduleFieldInstance);
				return "Database Error";
			}
			return "";
		}

		$approved = !isset($roleFieldID);
		if(isset($roleFieldID)){	// If there is an attached role field...
			try{ $roleField = $moduleInstance->moduleFieldInstance($roleFieldID); }
			catch(InvalidArgumentException $e){
				Audit::logError($e);
				return "Invalid Module Field ID for ssociated Role Field";
			}
			$allowedRoleIDs = explode("##", $roleField->currentValue());

			foreach($userRoleList as $roleID => $roleName)
				if(array_search($roleID, $allowedRoleIDs) !== false){
					$approved = true;
					break;
				}
		}
		if($moduleFieldInstance->currentValue() != "" && count($currentValues) >= $maxReg)
			return "Max Registrations reached";

		// If everything looks good to this point, make the changes
		if($approved){
			$currentValues[] = $userMIID;

			$moduleFieldInstance->currentValueIs(implode("##", $currentValues));
			try{ $moduleFieldInstance->saveToDB(); }
			catch(MySQLException $e){
				Audit::logError($moduleFieldInstance);
				return "Database Error";
			}
			return "";
		}
		else
			return "Insufficient credentials";
	}

	if(isset($_POST['command'])){
		if($_POST['command'] == 'toggle'){	// Add or remove as necessary
			$moduleInstanceID = Utils::getPostInt("moduleInstanceID");
			$moduleFieldID = Utils::getPostInt("moduleFieldID");
			print(selectMemberAction($moduleInstanceID, $moduleFieldID));
		}
	}
?>
