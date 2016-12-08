<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Exception.php");
	require_once("lib/Database.php");
	
	class InitDatabase{
		/// Creates the MEMS database and creates all of the necessary tables
		///		This function likely called by an installation script
		/// @return True on success, an array of error strings on failure
		static public function initializeDatabase(){
			$result = array();

			// Create user if not created (temporarily connect as root)
//			$tempConnection = mysqli_init();
//			if($tempConnection === false)
//				throw new MySQLException("Init  failure: {$tempConnection->error}");
//			if($tempConnection->real_connect($this->hostName(), "root", "") === false)
//				 throw new MySQLException("Connect failure: {$tempConnection->error}");

//			$createUserSQL = "CREATE USER '{$this->username()}'@'localhost' IDENTIFIED BY '{$this->password()}'";
//			$setPermissionSQL = "GRANT ALL PRIVILEGES ON *.* TO '{$this->username()}'@'localhost' WITH GRANT OPTION";
//			try{
//				Database::databaseQuery($createUserSQL, $tempConnection);
//				Database::databaseQuery($setPermissionSQL, $tempConnection);
//			}
//			catch(MySQLException $e){ $result[] = $e; }
			//$tempConnection->close();

			$database = Database::getInstance(false);
			// This allows us in-effect to "reset" the database for testing purposes
			try{ $database->query("DROP DATABASE {$database->databaseName()} "); }
			catch(MySQLException $e){ $result[] = $e; }

			try{ $database->query("CREATE DATABASE IF NOT EXISTS {$database->databaseName()} "); }
			catch(MySQLException $e){ $result[] = $e; }
			$database->selectDatabase();

			// Table to log all internal script errors (eg. mySQL query errors)
			// Type: The Exception type (eg. MySQL)
			// Message: Text string that was used to describe the esception (for MySQL exceptions, included query)
			// CallStack: Text String of the exception call stack
			$errorLogTable = "CREATE TABLE IF NOT EXISTS `ErrorLog` (
			`ErrorLogID` int(5) NOT NULL AUTO_INCREMENT,
			`CallStack` text NOT NULL,
			`Message` text NOT NULL,
			`Type` varchar(50) NOT NULL,
			PRIMARY KEY (`ErrorLogID`)
			) ENGINE=InnoDB";
			try{ $database->query($errorLogTable); }
			catch(MySQLException $e){ $result[] = $e; }

			// This table is used by the ZebraSession class to store all session data
			//  in the MySQL server instead of as files on the server.  This is useful in
			//  environments such as Stanford's, where a single client may be handed-off
			//  between multiple physical servers during any one session
			$sessionDataTable = "CREATE TABLE `SessionData` (
			  `SessionID` varchar(32) NOT NULL default '',
			  `http_user_agent` varchar(32) NOT NULL default '',
			  `session_data` blob NOT NULL,
			  `session_expire` int(11) NOT NULL default '0',
			  PRIMARY KEY  (`SessionID`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8";
			try{ $database->query($sessionDataTable); }
			catch(MySQLException $e){ $result[] = $e; }

			// Groups ModuleFields together
			// Name: Name of the module
			// Removable: Whether or not a particular module should be removable by the user.
			//  Only used for pre-installed Modules, such as "Member", where other systems
			//  rely on its existance
			// Hidden: Similar to Removable, but used to hide modules we don't want the
			//  user messing with.
			$moduleTable = "CREATE TABLE IF NOT EXISTS `Module` (
			`ModuleID` int(5) NOT NULL AUTO_INCREMENT,
			`Name` varchar(20) NOT NULL,
			`Removable` bit(1) NOT NULL,
			`Hidden` bit(1) NOT NULL,
			PRIMARY KEY (`ModuleID`),
			UNIQUE KEY (`Name`)
			) ENGINE=InnoDB";
			try{ $database->query($moduleTable); }
			catch(MySQLException $e){ $result[] = $e; }

			// An instance of a Module.  Equivalent to a row in a spreadsheet
			$moduleInstanceTable = "CREATE TABLE IF NOT EXISTS `ModuleInstance` (
			`ModuleInstanceID` int(5) NOT NULL AUTO_INCREMENT,
			`ModuleID` int(5) NOT NULL,
			PRIMARY KEY (`ModuleInstanceID`),
			FOREIGN KEY (ModuleID) REFERENCES Module(ModuleID)
				ON DELETE CASCADE
			) ENGINE=InnoDB";
			try{ $database->query($moduleInstanceTable); }
			catch(MySQLException $e){ $result[] = $e; }

			// ForceLogin: Number of seconds that may have passed at most since logon without forcing another log-in.
			//	A value of 0 disables enforcement.
			// Removable: Whether this page can be removed through the in-browser editor.
			//	Presumably only special forms, such as log-in, change password, etc.
			//	will set this bit, and it should probably not be a user option
			// PageTitle: The Text that should be shown in both the browser title bar, and
			//  at the top of each page.
			$pageTable = "CREATE TABLE IF NOT EXISTS `Page` (
			`PageName` varchar(20) NOT NULL,
			`PageTitle` varchar(30) NOT NULL,
			`Removable` bit(1) NOT NULL,
			`ModuleID` int(50) NOT NULL,
			`Description` TEXT,
			`ForceLogin` int(5) NOT NULL,
			FOREIGN KEY (ModuleID) REFERENCES Module(ModuleID)
				ON DELETE CASCADE,
			PRIMARY KEY (`PageName`)
			) ENGINE=InnoDB";
			try{ $database->query($pageTable); }
			catch(MySQLException $e){ $result[] = $e; }

			// NavMenuName: Effectively groups NavLinks into a single menu
			// Text: Text string that the user will see
			// PageName: Page that we will link to (if first two chars are "##" -> treat as a URL)
			// ModuleInstanceID: Optional setting to load a particular instance
			// Position: Where, from left to right, this link will exist
			// Group: If one is included, will place this entry into a group (ie. drop-down list)
			$navLinkTable = "CREATE TABLE IF NOT EXISTS `NavLink` (
			`NavLinkID` int(5) NOT NULL AUTO_INCREMENT,
			`NavMenuName` varchar(20) NOT NULL,
			`Text` varchar(20) NOT NULL,
			`PageName` TEXT NOT NULL,
			`ModuleInstanceID` int(5),
			`Position` int(5) NOT NULL,
			`Group` varchar(20),
			PRIMARY KEY (`NavLinkID`),
			FOREIGN KEY (ModuleInstanceID) REFERENCES ModuleInstance(ModuleInstanceID)
			) ENGINE=InnoDB";
			try{ $database->query($navLinkTable); }
			catch(MySQLException $e){ $result[] = $e; }

			// Forms group together FormFields, and must be associated with exactly
			//  one Page in order to be useful.
			$formTable = "CREATE TABLE IF NOT EXISTS `Form` (
			`FormID` int(5) NOT NULL AUTO_INCREMENT,
			`PageName` varchar(20) NOT NULL,
			PRIMARY KEY (`FormID`),
			FOREIGN KEY (PageName) REFERENCES Page(PageName)
				ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB";
			try{ $database->query($formTable); }
			catch(MySQLException $e){ $result[] = $e; }

			// Regex: A pattern the input must be matched against to be accepted
			// DefaultValue: Value present in form before user input.  For static fields,
			//  this value is used for all instances.
			// Label: A short string describing the field, used in Listings and Forms
			// Type: Eg. Text, Date, etc..  All types can be found in the fieldtypes folder
			// Hidden: Whether this Def is shown on the form-creator form (and similarly, whether it can be deleted)
			// Unique: Each module needs to have exactly one field designated as "Unique",
			//  to be used by fields such as PageLink, where an integer is not a useful value
			$moduleFieldTable = "CREATE TABLE IF NOT EXISTS `ModuleField` (
			`ModuleFieldID` int(5) NOT NULL AUTO_INCREMENT,
			`ModuleID` int(50) NOT NULL,
			`Name` varchar(20) NOT NULL,
			`Type` varchar(30) NOT NULL,
			`Description` TEXT NOT NULL,
			`Regex` varchar(200) NOT NULL,
			`DefaultValue` varchar(200) NOT NULL,
			`Label` varchar(20) NOT NULL,
			`Hidden` bit(1) NOT NULL,
			`Unique` bit(1) NOT NULL,
			PRIMARY KEY (`ModuleFieldID`),
			FOREIGN KEY (ModuleID) REFERENCES Module(ModuleID)
				ON DELETE CASCADE
			) ENGINE=InnoDB";
			try{ $database->query($moduleFieldTable); }
			catch(MySQLException $e){ $result[] = $e; }

			// One can think of this table as providing a set of key->value
			//	pairs for fields.  Field types such as combo-boxes will use each entry
			//	as an entry in their option list, while the PageLink uses it to store
			//	the link's resulting page name.
			$fieldDefOptionTable = "CREATE TABLE IF NOT EXISTS `ModuleFieldOption` (
			`ModuleFieldOptionID` int(5) NOT NULL AUTO_INCREMENT,
			`OptionValue` TEXT NOT NULL,
			`OptionLabel` varchar(50) NOT NULL,
			`ModuleFieldID` int(5) NOT NULL,
			PRIMARY KEY (`ModuleFieldOptionID`),
			FOREIGN KEY (ModuleFieldID) REFERENCES ModuleField(ModuleFieldID)
				ON DELETE CASCADE
			) ENGINE=InnoDB";
			try{ $database->query($fieldDefOptionTable); }
			catch(MySQLException $e){ $result[] = $e; }

			// Fields and their properties on a form
			// Mutable: Whether this field can be edited; otherwise the value is shown as static text.
			//	Note that depending on privileges and other variables, this may not be the final
			//	state in every instance.
			// Removable: Whether this field can be removed from a form (such as the password field during registration)
			// Pos_Left/Pos_Top: Distance (in pixels) from the left or top of the form container.
			// Pos_Width/Pos_Height: Dimensions (in pixels) of the field itself (ie. not including the label).
			//  Some fields (Such as the TextBox) ignore the height, while others (such as the CAPTCHA) ignore both.
			$formFieldsTable = "CREATE TABLE IF NOT EXISTS `FormField` (
			`FormFieldID` int(5) NOT NULL AUTO_INCREMENT,
			`ModuleFieldID` int(5) NOT NULL,
			`FormID` int(5) NOT NULL,
			`Pos_Top` int(5) NOT NULL,
			`Pos_Left` int(5) NOT NULL,
			`Pos_Width` int(5) NOT NULL,
			`Pos_Height` int(5) NOT NULL,
			`Mutable` bit(1) NOT NULL,
			`IncludeLabel` bit(1) NOT NULL DEFAULT b'1',
			`Removable` bit(1) NOT NULL,
			PRIMARY KEY (`FormFieldID`),
			FOREIGN KEY (ModuleFieldID) REFERENCES ModuleField(ModuleFieldID)
				ON DELETE CASCADE,
			FOREIGN KEY (FormID) REFERENCES Form(FormID)
				ON DELETE CASCADE
			) ENGINE=InnoDB";
			try{ $database->query($formFieldsTable); }
			catch(MySQLException $e){ $result[] = $e; }

			// Value: The content of this ModuleFieldInstance.  Note that different field types
			//	may encode this data differently than straight text.  All data in Value is
			// stored encrypted using a user-specified key.
			$fieldValuesTable = "CREATE TABLE IF NOT EXISTS `ModuleFieldInstance` (
			`ModuleFieldInstanceID` int(5) NOT NULL AUTO_INCREMENT,
			`ModuleFieldID` int(5) NOT NULL,
			`ModuleInstanceID` int(5) NOT NULL,
			`Value` BLOB NOT NULL,
			PRIMARY KEY (`ModuleFieldInstanceID`),
			FOREIGN KEY (ModuleInstanceID) REFERENCES ModuleInstance(ModuleInstanceID)
				ON DELETE CASCADE,
			FOREIGN KEY (ModuleFieldID) REFERENCES ModuleField(ModuleFieldID)
				ON DELETE CASCADE,
			UNIQUE KEY (`ModuleFieldID`, `ModuleInstanceID`)
			) ENGINE=InnoDB";
			try{ $database->query($fieldValuesTable); }
			catch(MySQLException $e){ $result[] = $e; }

			// PageName: String to be shown on both the browser title bar, and the title of the listing.
			// CreateText: The text to be used as button to instigate the creation
			//  of new instances of the applicable module.
			// NewEntryPageName: The page the user should be brought to upon clicking the
			//  "NewEntry" button.
			// MaxItems: Maximum number of items in the form (note items==Field | Group)
			$listTable = "CREATE TABLE IF NOT EXISTS `Listing` (
			`ListingID` int(5) NOT NULL AUTO_INCREMENT,
			`PageName` varchar(20) NOT NULL,
			`MaxItems` int(5) NOT NULL,
			`NewEntryPageName` varchar(20),
			`CreateText` varchar(20) NOT NULL,
			PRIMARY KEY (`ListingID`),
			FOREIGN KEY (PageName) REFERENCES Page(PageName)
				ON DELETE CASCADE,
			FOREIGN KEY (NewEntryPageName) REFERENCES Page(PageName)
				ON DELETE CASCADE
			) ENGINE=InnoDB";
			try{ $database->query($listTable); }
			catch(MySQLException $e){ $result[] = $e; }

			// Rank: Which group-by is applied first, second, etc.  Ranks need be unique
			//  among any given Listing.
			// Direction: 1 -> Ascending; 0 -> descending
			// Type: 0->SortBy; 1->GroupBy
			// Orientation: 0-> Sub-items are displayed horizontally and the group is repeated vertically.
			$listByTable= "CREATE TABLE IF NOT EXISTS `ListBy` (
				`ListByID` int(5) NOT NULL AUTO_INCREMENT,
				`Rank` int(5) NOT NULL,
				`ModuleFieldID` int(5) NOT NULL,
				`ListingID` int(5) NOT NULL,
				`Direction` bit(1) NOT NULL,
				`Orientation` bit(1) NOT NULL,
				`Type` bit(1) NOT NULL,
			PRIMARY KEY (`ListByID`),
			FOREIGN KEY (ModuleFieldID) REFERENCES ModuleField(ModuleFieldID)
				ON DELETE CASCADE,
			FOREIGN KEY (ListingID) REFERENCES Listing(ListingID)
				ON DELETE CASCADE
			) ENGINE=InnoDB";
			try{ $database->query($listByTable); }
			catch(MySQLException $e){ $result[] = $e; }

			// Value: Some used to restrict the module instances displayed on the listing.
			//  Each module field may interpret this value slightly differently.
			$listFilterTable= "CREATE TABLE IF NOT EXISTS `ListFilter` (
				`ListFilterID` int(5) NOT NULL AUTO_INCREMENT,
				`ModuleFieldID` int(5) NOT NULL,
				`ListingID` int(5) NOT NULL,
				`Value` varchar(50) NOT NULL,
			PRIMARY KEY (`ListFilterID`),
			FOREIGN KEY (ModuleFieldID) REFERENCES ModuleField(ModuleFieldID)
				ON DELETE CASCADE,
			FOREIGN KEY (ListingID) REFERENCES Listing(ListingID)
				ON DELETE CASCADE
			) ENGINE=InnoDB";
			try{ $database->query($listFilterTable); }
			catch(MySQLException $e){ $result[] = $e; }

			// Each entry in this table represents a module field we want to show in the listing
			// ModuleFieldID: If=NULL, then this field is a down-down menu that uses the ListOptions
			// IncludeLabel: 1->Include the field label next to the field value
			// Position: In what order the fields will be displayed.  Should be unique
			// LinkPageName: If not NULL, the page the user should be brought to after
			//  clicking on this field.
			// Width: Proportional width to the other ListFields.  For example, two
			//  fields with widths 1,2 will be displayed the same as two fields with widths 2,4.
			// TODO: Move "Label" out of the Module, and in to both FormField and ListField
			$listFieldTable= "CREATE TABLE IF NOT EXISTS `ListField` (
				`ListFieldID` int(5) NOT NULL AUTO_INCREMENT,
				`Position` int(5) NOT NULL,
				`ModuleFieldID` int(5),
				`ListingID` int(5) NOT NULL,
				`IncludeLabel` bit(1) NOT NULL,
				`Width` int(5) NOT NULL,
				`LinkPageName` varchar(20),
			PRIMARY KEY (`ListFieldID`),
			FOREIGN KEY (ModuleFieldID) REFERENCES ModuleField(ModuleFieldID)
				ON DELETE CASCADE,
			FOREIGN KEY (ListingID) REFERENCES Listing(ListingID)
				ON DELETE CASCADE,
			FOREIGN KEY (LinkPageName) REFERENCES Page(PageName)
				ON DELETE CASCADE
			) ENGINE=InnoDB";
			try{ $database->query($listFieldTable); }
			catch(MySQLException $e){ $result[] = $e; }

			// Represents the items that will appear in the options list for each list entry
			//	These are shown in place of a ListField when its ModuleFieldID == NULL
			// PageName: The Page the user should be brought to after clicking this entry
			// Title: The text to be displayed for a given page link; special case: 'Delete'
			$listOptionTable= "CREATE TABLE IF NOT EXISTS `ListOption` (
				`ListOptionID` int(5) NOT NULL AUTO_INCREMENT,
				`PageName` varchar(20),
				`ListingID` int(5) NOT NULL,
				`Title` varchar(20) NOT NULL,
			PRIMARY KEY (`ListOptionID`),
			FOREIGN KEY (PageName) REFERENCES Page(PageName)
				ON DELETE CASCADE,
			FOREIGN KEY (ListingID) REFERENCES Listing(ListingID)
				ON DELETE CASCADE
			) ENGINE=InnoDB";
			try{ $database->query($listOptionTable); }
			catch(MySQLException $e){ $result[] = $e; }

			//****************************************************//
			//*************Permissions****************************//
			//****************************************************//

			// Defines all of the avilable roles to choose from
			$rolesTable = "CREATE TABLE IF NOT EXISTS `Role` (
				`RoleID` int(5),
				`RoleName` varchar(20),
				`Description` TEXT,
				PRIMARY KEY (`RoleID`)
			) ENGINE=InnoDB";
			try{ $database->query($rolesTable); }
			catch(MySQLException $e){ $result[] = $e; }

			// Stores the privileges associated with each role for each module field
			// Task: See the ModuleFieldPrivilege class for a current list of valid tasks.
			$mfPrivilegeTable = "CREATE TABLE IF NOT EXISTS `MFPrivilege` (
				`MFPID` int(5) NOT NULL AUTO_INCREMENT,
				`ModuleFieldID` int(5),
				`RoleID` int(5),
				`Task` varchar(20),
				PRIMARY KEY (`MFPID`),
				UNIQUE KEY(`ModuleFieldID`, `RoleID`, `Task`),
				FOREIGN KEY (ModuleFieldID) REFERENCES ModuleField(ModuleFieldID)
					ON DELETE CASCADE,
				FOREIGN KEY (RoleID) REFERENCES Role(RoleID)
					ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB";
			try{ $database->query($mfPrivilegeTable); }
			catch(MySQLException $e){ $result[] = $e; }

			// Gives a user some role.  A user may have multiple roles, or none.
			$userRoleTable = "CREATE TABLE IF NOT EXISTS `UserRole` (
				`RoleID` int(5),
				`UserMIID` int(5),
				PRIMARY KEY (`RoleID`, `UserMIID`),
				FOREIGN KEY (UserMIID) REFERENCES ModuleInstance(ModuleInstanceID)
					ON DELETE CASCADE,
				FOREIGN KEY (RoleID) REFERENCES Role(RoleID)
					ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB";
			try{ $database->query($userRoleTable); }
			catch(MySQLException $e){ $result[] = $e; }

			// Specifies what a role is allowed to do with regard to a module
			// Task: See the ModulePrivilege class for a current list of valid tasks.
			// PENDING: Probably can just remove the Option attribute
			$modulePrivilegeTable = "CREATE TABLE IF NOT EXISTS `ModulePrivilege` (
				`ModulePrivilegeID` int(5) NOT NULL AUTO_INCREMENT,
				`RoleID` int(5),
				`ModuleID` int(5),
				`Task` varchar(30),
				`Option` TEXT,
				PRIMARY KEY (`ModulePrivilegeID`),
				UNIQUE KEY(`RoleID`, `ModuleID`, `Task`),
				FOREIGN KEY (ModuleID) REFERENCES Module(ModuleID)
					ON DELETE CASCADE,
				FOREIGN KEY (RoleID) REFERENCES Role(RoleID)
					ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB";
			try{ $database->query($modulePrivilegeTable); }
			catch(MySQLException $e){ $result[] = $e; }

			// Task: See the GeneralPrivilege class for a current list of valid tasks.
			$generalPermissionTable = "CREATE TABLE IF NOT EXISTS `GeneralPrivilege` (
				`GeneralPrivilegeID` int(5) NOT NULL AUTO_INCREMENT,
				`RoleID` int(5),
				`Task` varchar(30),
				PRIMARY KEY (`GeneralPrivilegeID`),
				UNIQUE KEY(`RoleID`, `Task`),
				FOREIGN KEY (RoleID) REFERENCES Role(RoleID)
					ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB";
			try{ $database->query($generalPermissionTable); }
			catch(MySQLException $e){ $result[] = $e; }

			// When a user should be allowed write access to only a single instance,
			//	they can temporarily "borrow" another role for it
			// UserMIID: The ModuleInstanceID for the user in question
			// ModuleInstanceID: The ID of the instance we are allowing this user to
			//  have increased access to.
			// RoleID: The ID of the role we are giving to this user.
			$instancePrivilegeTable = "CREATE TABLE IF NOT EXISTS `InstancePrivilege` (
				`InstancePrivilegeID` int(5) NOT NULL AUTO_INCREMENT,
				`RoleID` int(5),
				`UserMIID` int(5),
				`ModuleInstanceID` int(5),
				PRIMARY KEY (`InstancePrivilegeID`),
				FOREIGN KEY (UserMIID) REFERENCES ModuleInstance(ModuleInstanceID)
					ON DELETE CASCADE,
				FOREIGN KEY (ModuleInstanceID) REFERENCES ModuleInstance(ModuleInstanceID)
					ON DELETE CASCADE,
				FOREIGN KEY (RoleID) REFERENCES Role(RoleID)
					ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB";
			try{ $database->query($instancePrivilegeTable); }
			catch(MySQLException $e){ $result[] = $e; }

			return $result;
		}
	}
?>
