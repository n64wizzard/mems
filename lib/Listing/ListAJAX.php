<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Database.php");
	require_once("lib/Module/FieldFactory.php");
	require_once("lib/Module/Moduleinstance.php");
	require_once("lib/Listing/ListFilter.php");
	require_once("lib/Security/Security.php");

	/// Delete some Module Instance from the DB
	/// @return Empty string upon success, or an error string
	function deleteMI($moduleInstanceID, $moduleID){
		if(!Security::privilege(new ModulePrivilege("DeleteInstance", $moduleID), $moduleInstanceID)){
			Audit::logError(new PrivilegeException("Illegal attempt to delete MIID: " . $moduleInstanceID));
			return "Error: Insufficient privileges to delete ModuleInstance from DB";
		}

		return ModuleInstance::deleteInstance($moduleInstanceID);
	}

	/// When the user changes the Module Field associated with some filter,
	///  we need to update the field depending on its type (eg. DatePicker -> DateRangePicker)
	/// @param $filterCount The index of the filter we are changing (required in order to name input properly)
	/// @param $newModuleFieldID ID of the new fiter type
	/// @return The new field, otherwise an error string starting with 'Error:'
	function changeFilterField($newModuleFieldID, $filterCount, $moduleID){
		try{
			$newListFilter = new ListFilter($newModuleFieldID, $filterCount, array());
			return $newListFilter->toHTML(Module::createModule($moduleID)->moduleFields());
		}
		catch(MySQLException $e){
			Audit::logError($e);
			return "Error: Database error ";
		}
	}

	$moduleID = Utils::getPostInt("moduleID");
	if(isset($_POST['command'])){
		$command = $_POST['command'];
		if($command == 'deleteMI'){
			$moduleInstanceID = Utils::getPostInt("moduleInstanceID");
			print(deleteMI($moduleInstanceID, $moduleID));
		}
		if($command == 'filter_change'){
			$moduleFieldID = Utils::getPostInt("newMFID");
			$filterCount =  Utils::getPostInt("filterCount");
			print(changeFilterField($moduleFieldID, $filterCount, $moduleID));
		}
	}
?>
