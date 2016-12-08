<?php
	/// This page updates the default installation to provide an example of the functionality of the MEMS
	///  system.  It is designed to be similar in functionality to what one would hope to use at StEMS.
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("lib/Utils.php");
	require_once("install/InitDatabase.php");

	function installStEMSDemo(){
		// Create modules
		$defaultEntries = array(
			"INSERT INTO `module`
				(`ModuleID`, `Name`, `Removable`, `Hidden`) VALUES
				(5, 'CrewMember', b'1', b'0'),
				(6, 'PCR', b'1', b'0'),
				(7, 'Event', b'1', b'0')",
			"INSERT INTO `page`
				(`PageName`, `PageTitle`, `Removable`, `ModuleID`, `ForceLogin`, `Description`) VALUES
				('PCR', 'Patient Care Report', b'1', '6', '3600', 'Form to edit or create a new PCR.'),
				('PCRList', 'PCRs', b'1', '6', '3600', 'Listing of PCRs.'),
				('EventList', 'Event List', b'1', '7', '3600', 'A listing of Events.'),
				('Event', 'Event', b'1', '7', '3600', 'Form to edit the details of an event.'),
				('CrewMember', 'Crew Member', b'1', '5', '3600', 'Form to sign-up for a shift.'),
				('CrewList', 'Crews', b'1', '5', '3600', 'Listing members signed-up for an event.')",
			"DELETE FROM `navlink`",
			"INSERT INTO `navlink` (`NavLinkID`, `NavMenuName`, `Text`, `PageName`, `ModuleInstanceID`, `Position`, `Group`) VALUES
				(NULL, 'Default', 'Member List', 'MemberList', NULL, '2', NULL),
				(NULL, 'Default', 'Events', 'EventList', NULL, '3', NULL),
				(NULL, 'Default', 'PCRs', 'PCRList', NULL, '4', NULL),
				(NULL, 'Default', 'Modules', '##ModuleCreator.php', NULL, '7', 'Administration'),
				(NULL, 'Default', 'Forms', '##FormEditor.php', NULL, '8', 'Administration'),
				(NULL, 'Default', 'Listings', '##ListingEditor.php', NULL, '9', 'Administration'),
				(NULL, 'Default', 'Permissions', '##Permissions.php', NULL, '10', 'Administration'),
				(NULL, 'Default', 'Log Out', 'LogOut', NULL, '11', NULL),
				(NULL, 'Default', 'StEMS', '##http://stems.stanford.edu', NULL, '12', NULL)",
			"INSERT INTO `form`
				(`FormID`, `PageName`) VALUES
				(50, 'PCR'),
				(51, 'Event'),
				(52, 'CrewMember')",
			"INSERT INTO `ModuleField`
				(`ModuleFieldID`, `ModuleID`, `Name`, `Type`, `Description`, `Regex`, `DefaultValue`, `Label`, `Hidden`, `Unique`) VALUES
				('50', '1', 'CPRCert', 'Date', 'Expiration of CPR certification.', '/^.*$/s', '', 'CPR Expiration', b'0', b'0'),
				('51', '1', 'EMTCert', 'Date', 'Expiration of EMT certification.', '/^.*$/s', '', 'EMT-B Expiration', b'0', b'0'),
				('52', '1', 'DL', 'Text', 'Drivers License number.', '/.*/s', '', 'Drivers License', b'0', b'0'),
				('53', '1', 'DLState', 'Select', 'State in which the user is licensed.', '/.*/s', '', 'State', b'0', b'0'),
				('54', '1', 'EMTID', 'Text', 'SC-EMT ID number.', '/.*/s', '', 'Santa Clara EMT ID', b'0', b'0'),
				('55', '1', 'CPRAgency', 'Text', 'CPR certification issuing Agency.', '/.*/s', '', 'CPR Agency', b'0', b'0'),
				('56', '1', 'BBP', 'Date', 'Blood Borne Pathogens(BBP) training.', '/^.*$/s', '', 'BBP', b'0', b'0'),
				('57', '1', 'HIPAA', 'Date', 'HIPAA Training date.', '/^.*$/s', '', 'HIPAA', b'0', b'0'),
				('58', '1', 'IS1', 'Date', 'IS-1 training completing date.', '/^.*$/s', '', 'IS-1', b'0', b'0'),
				('59', '1', 'NIMS700', 'Date', 'NIMS-700 training completing date.', '/^.*$/s', '', 'NIMS-700', b'0', b'0'),
				('60', '1', 'IS3', 'Date', 'IS-3 training completing date.', '/^.*$/s', '', 'IS3', b'0', b'0'),
				('61', '1', 'AWR160', 'Date', 'AWR-160 training completing date.', '/^.*$/s', '', 'AWR-160', b'0', b'0'),
				('62', '1', 'SEMS', 'Date', 'SEMS training completing date.', '/^.*$/s', '', 'SEMS', b'0', b'0'),
				('63', '1', 'EMSUpdate', 'Date', 'Santa Clara Annual EMS Update completion date.', '/^.*$/s', '', 'SC EMS Update', b'0', b'0'),
				('64', '1', 'ResponseLimitations', 'TextArea', 'Any restrictions one might have if needed to respond to an MCI, such as being an RA.', '/^.*$/s', '', 'Campus-wide Response Limitations', b'0', b'0'),
				('65', '1', 'Image', 'StaticImage', 'Static image to be shown at the logon screen.', '/.*/s', 'resources/images/Gator.jpg', 'Logon Image', b'0', b'0'),

				('70', '7', 'EndTime', 'Text', 'Event ending time.', '/.*/s', '', 'End', b'0', b'0'),
				('71', '7', 'Date', 'Date', 'Event Date.', '/.*/s', '', 'Date', b'0', b'0'),
				('72', '7', 'EventType', 'Select', 'Type of medical event.', '/^.*$/s', '', 'Event Type', b'0', b'0'),
				('73', '7', 'Location', 'SelectEdit', 'Location where the event takes place.', '/.*/s', '', 'Location', b'0', b'0'),
				('74', '7', 'EventID', 'Text', 'Event ID provided by DPS.', '/.*/s', '', 'EventID', b'0', b'0'),
				('75', '7', 'Dispatch', 'SelectEdit', 'Who StEMS members should contact for ALS.', '/.*/s', '', 'Dispatch Info', b'0', b'0'),
				('76', '7', 'MeetLocation', 'SelectEdit', 'Location where members should meet for the event.', '/.*/s', '', 'Meeting Location', b'0', b'0'),
				('77', '7', 'Dress', 'Select', 'Attire or uniform type attending members should wear.', '/.*/s', '', 'Dress', b'0', b'0'),
				('78', '7', 'Details', 'TextArea', 'Any special information or details for this shift.', '/^.*$/s', '', 'Additional Details', b'0', b'0'),
				('79', '7', 'StartTime', 'Text', 'Event starting time.', '/.*/s', '', 'Start', b'0', b'0'),
				('80', '7', 'EventName', 'Text', 'Name given to the event.  Should be unique.', '/.*/s', '', 'Event Name', b'0', b'1'),
				('81', '7', 'AAR', 'FileUpload', 'Standard document describing unusual circumstances of an event.', '/^.*$/s', '', 'After Action Report', b'0', b'0'),
				('82', '7', 'Category', 'Select', 'Type of event.', '/^.*$/s', '', 'Category', b'0', b'0'),
				('83', '7', 'NumPeople', 'Text', 'The number of people needed for this event.', '/^[0-9]*-[0-9]*$/s', '', '# People', b'0', b'0'),

				('90', '5', 'WishPos', 'ComboBox', 'Which position this member wishes to fulfill.', '/^.*$/s', '', 'Desired Positions', b'0', b'0'),
				('91', '5', 'ChosenPos', 'Select', 'Which position this member will fulfill.', '/^.*$/s', '', 'Chosen Position', b'0', b'0'),
				('92', '5', 'Member', 'SelectMembers', 'The member chosen for this shift.', '/^.*$/s', '', 'Member', b'0', b'0'),
				('93', '5', 'Event', 'PageLink', 'Event for which this member is working.', '/^.*$/s', '', 'Event', b'0', b'0'),
				('94', '5', 'Crew', 'SelectEdit', 'The crew this member has been placed in.', '/^.*$/s', '', 'Crew', b'0', b'0'),
				('95', '5', 'Comments', 'TextArea', 'Any time constraints or comments for this member.', '/^.*$/s', '', 'Comments', b'0', b'1'),

				('100', '6', 'Event', 'PageLink', 'Event on which this PCR occurred.', '/^.*$/s', '', 'Event', b'0', b'0'),
				('101', '6', 'PtAge', 'Select', 'Approximate patient age.', '/^.*$/s', '', 'Age', b'0', b'0'),
				('102', '6', 'NOI', 'ComboBox', 'Nature of Illness or Mechanism of Injury.', '/^.*$/s', '', 'NOI/MOI', b'0', b'0'),
				('103', '6', 'Disposition', 'Select', 'Ultimate destination or outcome of this patient.', '/^.*$/s', '', 'Disposition', b'0', b'0'),
				('104', '6', 'PCRID', 'Text', 'The PCR ID of this entry.', '/^.*$/s', '', 'PCR ID', b'0', b'1')",
			"INSERT INTO `modulefieldoption`
				(`ModuleFieldOptionID`, `OptionValue`, `OptionLabel`, `ModuleFieldID`) VALUES
				(NULL, 'AL', 'Alabama', '53'),
				(NULL, 'AK', 'Alaska', '53'),
				(NULL, 'AZ', 'Arizona', '53'),
				(NULL, 'AR', 'Arkansas', '53'),
				(NULL, 'CA', 'California', '53'),
				(NULL, 'CO', 'Colorado', '53'),
				(NULL, 'CT', 'Connecticut', '53'),
				(NULL, 'DE', 'Delaware', '53'),
				(NULL, 'FL', 'Florida', '53'),
				(NULL, 'GA', 'Georgia', '53'),
				(NULL, 'HI', 'Hawaii', '53'),
				(NULL, 'ID', 'Idaho', '53'),
				(NULL, 'IL', 'Illinois', '53'),
				(NULL, 'IN', 'Indiana', '53'),
				(NULL, 'IA', 'Iowa', '53'),
				(NULL, 'KS', 'Kansas', '53'),
				(NULL, 'KY', 'Kentucky', '53'),
				(NULL, 'LA', 'Louisiana', '53'),
				(NULL, 'ME', 'Maine', '53'),
				(NULL, 'MD', 'Maryland', '53'),
				(NULL, 'MA', 'Massachusetts', '53'),
				(NULL, 'MI', 'Michigan', '53'),
				(NULL, 'MN', 'Minnesota', '53'),
				(NULL, 'MS', 'Mississippi', '53'),
				(NULL, 'MO', 'Missouri', '53'),
				(NULL, 'MT', 'Montana', '53'),
				(NULL, 'NE', 'Nebraska', '53'),
				(NULL, 'NV', 'Nevada', '53'),
				(NULL, 'NH', 'New Hampshire', '53'),
				(NULL, 'NJ', 'New Jersey', '53'),
				(NULL, 'NM', 'New Mexico', '53'),
				(NULL, 'NY', 'New York', '53'),
				(NULL, 'NC', 'North Carolina', '53'),
				(NULL, 'ND', 'North Dakota', '53'),
				(NULL, 'OH', 'Ohio', '53'),
				(NULL, 'OK', 'Oklahoma', '53'),
				(NULL, 'OR', 'Oregon', '53'),
				(NULL, 'PA', 'Pennsylvania', '53'),
				(NULL, 'RI', 'Rhode Island', '53'),
				(NULL, 'SC', 'South Carolina', '53'),
				(NULL, 'SD', 'South Dakota', '53'),
				(NULL, 'TN', 'Tennessee', '53'),
				(NULL, 'TX', 'Texas', '53'),
				(NULL, 'UT', 'Utah', '53'),
				(NULL, 'VT', 'Vermont', '53'),
				(NULL, 'VA', 'Virginia', '53'),
				(NULL, 'WA', 'Washington', '53'),
				(NULL, 'WV', 'West Virginia', '53'),
				(NULL, 'WI', 'Wisconsin', '53'),
				(NULL, 'WY', 'Wyoming', '53'),

				(NULL, '0-17', '0-17', '101'),
				(NULL, '18-22', '18-22', '101'),
				(NULL, '22+', '22+', '101'),

				(NULL, 'Cardiac', 'Cardiac', '102'),
				(NULL, 'Stroke', 'Stroke', '102'),
				(NULL, 'Seizure', 'Seizure', '102'),
				(NULL, 'HRI', 'Heat Related Illness', '102'),
				(NULL, 'Syncope', 'Syncope', '102'),
				(NULL, 'Psychiatric', 'Psychiatric', '102'),
				(NULL, 'Diabetic', 'Diabetic Emergency', '102'),
				(NULL, 'Abdominal', 'Abdominal Pain', '102'),
				(NULL, 'SOB', 'Shiftness of Breath', '102'),
				(NULL, 'OtherMed', 'Other Medical', '102'),
				(NULL, 'ALOC', 'Altered Mental Status', '102'),
				(NULL, 'DNV', 'Dizziness/Nausea/Vomiting', '102'),
				(NULL, 'TraumaOther', 'Trauma (Other)', '102'),
				(NULL, 'TraumaLower', 'Trauma (Lower Extremities)', '102'),
				(NULL, 'TraumaUpper', 'Trauma (Upper Extremities)', '102'),
				(NULL, 'TraumaHead', 'Trauma (Head)', '102'),

				(NULL, 'Transported', 'Transported', '103'),
				(NULL, 'Refused', 'Refused Treatment', '103'),
				(NULL, 'ReleasedSelf', 'Released to Self', '103'),
				(NULL, 'ReleasedPolice', 'Police Custody', '103'),
				(NULL, 'ReleasedOther', 'Released to Other', '103'),

				(NULL, 'Crew Chief', 'Crew Chief', 90),
				(NULL, 'Attendant', 'Attendant', 90),
				(NULL, 'CCIT', 'CCIT', 90),
				(NULL, 'Command', 'Command', 90),

				(NULL, 'Event', 'PageName', 93),
				(NULL, '7', 'ModuleID', 93),
				(NULL, '80', 'ModuleFieldID', 93),

				(NULL, 'Crew Chief', 'Crew Chief', 91),
				(NULL, 'Attendant', 'Attendant', 91),
				(NULL, 'CCIT', 'CCIT', 91),
				(NULL, 'Command', 'Command', 91),

				(NULL, '', 'RoleField', 92),
				(NULL, '1', 'MaxRegistrations', 92),
				(NULL, 'Register', 'AddText', 92),
				(NULL, 'Drop', 'DropText', 92),
				(NULL, '1', 'DeleteOnDrop', 92),

				(NULL, 'Event', 'PageName', 100),
				(NULL, '7', 'ModuleID', 100),
				(NULL, '80', 'ModuleFieldID', 100),

				(NULL, 'Party', 'Party', 72),
				(NULL, 'Football', 'Football', 72),

				(NULL, 'PACOMM', 'PACOMM', 75),
				(NULL, 'Stadium Control', 'Stadium Control', 75),

				(NULL, 'Casual', 'Casual', 77),
				(NULL, 'Whites', 'Whites', 77),
				(NULL, 'Blues', 'Blues', 77),

				(NULL, '5', 'MaxFileCount', 81),
				(NULL, 'on', 'ShowPreview', 81),
				(NULL, '10000000', 'MaxSize', 81),
				
				(NULL, 'Medical', 'Medical', 82),
				(NULL, 'Training', 'Training', 82),
				(NULL, 'Other', 'Other', 82)",
			"INSERT INTO `listing`
				(`ListingID`, `PageName`, `MaxItems`, `NewEntryPageName`, `CreateText`) VALUES
				(10, 'EventList', '10', 'Event', 'New Event'),
				(11, 'PCRList', '10', 'PCR', 'New PCR'),
				(12, 'CrewList', '10', 'CrewMember', 'Sign-Up')",
			"INSERT INTO `listfield`
				(`ListFieldID`, `Position`, `ModuleFieldID`, `ListingID`, `IncludeLabel`, `LinkPageName`, `Width`) VALUES
				(NULL, '1', '80', '10', b'0', 'Event', '4'),
				(NULL, '2', '71', '10', b'0', NULL, '1'),
				(NULL, '3', '79', '10', b'0', NULL, '1'),
				(NULL, '4', '70', '10', b'0', NULL, '1'),
				(NULL, '6', '83', '10', b'0', NULL, '1'),
				(NULL, '7', '73', '10', b'0', NULL, '2'),
				(NULL, '8', NULL, '10', b'0', NULL, '1'),

				(NULL, '1', '92', '12', b'0', 'CrewMember', '1'),
				(NULL, '2', '90', '12', b'0', NULL, '1'),
				(NULL, '3', '91', '12', b'0', NULL, '1'),
				(NULL, '4', '94', '12', b'0', NULL, '1'),

				(NULL, '1', '104', '11', b'0', 'PCR', '1'),
				(NULL, '2', '100', '11', b'0', NULL, '1'),
				(NULL, '3', '101', '11', b'0', NULL, '1'),
				(NULL, '4', '103', '11', b'0', NULL, '1'),
				(NULL, '5', '102', '11', b'0', NULL, '1')",
			"INSERT INTO `listby`
				(`ListByID`, `Rank`, `ModuleFieldID`, `ListingID`, `Direction`, `Orientation`, `Type`) VALUES
				(NULL, '1', '71', '10', b'1', b'0', b'0'),
				(NULL, '2', '79', '10', b'1', b'0', b'0'),

				(NULL, '1', '92', '12', b'1', b'0', b'0'),

				(NULL, '1', '104', '11', b'1', b'0', b'0')",
			"INSERT INTO `listoption`
				(`ListOptionID`, `PageName`, `ListingID`, `Title`) VALUES
				(NULL, 'Event', '10', 'View'),
				(NULL, 'Event', '10', 'Edit'),
				(NULL, 'PCRList', '10', 'PCRs'),
				(NULL, 'CrewList', '10', 'Crews'),
				(NULL, NULL, '10', 'Delete')",
			"INSERT INTO `listfilter`
				(`ListFilterID`, `ModuleFieldID`, `ListingID`, `Value`) VALUES
				(NULL, '80', '10', ''),
				(NULL, '92', '12', ''),
				(NULL, '104', '11', '')",
			"DELETE FROM MFPrivilege",
			"DELETE FROM ModulePrivilege",
			"DELETE FROM formfield",
			"INSERT INTO `formfield`
				(`FormFieldID`, `ModuleFieldID`, `FormID`, `Pos_Top`, `Pos_Left`, `Pos_Width`, `Pos_Height`, `Mutable`, `IncludeLabel`, `Removable`) VALUES
				(7, 1, 1, 0, 0, 100, 22, '1', '1', '1'),
				(8, 2, 1, 24, 10, 100, 22, '1', '1', '1'),
				(9, 6, 1, 0, 407, 318, 129, '1', '1', '1'),
				(10, 8, 1, 93, 4, 100, 22, '1', '1', '1'),
				(11, 9, 1, 93, 187, 100, 22, '1', '1', '1'),
				(12, 11, 1, 117, 4, 200, 22, '1', '1', '1'),
				(13, 1, 3, 224, 0, 100, 22, '1', '1', '1'),
				(14, 2, 3, 248, 9, 100, 22, '1', '1', '1'),
				(15, 2, 4, 0, 0, 100, 22, '1', '1', '1'),
				(16, 2, 4, 24, 77, 100, 22, '1', '1', '1'),
				(17, 20, 20, 0, 0, 100, 22, '1', '1', '1'),
				(18, 21, 20, 0, 147, 100, 25, '1', '1', '1'),
				(19, 22, 20, 30, 0, 600, 22, '1', '1', '1'),
				(20, 23, 20, 0, 626, 150, 22, '1', '1', '1'),
				(21, 24, 20, 0, 432, 100, 22, '1', '1', '1'),
				(22, 25, 20, 0, 287, 100, 22, '1', '1', '1'),
				(38, 90, 52, 0, 0, 150, 100, '1', '1', '1'),
				(39, 91, 52, 0, 278, 150, 22, '1', '1', '1'),
				(40, 92, 52, 101, 197, 150, 150, '1', '1', '1'),
				(41, 93, 52, 101, -1, 150, 150, '1', '1', '1'),
				(42, 94, 52, 23, 353, 150, 22, '1', '1', '1'),
				(43, 95, 52, 51, 411, 400, 200, '1', '1', '1'),
				(44, 100, 50, 0, 0, 200, 150, '1', '1', '1'),
				(45, 101, 50, 23, 301, 100, 22, '1', '1', '1'),
				(46, 102, 50, 0, 483, 200, 150, '1', '1', '1'),
				(47, 103, 50, 0, 248, 150, 22, '1', '1', '1'),
				(48, 104, 50, 47, 280, 100, 22, '1', '1', '1'),
				(49, 80, 51, 0, 0, 200, 22, '1', '1', '1'),
				(50, 71, 51, 0, 337, 182, 22, '1', '1', '1'),
				(51, 79, 51, 23, 337, 75, 22, '1', '1', '1'),
				(52, 70, 51, 23, 451, 75, 22, '1', '1', '1'),
				(53, 73, 51, 99, 310, 150, 22, '1', '1', '1'),
				(54, 72, 51, 75, 563, 150, 22, '1', '1', '1'),
				(55, 75, 51, 75, -1, 150, 22, '1', '1', '1'),
				(56, 74, 51, 23, 32, 100, 22, '1', '1', '1'),
				(57, 76, 51, 75, 249, 150, 22, '1', '1', '1'),
				(58, 77, 51, 99, 55, 150, 22, '1', '1', '1'),
				(59, 82, 51, 99, 578, 150, 22, '1', '1', '1'),
				(60, 83, 51, 0, 580, 100, 22, '1', '1', '1'),
				(62, 78, 51, 158, 0, 600, 104, '1', '1', '1'),
				(63, 81, 51, 262, 127, 150, 26, '1', '1', '1'),
				(65, 65, 3, 0, 0, 921, 201, '1', b'0', '1'),
				(66, 8, 2, 0, 0, 150, 22, '1', '1', '1'),
				(67, 9, 2, 0, 232, 150, 22, '1', '1', '1'),
				(68, 1, 2, 23, 0, 100, 22, '1', '1', '1'),
				(69, 10, 2, 60, 1, 100, 22, '1', '1', '1'),
				(70, 11, 2, 84, 1, 200, 22, '1', '1', '1'),
				(71, 52, 2, 145, 0, 150, 22, '1', '1', '1'),
				(72, 53, 2, 145, 266, 150, 22, '1', '1', '1'),
				(73, 55, 2, 210, 47, 150, 22, '1', '1', '1'),
				(74, 50, 2, 210, 301, 150, 22, '1', '1', '1'),
				(75, 54, 2, 234, 0, 150, 22, '1', '1', '1'),
				(76, 51, 2, 234, 287, 150, 22, '1', '1', '1'),
				(77, 56, 2, 288, 0, 100, 22, '1', '1', '1'),
				(78, 57, 2, 312, 117, 100, 22, '1', '1', '1'),
				(79, 58, 2, 312, 382, 100, 22, '1', '1', '1'),
				(80, 59, 2, 336, 93, 100, 22, '1', '1', '1'),
				(81, 60, 2, 360, 136, 100, 22, '1', '1', '1'),
				(82, 61, 2, 360, 347, 100, 22, '1', '1', '1'),
				(83, 62, 2, 336, 372, 100, 22, '1', '1', '1'),
				(84, 63, 2, 288, 301, 100, 22, '1', '1', '1'),
				(85, 64, 2, 411, -1, 300, 212, '1', '1', '1'),
				(86, 7, 2, 411, 472, 200, 150, '1', '1', '1')"
		);
		foreach($defaultEntries as $defaultQuery)
			Database::getInstance()->query($defaultQuery);

		$newRoleQuery = "INSERT INTO `Role`
			(`RoleID`, `RoleName`, `Description`) VALUES
			('5', 'Full Member', 'Allowed to sign-up for all events.'),
			('6', 'Probationary', 'Allowed to sign-up for all events.'),
			('7', 'Crew Chief', 'Able to sign-up for all shift-types.'),
			('8', 'Dir. of Personnel', 'Can create all members profiles, and accept new members.'),
			('9', 'Dir. of Ops', 'Can create new shifts and crews.'),
			('10', 'Edit Profile', 'Allows one to edit ones profile.'),
			('11', 'Alumni', 'May sign-in and edit their profile, but unable to sign-up for any event.')";
		Database::getInstance()->query($newRoleQuery);

		$generalPrivilegesQuery = "INSERT INTO `generalprivilege`
			(`GeneralPrivilegeID`, `RoleID`, `Task`) VALUES
			(NULL, 5, 'Logon'),
			(NULL, 6, 'Logon'),
			(NULL, 7, 'Logon'),
			(NULL, 8, 'Logon'),
			(NULL, 9, 'Logon'),
			(NULL, 10, 'Logon'),
			(NULL, 11, 'Logon')";
		Database::getInstance()->query($generalPrivilegesQuery);

$mfPrivilegesQuery = "INSERT INTO `mfprivilege`
(`MFPID`, `ModuleFieldID`, `RoleID`, `Task`) VALUES
(1, 1, 1, 'Create'),
(7, 1, 2, 'Create'),
(18, 1, 2, 'Read'),
(29, 1, 2, 'Write'),
(760, 1, 5, 'Read'),
(2, 2, 1, 'Create'),
(8, 2, 2, 'Create'),
(19, 2, 2, 'Read'),
(30, 2, 2, 'Write'),
(9, 3, 2, 'Create'),
(20, 3, 2, 'Read'),
(31, 3, 2, 'Write'),
(10, 4, 2, 'Create'),
(21, 4, 2, 'Read'),
(32, 4, 2, 'Write'),
(11, 5, 2, 'Create'),
(22, 5, 2, 'Read'),
(33, 5, 2, 'Write'),
(719, 6, 1, 'Create'),
(717, 6, 1, 'Read'),
(718, 6, 1, 'Write'),
(12, 6, 2, 'Create'),
(23, 6, 2, 'Read'),
(34, 6, 2, 'Write'),
(720, 6, 10, 'Write'),
(13, 7, 2, 'Create'),
(24, 7, 2, 'Read'),
(35, 7, 2, 'Write'),
(761, 7, 5, 'Read'),
(3, 8, 1, 'Create'),
(14, 8, 2, 'Create'),
(25, 8, 2, 'Read'),
(36, 8, 2, 'Write'),
(658, 8, 5, 'Read'),
(721, 8, 10, 'Write'),
(4, 9, 1, 'Create'),
(15, 9, 2, 'Create'),
(26, 9, 2, 'Read'),
(37, 9, 2, 'Write'),
(659, 9, 5, 'Read'),
(722, 9, 10, 'Write'),
(5, 10, 1, 'Create'),
(16, 10, 2, 'Create'),
(27, 10, 2, 'Read'),
(38, 10, 2, 'Write'),
(660, 10, 5, 'Read'),
(723, 10, 10, 'Write'),
(6, 11, 1, 'Create'),
(17, 11, 2, 'Create'),
(28, 11, 2, 'Read'),
(39, 11, 2, 'Write'),
(661, 11, 5, 'Read'),
(724, 11, 10, 'Write'),
(478, 20, 2, 'Create'),
(479, 20, 2, 'Read'),
(480, 20, 2, 'Write'),
(481, 21, 2, 'Create'),
(482, 21, 2, 'Read'),
(483, 21, 2, 'Write'),
(484, 22, 2, 'Create'),
(485, 22, 2, 'Read'),
(486, 22, 2, 'Write'),
(487, 23, 2, 'Create'),
(488, 23, 2, 'Read'),
(489, 23, 2, 'Write'),
(490, 24, 2, 'Create'),
(491, 24, 2, 'Read'),
(492, 24, 2, 'Write'),
(493, 25, 2, 'Create'),
(494, 25, 2, 'Read'),
(495, 25, 2, 'Write'),
(496, 26, 2, 'Create'),
(497, 26, 2, 'Read'),
(498, 26, 2, 'Write'),
(75, 50, 2, 'Create'),
(73, 50, 2, 'Read'),
(74, 50, 2, 'Write'),
(78, 51, 2, 'Create'),
(76, 51, 2, 'Read'),
(77, 51, 2, 'Write'),
(81, 52, 2, 'Create'),
(79, 52, 2, 'Read'),
(80, 52, 2, 'Write'),
(725, 52, 10, 'Write'),
(84, 53, 2, 'Create'),
(82, 53, 2, 'Read'),
(83, 53, 2, 'Write'),
(726, 53, 10, 'Write'),
(87, 54, 2, 'Create'),
(85, 54, 2, 'Read'),
(86, 54, 2, 'Write'),
(90, 55, 2, 'Create'),
(88, 55, 2, 'Read'),
(89, 55, 2, 'Write'),
(93, 56, 2, 'Create'),
(91, 56, 2, 'Read'),
(92, 56, 2, 'Write'),
(96, 57, 2, 'Create'),
(94, 57, 2, 'Read'),
(95, 57, 2, 'Write'),
(99, 58, 2, 'Create'),
(97, 58, 2, 'Read'),
(98, 58, 2, 'Write'),
(102, 59, 2, 'Create'),
(100, 59, 2, 'Read'),
(101, 59, 2, 'Write'),
(105, 60, 2, 'Create'),
(103, 60, 2, 'Read'),
(104, 60, 2, 'Write'),
(108, 61, 2, 'Create'),
(106, 61, 2, 'Read'),
(107, 61, 2, 'Write'),
(111, 62, 2, 'Create'),
(109, 62, 2, 'Read'),
(110, 62, 2, 'Write'),
(114, 63, 2, 'Create'),
(112, 63, 2, 'Read'),
(113, 63, 2, 'Write'),
(117, 64, 2, 'Create'),
(115, 64, 2, 'Read'),
(116, 64, 2, 'Write'),
(727, 64, 10, 'Write'),
(662, 65, 5, 'Read'),
(728, 65, 10, 'Write'),
(438, 70, 2, 'Create'),
(436, 70, 2, 'Read'),
(437, 70, 2, 'Write'),
(697, 70, 5, 'Read'),
(441, 71, 2, 'Create'),
(439, 71, 2, 'Read'),
(440, 71, 2, 'Write'),
(698, 71, 5, 'Read'),
(444, 72, 2, 'Create'),
(442, 72, 2, 'Read'),
(443, 72, 2, 'Write'),
(699, 72, 5, 'Read'),
(447, 73, 2, 'Create'),
(445, 73, 2, 'Read'),
(446, 73, 2, 'Write'),
(700, 73, 5, 'Read'),
(450, 74, 2, 'Create'),
(448, 74, 2, 'Read'),
(449, 74, 2, 'Write'),
(701, 74, 5, 'Read'),
(453, 75, 2, 'Create'),
(451, 75, 2, 'Read'),
(452, 75, 2, 'Write'),
(702, 75, 5, 'Read'),
(456, 76, 2, 'Create'),
(454, 76, 2, 'Read'),
(455, 76, 2, 'Write'),
(703, 76, 5, 'Read'),
(459, 77, 2, 'Create'),
(457, 77, 2, 'Read'),
(458, 77, 2, 'Write'),
(704, 77, 5, 'Read'),
(462, 78, 2, 'Create'),
(460, 78, 2, 'Read'),
(461, 78, 2, 'Write'),
(705, 78, 5, 'Read'),
(465, 79, 2, 'Create'),
(463, 79, 2, 'Read'),
(464, 79, 2, 'Write'),
(706, 79, 5, 'Read'),
(468, 80, 2, 'Create'),
(466, 80, 2, 'Read'),
(467, 80, 2, 'Write'),
(707, 80, 5, 'Read'),
(471, 81, 2, 'Create'),
(469, 81, 2, 'Read'),
(470, 81, 2, 'Write'),
(708, 81, 5, 'Read'),
(474, 82, 2, 'Create'),
(472, 82, 2, 'Read'),
(473, 82, 2, 'Write'),
(709, 82, 5, 'Read'),
(477, 83, 2, 'Create'),
(475, 83, 2, 'Read'),
(476, 83, 2, 'Write'),
(710, 83, 5, 'Read'),
(198, 90, 2, 'Create'),
(196, 90, 2, 'Read'),
(197, 90, 2, 'Write'),
(670, 90, 5, 'Create'),
(668, 90, 5, 'Read'),
(669, 90, 5, 'Write'),
(201, 91, 2, 'Create'),
(199, 91, 2, 'Read'),
(200, 91, 2, 'Write'),
(671, 91, 5, 'Read'),
(204, 92, 2, 'Create'),
(202, 92, 2, 'Read'),
(203, 92, 2, 'Write'),
(673, 92, 5, 'Create'),
(672, 92, 5, 'Read'),
(207, 93, 2, 'Create'),
(205, 93, 2, 'Read'),
(206, 93, 2, 'Write'),
(675, 93, 5, 'Create'),
(674, 93, 5, 'Read'),
(210, 94, 2, 'Create'),
(208, 94, 2, 'Read'),
(209, 94, 2, 'Write'),
(676, 94, 5, 'Read'),
(213, 95, 2, 'Create'),
(211, 95, 2, 'Read'),
(212, 95, 2, 'Write'),
(679, 95, 5, 'Create'),
(677, 95, 5, 'Read'),
(678, 95, 5, 'Write'),
(312, 100, 2, 'Create'),
(310, 100, 2, 'Read'),
(311, 100, 2, 'Write'),
(1094, 101, 2, 'Create'),
(1092, 101, 2, 'Read'),
(1093, 101, 2, 'Write'),
(1097, 102, 2, 'Create'),
(1095, 102, 2, 'Read'),
(1096, 102, 2, 'Write'),
(1100, 103, 2, 'Create'),
(1098, 103, 2, 'Read'),
(1099, 103, 2, 'Write'),
(1103, 104, 2, 'Create'),
(1101, 104, 2, 'Read'),
(1102, 104, 2, 'Write')";
		Database::getInstance()->query($mfPrivilegesQuery);

		$modulePriviligesQuery = "INSERT INTO `moduleprivilege`
			(`ModulePrivilegeID`, `RoleID`, `ModuleID`, `Task`, `Option`) VALUES
(14, 1, 1, 'CreateInstance', NULL),
(15, 2, 1, 'EditModuleProperties', NULL),
(16, 2, 1, 'TransferRole', NULL),
(17, 2, 1, 'CreateField', NULL),
(18, 2, 1, 'DeleteField', NULL),
(19, 2, 1, 'CreateInstance', NULL),
(20, 2, 1, 'DeleteInstance', NULL),
(21, 2, 1, 'CreateList', NULL),
(22, 2, 1, 'CreateForm', NULL),
(23, 2, 1, 'DeleteList', NULL),
(24, 2, 1, 'DeleteForm', NULL),
(25, 2, 1, 'EditList', NULL),
(26, 2, 1, 'EditForm', NULL),
(27, 2, 6, 'EditModuleProperties', NULL),
(28, 2, 6, 'TransferRole', NULL),
(29, 2, 6, 'CreateField', NULL),
(30, 2, 6, 'DeleteField', NULL),
(31, 2, 6, 'CreateInstance', NULL),
(32, 2, 6, 'DeleteInstance', NULL),
(33, 2, 6, 'CreateList', NULL),
(34, 2, 6, 'CreateForm', NULL),
(35, 2, 6, 'DeleteList', NULL),
(36, 2, 6, 'DeleteForm', NULL),
(37, 2, 6, 'EditList', NULL),
(38, 2, 6, 'EditForm', NULL),
(39, 2, 5, 'EditModuleProperties', NULL),
(40, 2, 5, 'TransferRole', NULL),
(41, 2, 5, 'CreateField', NULL),
(42, 2, 5, 'DeleteField', NULL),
(43, 2, 5, 'CreateInstance', NULL),
(44, 2, 5, 'DeleteInstance', NULL),
(45, 2, 5, 'CreateList', NULL),
(46, 2, 5, 'CreateForm', NULL),
(47, 2, 5, 'DeleteList', NULL),
(48, 2, 5, 'DeleteForm', NULL),
(49, 2, 5, 'EditList', NULL),
(50, 2, 5, 'EditForm', NULL),
(51, 2, 7, 'EditModuleProperties', NULL),
(52, 2, 7, 'TransferRole', NULL),
(53, 2, 7, 'CreateField', NULL),
(54, 2, 7, 'DeleteField', NULL),
(55, 2, 7, 'CreateInstance', NULL),
(56, 2, 7, 'DeleteInstance', NULL),
(57, 2, 7, 'CreateList', NULL),
(58, 2, 7, 'CreateForm', NULL),
(59, 2, 7, 'DeleteList', NULL),
(60, 2, 7, 'DeleteForm', NULL),
(61, 2, 7, 'EditList', NULL),
(62, 2, 7, 'EditForm', NULL),
(63, 5, 5, 'CreateInstance', NULL)";
		Database::getInstance()->query($modulePriviligesQuery);

		$firstNames = array("Bob", "Anne", "Henry", "Steve", "Brian", "Brittany", "Mark", "Fred", "Dwight", "Chelsea", "Nicole", "Ariel");
		$lastNames = array("Williams", "Smith", "Jones", "Twain", "Washington", "Deaver", "Johnson", "Brown", "Davis", "Miller", "Wilson", "Moore");
		$userRoles = array("6##8##5", "6##9##5##7", "5##6", "5##6","5##6","5##6","5##6","5##6","5##6","5##6", "11", "11");
		$cprAgency = array("AHA", "ARC");

		for($i = 0; $i < 12; $i++){
			$moduleInstance = ModuleInstance::newModuleInstance(1);
			$userName = substr($firstNames[$i], 0, 1) . substr($lastNames[$i], 0, 8);

			$fieldsToModify = array(
				"1" => $userName,
				"2" => Utils::hashPassword("password"),
				"7" => $userRoles[$i],
				"8" => $firstNames[$i],
				"9" => $lastNames[$i],
				"10" => "(650)555-" . rand(1000, 9999),
				"11" => "$userName@ems.com",
				"55" => $cprAgency[$i % 2],
				"52" => strtoupper(Utils::makeRandomLetter()) . rand(1000000, 1000000),
				"54" => "E" . rand(100000, 100000),
				"50" => Utils::makeRandomDateInclusive('2011-04-01','2013-04-03'),
				"51" => Utils::makeRandomDateInclusive('2011-04-01','2013-04-03'),
				"56" => Utils::makeRandomDateInclusive('2008-04-01','2011-04-03'),
				"57" => Utils::makeRandomDateInclusive('2008-04-01','2011-04-03'),
				"58" => Utils::makeRandomDateInclusive('2008-04-01','2011-04-03'),
				"59" => Utils::makeRandomDateInclusive('2008-04-01','2011-04-03'),
				"60" => Utils::makeRandomDateInclusive('2008-04-01','2011-04-03'),
				"61" => Utils::makeRandomDateInclusive('2008-04-01','2011-04-03'),
				"62" => Utils::makeRandomDateInclusive('2008-04-01','2011-04-03'),
				"63" => Utils::makeRandomDateInclusive('2008-04-01','2011-04-03'));

			$moduleInstance->moduleFieldValuesAre($fieldsToModify);
			$moduleInstance->saveToDB();
		}

		// TODO: Create demo Crews and PCRs
//		$dates = array("05/08/2011", "05/15/2011", "05/22/2011", "05/29/2011");
//		$topics = array("Weapons of Mass Destruction", "Slope Day TM", "Bang Operations", "MCI Scenarios");
//
//		for($i = 0; $i < 4; $i++){
//			$moduleInstance = ModuleInstance::newModuleInstance(10);
//			$fieldsToModify = array(
//				"73" => "7:00pm",
//				"74" => "8:00pm",
//				"75" => $dates[$i],
//				"76" => "21##22##23##24##25##26##27##28##29##30##31",
//				"77" => $topics[$i]);
//			$moduleInstance->moduleFieldValuesAre($fieldsToModify);
//			$moduleInstance->saveToDB();
//		}
//
		$startTimes = array("1100", "1400", "1800");
		$endTimes = array("1600", "1900", "2300");
		$dates = array("06/01/2011", "06/02/2011", "06/03/2011", "06/04/2011", "06/05/2011", "06/06/2011", "06/07/2011");
		$categories = array("Medical");
		$names = array("Exotic Erotic", "Football vs USC", "Block Party", "Football vs Oregon", "Full Moon on the Quad", "Football vs. SJSU", "Band Run");
		$types = array("Party", "Football");
		$locations = array("Row", "Stanford Stadium");
		$members = array("");
		$dresses = array("Blues", "Whites");

		$ptAges = array("0-17", "18-22", "22+");
		$NOIs = array("Cardiac", "Seizure", "HRI", "Syncope", "Diabetic", "SOB", "TraumaLower", "ALOC", "DNV");
		$dispositions = array("Transported", "Refused", "ReleasedSelf", "ReleasedPolice", "ReleasedOther");

		$crews = array("StEMS 1", "StEMS 2", "StEMS 3", "StEMS 4");
		$positions = array("Crew Chief", "Attendant", "CCIT", "Command");

		for($i = 0; $i < 7; $i++){
			$moduleInstance = ModuleInstance::newModuleInstance(7);
			$fieldsToModify = array(
				"70" => $endTimes[rand(0,2)],
				"79" => $startTimes[rand(0,2)],
				"71" => $dates[$i],
				"82" => $categories[0],
				"72" => $types[$i % 2],
				"80" => $names[$i],
				"83" => "6-12",
				"76" => "Compound",
				"74" => "EID" . rand(0,4000),
				"75" => "PACOMM",
				"73" => $locations[$i % 2],
				"73" => $locations[$i % 2],
				"77" => $dresses[$i % 2]);
			$moduleInstance->moduleFieldValuesAre($fieldsToModify);
			$moduleInstance->saveToDB();

			for($j = 0; $j < 3; $j++){
				$pcrMI = ModuleInstance::newModuleInstance(6);
				$fieldsToModify = array(
					"100" => $moduleInstance->moduleInstanceID(),
					"101" => $ptAges[$j % 3],
					"102" => $NOIs[rand(0, count($NOIs) - 1)],
					"103" => $dispositions[rand(0, count($dispositions) - 1)],
					"104" => rand(0, 3000));
				$pcrMI->moduleFieldValuesAre($fieldsToModify);
				$pcrMI->saveToDB();
			}

			for($j = 0; $j < 3; $j++){
				$cmMI = ModuleInstance::newModuleInstance(5);
				$fieldsToModify = array(
					"90" => $positions[rand(0, count($positions) - 1)] . "##" . $positions[rand(0, count($positions) - 1)],
					"91" => $positions[rand(0, count($positions) - 1)],
					"92" => rand(21, 32),
					"93" => $moduleInstance->moduleInstanceID(),
					"94" => $crews[rand(0, count($crews) - 1)],
					"95" => "");
				$cmMI->moduleFieldValuesAre($fieldsToModify);
				$cmMI->saveToDB();
			}
		}

		$instancePrivQuery = "INSERT INTO `instanceprivilege`
			(`InstancePrivilegeID`, `RoleID`, `UserMIID`, `ModuleInstanceID`) VALUES
			(1, 10, 28, 28)";
		Database::getInstance()->query($instancePrivQuery);
	}
?>
