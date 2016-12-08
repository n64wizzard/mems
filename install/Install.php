<?php
	/// This file contains all of the code to start-up a bare-bones installation of MEMS
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("lib/Utils.php");
	require_once("install/InitDatabase.php");
	require_once("install/InstallCUEMSDemo.php");
	require_once("install/InstallSTEMSDemo.php");
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<title>Member and Event Management Systems - Installation</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	</head>
	<body>
		
<?php
	/// Installs all of the Module, Page, Form, and Listing-related data
	function installDefaultFields(){
		$defaultEntries = array(
			"INSERT INTO `module`
				(`ModuleID`, `Name`, `Removable`, `Hidden`) VALUES
				(NULL, 'Member', b'0', b'0')",
			"INSERT INTO `page`
				(`PageName`, `PageTitle`, `Removable`, `ModuleID`, `ForceLogin`, `Description`) VALUES
				('NewReg', 'New Registration', b'0', '1', '0', 'New registration page.'),
				('Profile', 'Profile', b'0', '1', '3600', 'Form to edit one\'s profile data.'),
				('ChangePW', 'Change Password', b'0', '1', '10', 'A small form to change one\'s password'),
				('Logon', 'Welcome', b'0', '1', '0', 'Logon form for all to use.'),
				('MemberList', 'Member List', b'1', '1', '3600', 'List of all members'),
				('LogOut', 'Log Out', b'0', '1', '1', 'Forces the user to log-out.')",
			"INSERT INTO `navlink` (`NavLinkID`, `NavMenuName`, `Text`, `PageName`, `ModuleInstanceID`, `Position`, `Group`) VALUES
				(NULL, 'Default', 'Main', 'MemberList', NULL, '1', NULL),
				(NULL, 'Default', 'Member List', 'MemberList', NULL, '2', NULL),
				(NULL, 'Default', 'Modules', '##ModuleCreator.php', NULL, '5', 'Administration'),
				(NULL, 'Default', 'Listings', '##ListingEditor.php', NULL, '6', 'Administration'),
				(NULL, 'Default', 'Forms', '##FormEditor.php', NULL, '7', 'Administration'),
				(NULL, 'Default', 'Permissions', '##Permissions.php', NULL, '8', 'Administration'),
				(NULL, 'Default', 'Log Out', 'LogOut', NULL, '9', NULL),
				(NULL, 'Default', 'StEMS', '##http://cuems.cornell.edu', NULL, '10', NULL)",
			"INSERT INTO `form`
				(`FormID`, `PageName`) VALUES
				(NULL, 'NewReg'),
				(NULL, 'Profile'),
				(NULL, 'Logon'),
				(NULL, 'ChangePW'),
				(NULL, 'LogOut')",
			"INSERT INTO `ModuleField`
				(`ModuleFieldID`, `ModuleID`, `Name`, `Type`, `Description`, `Regex`, `DefaultValue`, `Label`, `Hidden`, `Unique`) VALUES
				('1', '1', 'UserName', 'Text', 'Member-chosen user ID.', '/^.{1,10}$/s', '', 'User Name', b'0', b'1'),
				('2', '1', 'Password', 'Password', 'User\'s secret access code.', '/^.*$/s', '', 'Password', b'1', b'0'),
				('3', '1', 'CurrentCookie', 'Date', 'Hash of the cookie value sent to the user if they requested to be remembered during last last successful logon.', '/.*/s', '', 'Cookie', b'1', b'0'),
				('4', '1', 'CurrrentSession', 'Static', 'Session ID of current session.', '/.*/s', '', 'Session ID', b'1', b'0'),
				('5', '1', 'IPAddress', 'Static', 'Current IP address of the member.', '/.*/s', '', 'IP Address', b'1', b'0'),
				('6', '1', 'Captcha', 'Captcha', 'CAPTCHA', '', '', 'Captcha', b'0', b'0'),
				('7', '1', 'Roles', 'SelectRoles', 'Privilege sets this member is a part of.', '/.*/s', '', 'Roles', b'0', b'0'),
				('8', '1', 'FirstName', 'Text', 'First Name of the member.', '/^[a-zA-Z-\s]{1,40}$/s', '', 'First Name', b'0', b'0'),
				('9', '1', 'LastName', 'Text', 'Last Name of the member.', '/^[a-zA-Z-\s]{1,40}$/s', '', 'Last Name', b'0', b'0'),
				('10', '1', 'PhoneNumber', 'Text', 'Phone number', '/^\\\([0-9]{3}\\\)[0-9]{3}-[0-9]{4}$/s', '', 'Phone Number', b'0', b'0'),
				('11', '1', 'Email', 'Text', 'Email Address', '/^.*$/s', '', 'Email Address', b'0', b'0')",
			"INSERT INTO `modulefieldoption`
				(`ModuleFieldOptionID`, `OptionValue`, `OptionLabel`, `ModuleFieldID`) VALUES
				(NULL, '', 'SaveUserRoles', '7')",
			"INSERT INTO `formfield`
				(`FormFieldID`, `ModuleFieldID`, `FormID`, `Pos_Top`, `Pos_Left`, `Pos_Width`, `Pos_Height`, `Mutable`, `IncludeLabel`, `Removable`) VALUES
				(1, 1, 2, 23, 2, 100, 22, '1', '1', '1'),
				(2, 7, 2, 291, 5, 150, 100, '1', '1', '1'),
				(3, 8, 2, -1, -1, 100, 22, '1', '1', '1'),
				(4, 9, 2, -1, 210, 100, 22, '1', '1', '1'),
				(5, 10, 2, 60, 1, 100, 22, '1', '1', '1'),
				(6, 11, 2, 84, 1, 200, 22, '1', '1', '1'),
				(7, 1, 1, 0, 0, 100, 22, '1', '1', '1'),
				(8, 2, 1, 24, 10, 100, 22, '1', '1', '1'),
				(9, 6, 1, -1, 407, 318, 129, '1', '1', '1'),
				(10, 8, 1, 93, 4, 100, 22, '1', '1', '1'),
				(11, 9, 1, 93, 187, 100, 22, '1', '1', '1'),
				(12, 11, 1, 117, 4, 200, 22, '1', '1', '1'),
				(13, 1, 3, 0, 0, 100, 22, '1', '1', '1'),
				(14, 2, 3, -1, 201, 100, 22, '1', '1', '1'),
				(15, 2, 4, 0, 0, 100, 22, '1', '1', '1'),
				(16, 2, 4, 24, 77, 100, 22, '1', '1', '1')",
			"INSERT INTO `listing`
				(`ListingID`, `PageName`, `MaxItems`, `NewEntryPageName`, `CreateText`) VALUES
				(1, 'MemberList', '10', 'Profile', 'Create New')",
			"INSERT INTO `listfield`
				(`ListFieldID`, `Position`, `ModuleFieldID`, `ListingID`, `IncludeLabel`, `LinkPageName`, `Width`) VALUES
				(NULL, '1', '9', '1', b'0', NULL, '1'),
				(NULL, '2', '8', '1', b'0', NULL, '1'),
				(NULL, '3', '1', '1', b'0', 'Profile', '1'),
				(NULL, '4', '10', '1', b'0', NULL, '1'),
				(NULL, '5', '11', '1', b'0', NULL, '1'),
				(NULL, '6', NULL, '1', b'0', NULL, '1')",
			"INSERT INTO `listby`
				(`ListByID`, `Rank`, `ModuleFieldID`, `ListingID`, `Direction`, `Orientation`, `Type`) VALUES
				(NULL, '1', '9', '1', b'1', b'0', b'0')",
			"INSERT INTO `listoption`
				(`ListOptionID`, `PageName`, `ListingID`, `Title`) VALUES
				(NULL, 'Profile', '1', 'View'),
				(NULL, 'Profile', '1', 'Edit'),
				(NULL, NULL, '1', 'Delete')",
			"INSERT INTO `listfilter`
				(`ListFilterID`, `ModuleFieldID`, `ListingID`, `Value`) VALUES
				(NULL, '8', '1', 'value:')"
			);
		foreach($defaultEntries as $defaultQuery)
			Database::getInstance()->query($defaultQuery);
	}
	/// Creates the fields, modules, etc. used by the ModuleCreator page
	function installEditorFields(){
		$creatorEntries = array(
			"INSERT INTO `module`
				(`ModuleID`, `Name`, `Removable`, `Hidden`) VALUES
				(20, 'ModuleCreator', b'0', b'1')",
			"INSERT INTO `page`
				(`PageName`, `PageTitle`, `Removable`, `ModuleID`, `ForceLogin`) VALUES
				('ModuleCreator', 'Module Editor', b'0', '20', '600')",
			"INSERT INTO `form`
				(`FormID`, `PageName`) VALUES
				(20, 'ModuleCreator')",
			"INSERT INTO `ModuleField`
				(`ModuleFieldID`, `ModuleID`, `Name`, `Type`, `Description`, `Regex`, `DefaultValue`, `Label`, `Hidden`, `Unique`) VALUES
				(20, '20', 'Name', 'Text', 'Name of the new field.', '/\\\S+/s', '', 'Name', b'0', b'0'),
				(21, '20', 'Type', 'Select', 'Type of the new field.', '/.+/s', '', 'Type', b'0', b'0'),
				(22, '20', 'Description', 'Text', 'Description of the new field.', '/.*/s', '', 'Description', b'0', b'0'),
				(23, '20', 'Regex', 'Text', 'Regular expression for the new field.', '/.*/s', '/.*/s', 'Regex', b'0', b'0'),
				(24, '20', 'DefaultValue', 'Text', 'Default value for the new field.', '/.*/s', '', 'DefaultValue', b'0', b'0'),
				(25, '20', 'Label', 'Text', 'Label for the new field.', '/.+/s', '', 'Label', b'0', b'0'),
				(26, '20', 'ModuleName', 'Select', 'Name of the module to create/edit.', '/.*/s', '', 'Module Name', b'0', b'0')",
			"INSERT INTO `formfield`
				(`FormFieldID`, `ModuleFieldID`, `FormID`, `Pos_Top`, `Pos_Left`, `Pos_Width`, `Pos_Height`, `Mutable`, `IncludeLabel`, `Removable`) VALUES
				(17, 20, 20, 0, 0, 100, 22, '1', '1', '1'),
				(18, 21, 20, 0, 147, 100, 25, '1', '1', '1'),
				(19, 22, 20, 30, 0, 600, 22, '1', '1', '1'),
				(20, 23, 20, 0, 626, 150, 22, '1', '1', '1'),
				(21, 24, 20, 0, 432, 100, 22, '1', '1', '1'),
				(22, 25, 20, 0, 287, 100, 22, '1', '1', '1')",
			"INSERT INTO `moduleinstance` (`ModuleInstanceID`, `ModuleID`) VALUES
				(20, 20)"
			);
		foreach($creatorEntries as $creatorEntry)
			Database::getInstance()->query($creatorEntry);
	}
	/// Creates two roles: one which allows all people to attempt to sign-in or register;
	///  and another which is given full privileges over everything.
	function createUserRoles($adminRoleName){
		$newRoleQuery = "INSERT INTO `Role`
			(`RoleID`, `RoleName`, `Description`) VALUES
			('1', 'Anonymous', 'Access granted to non-members.  Should include Username/Password fields to permit logon.'),
			('2', '$adminRoleName', 'Given full access to all parts of the site.  Has every privilege.')";
		Database::getInstance()->query($newRoleQuery);
		$modulePrivileges = "INSERT INTO `moduleprivilege`
			(`ModulePrivilegeID`, `RoleID`, `ModuleID`, `Task`) VALUES
			(NULL , '1', '1', 'CreateInstance'),
			(NULL, 2, 1, 'EditModuleProperties'),
			(NULL, 2, 1, 'TransferRole'),
			(NULL, 2, 1, 'CreateField'),
			(NULL, 2, 1, 'DeleteField'),
			(NULL, 2, 1, 'CreateInstance'),
			(NULL, 2, 1, 'DeleteInstance'),
			(NULL, 2, 1, 'CreateList'),
			(NULL, 2, 1, 'CreateForm'),
			(NULL, 2, 1, 'DeleteList'),
			(NULL, 2, 1, 'DeleteForm'),
			(NULL, 2, 1, 'EditList'),
			(NULL, 2, 1, 'EditForm')";
		Database::getInstance()->query($modulePrivileges);
		$generalPrivileges = "INSERT INTO `GeneralPrivilege`
			(`GeneralPrivilegeID`, `RoleID`, `Task`) VALUES
			(NULL , '2', 'EditRole'),
			(NULL , '2', 'ChangeRoles'),
			(NULL , '2', 'CreateRole'),
			(NULL , '2', 'DeleteRole'),
			(NULL , '2', 'NavBarEdit'),
			(NULL , '2', 'Logon'),
			(NULL, 2, 'CreateModule'),
			(NULL, 2, 'DeleteModule')";
		Database::getInstance()->query($generalPrivileges);
		$moduleFieldPrivileges = "INSERT INTO `MFPrivilege`
			(`MFPID`, `ModuleFieldID`, `RoleID`, `Task`) VALUES
			(NULL , '1', '1', 'Create'),
			(NULL , '2', '1', 'Create'),
			(NULL , '8', '1', 'Create'),
			(NULL , '9', '1', 'Create'),
			(NULL , '10', '1', 'Create'),
			(NULL , '11', '1', 'Create'),
			(NULL , '1', '2', 'Create'),
			(NULL , '2', '2', 'Create'),
			(NULL , '3', '2', 'Create'),
			(NULL , '4', '2', 'Create'),
			(NULL , '5', '2', 'Create'),
			(NULL , '6', '2', 'Create'),
			(NULL , '7', '2', 'Create'),
			(NULL , '8', '2', 'Create'),
			(NULL , '9', '2', 'Create'),
			(NULL , '10', '2', 'Create'),
			(NULL , '11', '2', 'Create'),
			(NULL , '1', '2', 'Read'),
			(NULL , '2', '2', 'Read'),
			(NULL , '3', '2', 'Read'),
			(NULL , '4', '2', 'Read'),
			(NULL , '5', '2', 'Read'),
			(NULL , '6', '2', 'Read'),
			(NULL , '7', '2', 'Read'),
			(NULL , '8', '2', 'Read'),
			(NULL , '9', '2', 'Read'),
			(NULL , '10', '2', 'Read'),
			(NULL , '11', '2', 'Read'),
			(NULL , '1', '2', 'Write'),
			(NULL , '2', '2', 'Write'),
			(NULL , '3', '2', 'Write'),
			(NULL , '4', '2', 'Write'),
			(NULL , '5', '2', 'Write'),
			(NULL , '6', '2', 'Write'),
			(NULL , '7', '2', 'Write'),
			(NULL , '8', '2', 'Write'),
			(NULL , '9', '2', 'Write'),
			(NULL , '10', '2', 'Write'),
			(NULL , '11', '2', 'Write')";
		Database::getInstance()->query($moduleFieldPrivileges);
	}
	/// Creates an initial "Admin" user
	function createInitialUser($userName, $userPassword){
		$initialUserQuery = "INSERT INTO `moduleinstance`
			(`ModuleInstanceID`, `ModuleID`) VALUES
			(1, 1)";
		Database::getInstance()->query($initialUserQuery);

		$hashedPassword = Utils::hashPassword($userPassword);
		$moduleInstance = ModuleInstance::createModuleInstance(1);
		$fieldsToModify = array(
			"1" => $userName,
			"2" => $hashedPassword,
			"7" => "2",
			"8" => "An",
			"9" => "Administrator",
			"10" => "(650)555-1234",
			"11" => "Admin@ems.com");
		$moduleInstance->moduleFieldValuesAre($fieldsToModify);
		$moduleInstance->saveToDB();
	}

	print("Loading parameters...<br/>");
	$dbHostName = isset($_POST["dbHostName"]) ? $_POST["dbHostName"] : NULL;
	$dbName = isset($_POST["dbName"]) ? $_POST["dbName"] : NULL;
	$dbUserName = isset($_POST["dbUserName"]) ? $_POST["dbUserName"] : NULL;
	$dbPassword = isset($_POST["dbPassword"]) ? $_POST["dbPassword"] : NULL;
	$orgName = isset($_POST["orgName"]) ? $_POST["orgName"] : NULL;
	$installCUEMS = isset($_POST["demoCUEMS"]) ? $_POST["demoCUEMS"] : NULL;
	$installStEMS = isset($_POST["demoStEMS"]) ? $_POST["demoStEMS"] : NULL;
	$newUserName = isset($_POST["newUserName"]) ? $_POST["newUserName"] : NULL;
	$newPassword = isset($_POST["newPassword"]) ? $_POST["newPassword"] : NULL;
	$passCode = isset($_POST["passCode"]) ? $_POST["passCode"] : NULL;
	if(!isset($dbHostName) || !isset($dbName) || !isset($dbUserName) ||
		!isset($dbPassword) || !isset($orgName) || !isset($newUserName) || !isset($newPassword)
		|| !isset($passCode)){
		print("Error loading parameters, please try and submit this form again.");
		return ;
	}
	print("Loading parameters...Done!<br/>");

	print("<br/>Saving database configuration...<br/>");
	$newValues = array("hostName" => $dbHostName,
		"databaseName" => $dbName,
		"userName" => $dbUserName,
		"password" => $dbPassword,
		"passCode" => $passCode,
		"orgName" => $orgName);
	Utils::editINIFile("config.ini.php", $newValues);
	print("Saving database configuration...Done!<br/>");

	print("<br/>Initializing Database...");
	$initResult = InitDatabase::initializeDatabase();
	if(count($initResult) != 0){
		print("<br/><br/>---Begin Errors---<br/>");
		print(implode("<br/><br/>", $initResult));
		print("<br/>---End Errors---<br/>");
	}
	print("<br/>Initializing Database...Done!<br/>");

	$database = Database::getInstance(true);
	require_once("lib/Security/Security.php");
	Security::disableSecurityIs(true);

	print("<br/>Installing default Fields...<br/>");
	try{ installDefaultFields(); }
	catch(Exception $e){
		print("Encountered error: $e, stopping.");
		return ;
	}
	print("Installing default Fields...Done!<br/>");

	print("<br/>Installing Editor Fields...<br/>");
	try{ installEditorFields(); }
	catch(Exception $e){
		print("Encountered error: $e, stopping.");
		return ;
	}
	print("Installing Editor Fields...Done!<br/>");

	print("<br/>Installing Administrator Role...<br/>");
	try{ createUserRoles("Administrator"); }
	catch(Exception $e){
		print("Encountered error: $e, stopping.");
		return ;
	}
	print("Installing Administrator Role...Done!<br/>");
	
	print("<br/>Installing Initial User...<br/>");
	try{ createInitialUser($newUserName, $newPassword); }
	catch(Exception $e){
		print("Encountered error: $e, stopping.");
		return ;
	}
	print("Installing Initial User...Done!<br/>");

	if($installCUEMS){
		print("<br/>Installing CUEMS Demo...<br/>");
		try{ installCUEMSDemo(); }
		catch(Exception $e){
			print("Encountered error: $e, stopping.");
			return ;
		}
		print("Installing CUEMS Demo...Done!<br/>");
	}
	elseif($installStEMS){
		print("<br/>Installing StEMS Demo...<br/>");
		try{ installStEMSDemo(); }
		catch(Exception $e){
			print("Encountered error: $e, stopping.");
			return ;
		}
		print("Installing StEMS Demo...Done!<br/>");
	}

	print("<br/>MEMS Installation complete");
?>

	</body>
</html>
