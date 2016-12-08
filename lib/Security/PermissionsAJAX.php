<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Database.php");
	require_once("lib/Security/Security.php");
	require_once("lib/module/Module.php");
	require_once("lib/Utils.php");

	/// Submits a new role, or changes to a role, to the database
	/// @param $roleID The role to modify.  If NULL, will create a new role.
	/// @return Empty string upon success, otherwise an error string
	function submitRoleSettings($newRoleName, $newRoleDescription, $roleID=NULL){
		// We must have the proper privilege to create a new role, or edit an existing one
		if((!Security::privilege(new GeneralPrivilege("EditRole")) && isset($roleID))
			|| (!Security::privilege(new GeneralPrivilege("CreateRole")) && !isset($roleID)))
			return "Insufficient privileges";

		$newRole = new Role($newRoleName, $newRoleDescription, array(), $roleID);
		try{ $newRole->saveToDB(); }
		catch(Exception $e){
			Audit::logError($e);
			return "An error has occurred";
		}
		return "";
	}

	/// Modifies the general privileges of the specified Role by using
	///  POST data, and then saves the changes to the database
	/// @return Empty string upon success, otherwise an error string
	function submitGenPriv($roleID){
		if(!Security::privilege(new GeneralPrivilege("EditRole")))
			return "Insufficient privileges";
		
		try{ $role = Role::createRole($roleID); }
		catch(MySQLException $e){
			Audit::logError($e);
			return "Error loading existing role values";
		}
		foreach(GeneralPrivilege::tasks() as $task => $description){
			$newPriv = new GeneralPrivilege($task);
			if(!isset($_POST[$task]))
				$newPriv->valueIs(false);
			
			$role->privilegeIs($newPriv);
		}

		try{ $role->saveToDB(); }
		catch(MySQLException $e){
			Audit::logError($e);
			return "Error saving new role attributes";
		}

		return "";
	}

	/// Modifies the module-related privileges of the specified Role by using
	/// POST data, and then saves the changes to the database
	function submitModulePriv($roleID, $moduleID){
		if(!Security::privilege(new GeneralPrivilege("EditRole")))
			return "Insufficient privileges";
		
		$role = Role::createRole($roleID);
		foreach(ModulePrivilege::tasks() as $task => $description){
			$newPriv = new ModulePrivilege($task, $moduleID);

			// Since the default value is true, we only need to change it if it doesn't exist
			if(!isset($_POST[$moduleID . "_" . $task]))
				$newPriv->valueIs(false);

			$role->privilegeIs($newPriv);
		}
		$module = Module::createModule($moduleID);
		foreach($module->moduleFields() as $moduleFieldID => $moduleField){
			if($moduleField->hidden()) continue;
			foreach(ModuleFieldPrivilege::tasks() as $task => $description){
				$newPriv = new ModuleFieldPrivilege($task, $moduleFieldID);
				if(!isset($_POST[$moduleFieldID . "_" . $task]))
					$newPriv->valueIs(false);

				$role->privilegeIs($newPriv);
			}
		}

		$role->saveToDB();
		return "";
	}
	
	/// Creates an HTML string containing a list of roles available to the current user,
	///  and provides toggles to enable or disable them.
	function activeRoleDialog(){
		$session = new Zebra_Session();
		$output = "";

		$validRoles = Security::roleList(true, Security::userMIID());
		$activeRoleIDs = isset($_SESSION["ActiveRoles"]) ? explode("##", $_SESSION["ActiveRoles"]) : array_keys($validRoles);

		$output .= "<table id='activeRolesTable'>";
		foreach($validRoles as $roleID => $roleName){
			$selected = in_array($roleID, $activeRoleIDs) ? "Active" : "Disabled";
			$output .= <<<EOD
<tr>
	<td>$roleName<td>
	<td><div id='$roleID' class='ActiveRoleToggle'>$selected</div></td>
</tr>
EOD;
		}
		$output .= <<<EOD
<script type="text/javascript">
$(document).ready(function(){
	$(".ActiveRoleToggle").each(function(){
		$(this).button();
		if($(this).text() == "Disabled")
			$(this).children().addClass("ui-state-error");
	});
	$(".ActiveRoleToggle").click(function(){
		if($(this).text() == "Active"){
			$(this).children().html('Disabled');
			$(this).children().addClass("ui-state-error");
		}
		else{
			$(this).children().html('Active');
			$(this).children().removeClass("ui-state-error");
		}

		$(this).button();
	});
});
</script>
</table>
EOD;

		return $output;
	}

	/// Saves the results of active roles dialog box to a session variable, which
	///  will be used in future security-related calls
	function submitActiveRoles(){
		$session = new Zebra_Session();

		$validRoles = Security::roleList(true);
		$activeRoles = array();
		foreach($validRoles as $roleID => $roleName){
			if(isset($_POST[$roleID]) && $_POST[$roleID] == "Active")
				$activeRoles[] = $roleID;
		}
		$_SESSION["ActiveRoles"] = implode("##", $activeRoles);
	
		return "";
	}

	/// @return A array of all users in the form UserMIID => UserName
	function userList(){
		$iniArray = Utils::iniSettings();
		
		$userQuery = sprintf(
			"SELECT AES_DECRYPT(Value, '%s') AS UniqueValue,MFI.ModuleInstanceID AS ModuleInstanceID
			FROM (SELECT ModuleFieldID FROM ModuleField WHERE ModuleField.Unique=b'1') AS MF
				JOIN ModuleFieldInstance AS MFI
					ON MF.ModuleFieldID=MFI.ModuleFieldID
				JOIN (SELECT ModuleInstanceID FROM ModuleInstance JOIN Module ON Module.ModuleID=ModuleInstance.ModuleID WHERE Module.Name='Member') AS M
					ON MFI.ModuleInstanceID=M.ModuleInstanceID
			ORDER BY UniqueValue",
			mysql_real_escape_string($iniArray["passCode"]));
		$userResult = Database::getInstance()->query($userQuery);
		$resultArray = array();
		while($userObj = $userResult->fetch_object())
			$resultArray[$userObj->ModuleInstanceID] = $userObj->UniqueValue;
		return $resultArray;
	}

	/// @return An HTML string to be used to give roles additional privileges
	///  for a particular module instance
	function instancePrivDialog($moduleInstanceID, $moduleID){
		$output = "";

		$instancePrivs = InstancePrivileges::moduleInstancePrivileges($moduleInstanceID);
		$existingPrivsStr = "";
		foreach($instancePrivs->privileges() as $instancePrivilege){
			$userName = UserData::userName($instancePrivilege->userMIID());
			$existingPrivsStr .= "appendInstancePrivEntry('{$instancePrivilege->instancePrivilegeID()}', '{$userName}', '{$instancePrivilege->roleName()}');\n";
		}

		$userList = userList();
		$userOptions = "";
		foreach($userList as $userMIID => $userName)
			$userOptions .= "<option value='$userMIID'>$userName</option>\n";

		$validRoles = Security::roleList(true);
		$roleOptions = "";
		foreach($validRoles as $roleID => $roleName)
			$roleOptions .= "<option value='$roleID'>$roleName</option>\n";

		$output .= <<<EOD
Let an individual user "borrow" the privileges accorded to a role for just this instance.<br/>
<select id="userSelect" size="10" style="width:45%;">
	$userOptions
</select>
<select id="roleSelect" size="10" style="width:45%;">
	$roleOptions
</select>
<div id="newInstancePriv" style="font-size:10pt;">Add</div>
<br/><br/>
<table id="instancePrivList">
	<tr><th>User Name</th><th>Role Name</th><th>Delete</th></tr>
</table>
<script type="text/javascript">
initInstancePrivDialog();
$existingPrivsStr
</script>
EOD;
		return $output;
	}

	/// Saves the result of the instance privilege dialog box to the database
	/// @param $moduleInstanceID The instance who we are allowing another to access
	/// @param $userMIID The user who is receiving additional privileges
	/// @param $roleID The role whose privileges we are borrowing from
	/// @return An integer instance privilege ID, or an error string
	function submitInstancePrivilege($moduleInstanceID, $moduleID, $userMIID, $roleID){
		if(!Security::privilege(new ModulePrivilege("TransferRole", $moduleID))
			|| array_search($roleID, array_keys(Security::roleList(true))) === false)
			return "Insufficient privileges";

		$instancePrivs = InstancePrivileges::moduleInstancePrivileges($moduleInstanceID);
		$instancePriv =  new InstancePrivilege($userMIID, $roleID, $moduleInstanceID);
		
		$instancePrivilegeID = $instancePrivs->privilegeIs($instancePriv); 
		return $instancePrivilegeID;
	}

	/// Deletes the given instance privilege from the DB
	/// @return Empty string if success, otherwise an error string
	function deleteInstancePrivilege($instancePrivilegeID, $moduleID){
		if(!Security::privilege(new ModulePrivilege("TransferRole", $moduleID)))
			return "Insufficient privileges";
		$instancePrivs = new InstancePrivileges(array());
		$errorString = $instancePrivs->deletePrivilege($instancePrivilegeID, true);

		return $errorString;
	}

	/// Deletes a role from the DB
	/// @return Empty string upon success, otherwise an error string
	function deleteRole($roleID){
		if(Security::privilege(new GeneralPrivilege("DeleteRole"))){
			$roleQuery = sprintf(
				"DELETE FROM Role
				WHERE RoleID='%s'",
				mysql_real_escape_string($roleID));
			try{ Database::getInstance()->query($roleQuery, 2, 1); }
			catch(MySQLException $e){
				Audit::logError($e);
				return "Database Error";
			}
			return "";
		}
		else{
			Audit::logError(new PrivilegeException("Insufficient privileges to delete Role: " . $roleID));
			return "Insufficient privileges to delete Role";
		}
	}

	$moduleID = Utils::getPostInt("moduleID");
	$roleID = Utils::getPostInt("roleID");

	if(isset($_POST['command'])){
		$command = $_POST['command'];
		if($command == 'submitGenPriv')
			print(submitGenPriv($roleID));
		elseif($command == 'instancePrivDialog'){
			$miid = Utils::getPostInt("moduleInstanceID");
			print(instancePrivDialog($miid, $moduleID));
		}
		elseif($command == 'deleteInstancePrivilege'){
			$instancePrivilegeID = Utils::getPostInt("instancePrivilegeID");
			print(deleteInstancePrivilege($instancePrivilegeID, $moduleID));
		}
		elseif($command == 'submitInstancePrivilege'){
			$moduleInstanceID = Utils::getPostInt("moduleInstanceID");
			$userMIID = Utils::getPostInt("userMIID");
			print(submitInstancePrivilege($moduleInstanceID, $moduleID, $userMIID, $roleID));
		}
		elseif($command == 'activeRoleDialog')
			print(activeRoleDialog());
		elseif($command == 'submitActiveRoles')
			print(submitActiveRoles());
		elseif($command == 'deleteRole')
			print(deleteRole($roleID));
		elseif($command == 'submitModulePriv')
			print(submitModulePriv($roleID, $moduleID));
		elseif($command == 'submitRole'){
			$roleName = isset($_POST["roleName"]) && $_POST["roleName"] != "" ? $_POST["roleName"] : NULL;
			$roleDesc = isset($_POST["roleDesc"]) && $_POST["roleDesc"] != "" ? $_POST["roleDesc"] : NULL;
			if(isset($roleName) && preg_match('/^[a-zA-Z]{1,20}$/', $roleName) != 1)
				throw new InvalidArgumentException("Invalid roleID: $roleID");

			print(submitRoleSettings($roleName, $roleDesc, $roleID));
		}
	}
	else
		return "Error: no command";
?>
