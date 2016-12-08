<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("install/InitDatabase.php");
	
	function installDemoFields(){
		$defaultEntries = array(
			"INSERT INTO `mems`.`module`
				(`ModuleID`, `Name`, `Removable`, `Hidden`) VALUES
				(NULL, 'Member', b'0', b'0'),
				(NULL, 'Event', b'0', b'0')",
			"INSERT INTO `mems`.`page`
				(`PageName`, `PageTitle`, `Removable`, `ModuleID`, `ForceLogin`) VALUES
				('NewReg', 'New Registration', b'0', '1', '0'),
				('Profile', 'Profile', b'0', '1', '0'),
				('ChangePW', 'Change Password', b'0', '1', '0'),
				('Logon', 'Logon', b'0', '1', '0'),
				('MemberList', 'Member List', b'1', '1', '0'),
				('LogOut', 'Log Out', b'0', '1', '15')",
			"INSERT INTO `mems`.`navlink` (`NavLinkID`, `NavMenuName`, `Text`, `PageName`, `ModuleInstanceID`, `Position`, `Group`) VALUES
				(NULL, 'Default', 'Main', 'NewReg', NULL, '1', NULL),
				(NULL, 'Default', 'Member List', 'MemberList', NULL, '2', NULL),
				(NULL, 'Default', 'Event List', 'MemberList', NULL, '3', NULL),
				(NULL, 'Default', 'Modules', '##ModuleCreator.php', NULL, '5', 'Administration'),
				(NULL, 'Default', 'Forms', 'NewReg', NULL, '6', 'Administration'),
				(NULL, 'Default', 'Permissions', '##Permissions.php', NULL, '6', 'Administration'),
				(NULL, 'Default', 'Log Out', 'LogOut', NULL, '7', NULL),
				(NULL, 'Default', 'CUEMS', '##http://cuems.cornell.edu', NULL, '8', NULL)
				",
			"INSERT INTO `mems`.`form`
				(`FormID`, `PageName`) VALUES
				(NULL, 'NewReg'),
				(NULL, 'Profile'),
				(NULL, 'Logon'),
				(NULL, 'ChangePW'),
				(NULL, 'LogOut')",
			"INSERT INTO `mems`.`ModuleField`
				(`ModuleFieldID`, `ModuleID`, `Name`, `Type`, `Description`, `Regex`, `DefaultValue`, `Label`, `Hidden`, `Unique`) VALUES
				('1', '1', 'FirstName', 'Text', 'First Name of the member.', '/^[a-zA-Z-\s]{1,40}$/s', '', 'First Name', b'0', b'0'),
				('2', '1', 'LastName', 'Text', 'Last Name of the member.', '/^[a-zA-Z-\s]{1,40}$/s', '', 'Last Name', b'0', b'0'),
				('3', '1', 'UserName', 'Text', 'Member-chosen user ID.', '/^.{1,10}$/s', '', 'User Name:', b'1', b'1'),
				('4', '1', 'PhoneNumber', 'Text', 'Phone number', '/^\\\([0-9]{3}\\\)[0-9]{3}-[0-9]{4}$/s', '', 'Phone Number', b'0', b'0'),
				('5', '1', 'Email', 'Text', 'Email Address', '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/s', '', 'Email Address', b'0', b'0'),
				('6', '1', 'Password', 'Password', 'User\'s secret access code.', '/(?!^[0-9]*$)(?!^[a-zA-Z]*$)^([a-zA-Z0-9]{7,14})$/s', '', 'Password', b'1', b'0'),
				('7', '1', 'CurrentCookie', 'Date', 'Hash of the cookie value sent to the user if they requested to be remembered during last last successful logon.', '/.*/s', '', 'Cookie', b'1', b'0'),
				('8', '1', 'CurrrentSession', 'Static', 'Session ID of current session.', '/.*/s', '', 'Session ID', b'1', b'0'),
				('9', '1', 'IPAddress', 'Static', 'Current IP address of the member.', '/.*/s', '', 'IP Address', b'1', b'0'),
				('10', '1', 'EMTExp', 'Date', 'Date of EMT-B certification expiration.', '/.*/s', '', 'EMT-B Expiration', b'0', b'0'),
				('11', '1', 'TextTest', 'TextArea', 'Multi-line text.', '/^[a-zA-Z-\s]{1,40}$/s', '', 'Text Test', b'0', b'0'),
				('12', '1', 'EMTBCert', 'CheckBox', 'EMT-B Certified', '/.*/s', '', 'EMT-B Certification', b'0', b'0'),
				('13', '1', 'Captcha', 'Captcha', 'CAPTCHA', '', '', 'Captcha', b'0', b'0'),
				('14', '1', 'ProfilePictureUpload', 'FileUpload', 'Profile Picture Upload', '/.*/s', '', 'Profile Picture', b'0', b'0'),
				('15', '1', 'Rank', 'SelectEdit', 'Rank withing the group', '/.*/s', '', 'Rank', b'0', b'0'),
				('16', '1', 'Preceptor', 'PageLink', 'Official instructor of this member.', '/.*/s', '', 'Preceptor', b'0', b'0'),
				('17', '1', 'Roles', 'SelectRoles', 'Privilege sets this member is a part of.', '/.*/s', '', 'Roles', b'0', b'0')",
			"INSERT INTO `mems`.`formfield`
				(`FormFieldID`, `ModuleFieldID`, `FormID`, `Pos_Top`, `Pos_Left`, `Pos_Width`, `Pos_Height`, `Mutable`, `Removable`) VALUES
				(NULL, '1', '2', '0', '0', '30', '1', b'1', b'1'),
				(NULL, '2', '2', '0', '0', '30', '1', b'1', b'1'),
				(NULL, '3', '2', '0', '0', '30', '1', b'0', b'0'),
				(NULL, '10', '2', '0', '0', '30', '1', b'1', b'1'),
				(NULL, '4', '2', '0', '0', '30', '1', b'1', b'0'),
				(NULL, '5', '2', '0', '0', '30', '1', b'1', b'0'),
				(NULL, '11', '2', '0', '0', '100', '30', b'1', b'1'),
				(NULL, '12', '2', '0', '0', '30', '1', b'1', b'1'),
				(NULL, '14', '2', '0', '0', '30', '7', b'1', b'1'),
				(NULL, '15', '2', '0', '0', '30', '2', b'1', b'1'),
				(NULL, '16', '2', '0', '0', '30', '1', b'1', b'1'),
				(NULL, '17', '2', '0', '0', '30', '100', b'1', b'0'),

				(NULL, '3', '1', '0', '0', '30', '1', b'1', b'0'),
				(NULL, '13', '1', '0', '0', '30', '2', b'1', b'0'),
				
				(NULL, '3', '3', '0', '0', '30', '1', b'1', b'0'),
				(NULL, '6', '3', '0', '0', '30', '1', b'1', b'0')",
			"INSERT INTO `mems`.`modulefieldoption`
				(`ModuleFieldOptionID`, `OptionValue`, `OptionLabel`, `ModuleFieldID`) VALUES
				(NULL, 'Crew Chief', 'Crew Chief', '15'),
				(NULL, 'Attendant', 'Attendant', '15'),
				(NULL, 'Trainee', 'Trainee', '15'),
				(NULL, 'Retired', 'Retired', '15'),
				(NULL, 'Leave of Absence', 'Leave of Absence', '15'),
				
				(NULL, 'Profile', 'PageName', '16'),
				(NULL, '1', 'ModuleID', '16'),

				(NULL, '2', 'MaxFileCount', '14'),
				(NULL, '1', 'ShowPreview', '14'),
				(NULL, '1000000', 'MaxSize', '14')",
			"INSERT INTO `mems`.`listing`
				(`ListingID`, `PageName`, `MaxItems`, `NewEntryPageName`) VALUES
				(NULL, 'MemberList', '10', 'NewReg')",
			"INSERT INTO `mems`.`listfield`
				(`ListFieldID`, `Position`, `ModuleFieldID`, `ListingID`, `IncludeLabel`, `LinkPageName`, `Width`) VALUES
				(NULL, '1', '1', '1', b'0', 'Profile', '1'),
				(NULL, '2', '2', '1', b'0', NULL, '1'),
				(NULL, '3', '3', '1', b'0', NULL, '1'),
				(NULL, '4', '4', '1', b'0', NULL, '1'),
				(NULL, '5', '5', '1', b'0', NULL, '1'),
				(NULL, '6', NULL, '1', b'0', NULL, '1')",
			"INSERT INTO `mems`.`listby`
				(`ListByID`, `Rank`, `ModuleFieldID`, `ListingID`, `Direction`, `Orientation`, `Type`) VALUES
				(NULL, '1', '1', '1', b'1', b'0', b'0')",
			"INSERT INTO `mems`.`listoption`
				(`ListOptionID`, `PageName`, `ListingID`, `Title`) VALUES
				(NULL, 'Profile', '1', 'View'),
				(NULL, 'Profile', '1', 'Edit'),
				(NULL, NULL, '1', 'Delete')",
			"INSERT INTO `mems`.`listfilter`
				(`ListFilterID`, `ModuleFieldID`, `ListingID`, `Value`) VALUES
				(NULL, '2', '1', 'value:Ulansey')",
			"INSERT INTO `Role`
				(`RoleID`, `RoleName`, `Description`) VALUES
				('1', 'President', 'Given full access to all parts of the site.  Has every privilege.'),
				('2', 'BLS Coordinator', 'May designate roles for other members, and modify the members Module.'),
				('3', 'Dir of Operations', 'Full control over events module.')"
			);
			$tempFields = array(
				"INSERT INTO `moduleinstance` (`ModuleInstanceID`, `ModuleID`) VALUES
					(1, 1),
					(3, 1),
					(4, 1),
					(5, 1)",
				"INSERT INTO `modulefieldinstance` (`ModuleFieldInstanceID`, `ModuleFieldID`, `ModuleInstanceID`, `Value`) VALUES
					(1, 1, 1, 'Glenn'),
					(2, 2, 1, 'Ulansey'),
					(3, 3, 1, 'gulansey'),
					(4, 4, 1, '(707)498-2711'),
					(5, 5, 1, 'n64wizzard@gol.com'),
					(6, 6, 1, 'password'),
					(7, 7, 1, '03/03/2011'),
					(8, 8, 1, ''),
					(9, 9, 1, ''),
					(10, 1, 3, 'Bob'),
					(11, 2, 3, 'Ulansey'),
					(12, 3, 3, 'bulansey'),
					(13, 4, 3, '(707)498-2711'),
					(14, 5, 3, 'n64wizzard@gol.com'),
					(15, 6, 3, '1234567'),
					(16, 7, 3, '03/03/2011'),
					(17, 8, 3, ''),
					(18, 9, 3, ''),
					(19, 1, 4, 'Cathy'),
					(20, 2, 4, 'Ulansey'),
					(21, 3, 4, 'culansey'),
					(22, 4, 4, '(707)498-7285'),
					(23, 5, 4, 'culansey@aol.com'),
					(24, 6, 4, 'password'),
					(25, 7, 4, '03/03/2011'),
					(26, 8, 4, ''),
					(27, 9, 4, ''),
					(28, 1, 5, 'David'),
					(29, 2, 5, 'Robson'),
					(30, 3, 5, 'suppleco'),
					(31, 4, 5, '(345)533-3894'),
					(32, 5, 5, 'supplecoder@aol.com'),
					(33, 6, 5, 'Password'),
					(34, 7, 5, '03/03/2011'),
					(35, 8, 5, ''),
					(36, 9, 5, ''),
					(37, 11, 1, 'hey-o'),
					(38, 12, 1, ''),
					(39, 13, 1, ''),
					(40, 16, 1, '3')",
			);
			$creatorEntries = array(
			"INSERT INTO `mems`.`module`
				(`ModuleID`, `Name`, `Removable`, `Hidden`) VALUES
				(20, 'ModuleCreator', b'0', b'1')",
			"INSERT INTO `mems`.`page`
				(`PageName`, `PageTitle`, `Removable`, `ModuleID`, `ForceLogin`) VALUES
				('ModuleCreator', 'Module Creator', b'0', '20', '0')",
			"INSERT INTO `mems`.`form`
				(`FormID`, `PageName`) VALUES
				(20, 'ModuleCreator')",
			"INSERT INTO `mems`.`ModuleField`
				(`ModuleFieldID`, `ModuleID`, `Name`, `Type`, `Description`, `Regex`, `DefaultValue`, `Label`, `Hidden`, `Unique`) VALUES
				(20, '20', 'Name', 'Text', 'Name of the new field.', '/.*/s', '', 'Name', b'0', b'0'),
				(21, '20', 'Type', 'Select', 'Type of the new field.', '/.*/s', '', 'Type', b'0', b'0'),
				(22, '20', 'Description', 'Text', 'Description of the new field.', '/.*/s', '', 'Description', b'0', b'0'),
				(23, '20', 'Regex', 'Text', 'Regular expression for the new field.', '/.*/s', '', 'Regex', b'0', b'0'),
				(24, '20', 'DefaultValue', 'Text', 'Default value for the new field.', '/.*/s', '', 'DefaultValue', b'0', b'0'),
				(25, '20', 'Label', 'Text', 'Label for the new field.', '/.*/s', '', 'Label', b'0', b'0'),
				(26, '20', 'ModuleName', 'Select', 'Name of the module to create/edit.', '/.*/s', '', 'Module Name', b'0', b'0')",
			"INSERT INTO `mems`.`formfield`
				(`FormFieldID`, `ModuleFieldID`, `FormID`, `Pos_Top`, `Pos_Left`, `Pos_Width`, `Pos_Height`, `Mutable`, `Removable`) VALUES
				(20, 20, 20, '0', '0', '30', '1', b'1', b'1'),
				(21, 21, 20, '0', '0', '30', '1', b'1', b'1'),
				(22, 22, 20, '0', '0', '30', '1', b'1', b'1'),
				(23, 23, 20, '0', '0', '30', '1', b'1', b'1'),
				(24, 24, 20, '0', '0', '30', '1', b'1', b'1'),
				(25, 25, 20, '0', '0', '30', '1', b'1', b'1')",
			"INSERT INTO `moduleinstance` (`ModuleInstanceID`, `ModuleID`) VALUES
				(20, 20)"
			);
		try{
			foreach($defaultEntries as $defaultQuery)
				Database::getInstance()->query($defaultQuery);
			foreach($tempFields as $tempField)
				Database::getInstance()->query($tempField);
			foreach($creatorEntries as $creatorEntry)
				Database::getInstance()->query($creatorEntry);
		}
		catch(MySQLException $e){ print("Encountered error: $e, stopping."); }
	}

	print("Initializing Database...");
	$initResult = InitDatabase::initializeDatabase();
	if(count($initResult) != 0){
		print("<br/><br/>---Begin Errors---<br/>");
		print(implode("<br/><br/>", $initResult));
		print("<br/>---End Errors---<br/>");
	}
	$database = Database::getInstance(true);
	print("<br/>Initializing Database...Done!<br/>");
	print("<br/>Installing default Fields...<br/>");
	installDemoFields();
	print("<br/>Installing default Fields...Done!<br/>");
?>
