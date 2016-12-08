<?php
	/// This page updates the default installation to provide an example of the functionality of the MEMS
	///  system.  It is designed to be similar in functionality to what one would hope to use at CUEMS.
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("lib/Utils.php");
	require_once("install/InitDatabase.php");

	function installCUEMSDemo(){
		// Create modules
		$defaultEntries = array(
			"INSERT INTO `module`
				(`ModuleID`, `Name`, `Removable`, `Hidden`) VALUES
				(6, 'CrewMember', b'1', b'0'),
				(9, 'PCR', b'1', b'0'),
				(10, 'TM', b'1', b'0')",
			"INSERT INTO `page`
				(`PageName`, `PageTitle`, `Removable`, `ModuleID`, `ForceLogin`, `Description`) VALUES
				('PCR', 'Patient Care Report', b'1', '9', '3600', 'Form to edit or create a new PCR.'),
				('ShiftBoard', 'Shift Board', b'1', '6', '3600', 'A listing of shifts and their crews.'),
				('PCRs', 'PCRs', b'1', '9', '3600', 'Listing of PCRs.'),
				('CrewMember', 'Crew Member', b'1', '6', '3600', 'Form to sign-up for a shift.'),
				('TM', 'Training Meeting', b'1', '10', '3600', 'Details of a TM and the ability to record the attendees.'),
				('TMList', 'Training Meetings', b'1', '10', '3600', 'Listing of Training Meetings.')",
			"DELETE FROM `navlink`",
			"INSERT INTO `navlink` (`NavLinkID`, `NavMenuName`, `Text`, `PageName`, `ModuleInstanceID`, `Position`, `Group`) VALUES
				(NULL, 'Default', 'Member List', 'MemberList', NULL, '2', NULL),
				(NULL, 'Default', 'Shift Board', 'ShiftBoard', NULL, '3', NULL),
				(NULL, 'Default', 'TMs', 'TMList', NULL, '5', NULL),
				(NULL, 'Default', 'PCRs', 'PCRs', NULL, '6', NULL),
				(NULL, 'Default', 'Modules', '##ModuleCreator.php', NULL, '7', 'Administration'),
				(NULL, 'Default', 'Forms', '##FormEditor.php', NULL, '8', 'Administration'),
				(NULL, 'Default', 'Listings', '##ListingEditor.php', NULL, '9', 'Administration'),
				(NULL, 'Default', 'Permissions', '##Permissions.php', NULL, '10', 'Administration'),
				(NULL, 'Default', 'Log Out', 'LogOut', NULL, '11', NULL),
				(NULL, 'Default', 'CUEMS', '##http://cuems.cornell.edu', NULL, '12', NULL)",
			"INSERT INTO `form`
				(`FormID`, `PageName`) VALUES
				(50, 'PCR'),
				(53, 'CrewMember'),
				(55, 'TM')",
			"INSERT INTO `ModuleField`
				(`ModuleFieldID`, `ModuleID`, `Name`, `Type`, `Description`, `Regex`, `DefaultValue`, `Label`, `Hidden`, `Unique`) VALUES
				('50', '1', 'CPRCert', 'Date', 'Expiration of CPR certification.', '/^.*$/s', '', 'CPR Expiration', b'0', b'0'),
				('51', '1', 'EMTCert', 'Date', 'Expiration of EMT certification.', '/^.*$/s', '', 'EMT-B Expiration', b'0', b'0'),
				('52', '1', 'DL', 'Text', 'Drivers License number.', '/.*/s', '', 'Drivers License', b'0', b'0'),
				('53', '1', 'DLState', 'Select', 'State in which the user is licensed.', '/.*/s', '', 'State', b'0', b'0'),
				('79', '1', 'Picture', 'FileUpload', 'A picture of this member.', '/.*/s', '', 'Picture', b'0', b'0'),
				
				('54', '6', 'StartTime', 'Text', 'Shift starting time.', '/.*/s', '', 'Start', b'0', b'0'),
				('56', '6', 'Date', 'Date', 'Shift Date.', '/.*/s', '', 'Date', b'0', b'0'),
				('57', '6', 'Category', 'Select', 'Type of shift.', '/^.*$/s', '', 'Type', b'0', b'0'),
				('78', '6', 'BriefDoc', 'FileUpload', 'Standard document describing the circumstances of a shift or event.', '/^.*$/s', '', 'Briefing Document', b'0', b'0'),
				('58', '6', 'Details', 'TextArea', 'Any special information or details for this shift.', '/^.*$/s', '', 'Additional Details', b'0', b'1'),
				('59', '6', 'Position', 'Select', 'Which position this member fulfills.', '/^.*$/s', '', 'Position', b'0', b'0'),
				('60', '6', 'Level', 'SelectRoles', 'The desired qualifications of this member.', '/^.*$/s', '', 'Level', b'0', b'0'),
				('61', '6', 'Member', 'SelectMembers', 'The member chosen for this shift.', '/^.*$/s', '', 'Member', b'0', b'0'),

				('69', '9', 'Shift', 'PageLink', 'Shift on which this PCR occurred.', '/^.*$/s', '', 'Shift', b'0', b'0'),
				('70', '9', 'PtAge', 'Select', 'Approximate patient age.', '/^.*$/s', '', 'Age', b'0', b'0'),
				('71', '9', 'NOI', 'ComboBox', 'Nature of Illness or Mechanism of Injury.', '/^.*$/s', '', 'NOI/MOI', b'0', b'0'),
				('72', '9', 'Disposition', 'Select', 'Ultimate destination or outcome of this patient.', '/^.*$/s', '', 'Disposition', b'0', b'0'),
				('80', '9', 'PCRID', 'Text', 'The PCR ID of this entry.', '/^[0-9]*$/s', '', 'PCR ID', b'0', b'1'),

				('73', '10', 'StartTime', 'Text', 'TM starting time.', '/.*/s', '', 'Start', b'0', b'0'),
				('74', '10', 'EndTime', 'Text', 'TM ending time.', '/.*/s', '', 'End', b'0', b'0'),
				('75', '10', 'Date', 'Date', 'TM Date.', '/.*/s', '', 'Date', b'0', b'1'),
				('76', '10', 'Members', 'PageLink', 'Members in attendance.', '/^.*$/s', '', 'Members', b'0', b'0'),
				('77', '10', 'Topic', 'TextArea', 'Any special information or details for this TM.', '/^.*$/s', '', 'Additional Details', b'0', b'0')",
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

				(NULL, '1', 'MaxFileCount', '79'),
				(NULL, '1', 'ShowPreview', '79'),
				(NULL, '1000000', 'MaxSize', '79'),

				(NULL, 'EMS1D', 'EMS-1 D', '57'),
				(NULL, 'EMS1N', 'EMS-1 N', '57'),
				(NULL, 'Bikes', 'Bikes', '57'),
				(NULL, 'EMS2', 'EMS-2', '57'),
				(NULL, 'Events', 'Events', '57'),

				(NULL, '5', 'MaxFileCount', '78'),
				(NULL, '0', 'ShowPreview', '78'),
				(NULL, '5000000', 'MaxSize', '78'),
				
				(NULL, 'CC', 'Crew Chief', '59'),
				(NULL, 'Attendant', 'Attendant', '59'),
				(NULL, 'Trainee', 'Trainee', '59'),
				(NULL, 'Driver', 'Driver', '59'),
				(NULL, 'CCIT', 'CCIT', '59'),
				(NULL, 'ACCIT', 'ACCIT', '59'),

				(NULL, '60', 'RoleFieldID', '61'),
				(NULL, '1', 'MaxRegistrations', '61'),
				(NULL, 'Pick-up Shift', 'AddText', '61'),
				(NULL, 'Drop Shift', 'DropText', '61'),
				
				(NULL, '6', 'ModuleID', '69'),
				(NULL, 'CrewMember', 'PageName', '69'),
				(NULL, '56', 'ModuleFieldID', '69'),

				(NULL, '0-17', '0-17', '70'),
				(NULL, '18-22', '18-22', '70'),
				(NULL, '22+', '22+', '70'),

				(NULL, 'Cardiac', 'Cardiac', '71'),
				(NULL, 'Stroke', 'Stroke', '71'),
				(NULL, 'Seizure', 'Seizure', '71'),
				(NULL, 'HRI', 'Heat Related Illness', '71'),
				(NULL, 'Syncope', 'Syncope', '71'),
				(NULL, 'Psychiatric', 'Psychiatric', '71'),
				(NULL, 'Diabetic', 'Diabetic Emergency', '71'),
				(NULL, 'Abdominal', 'Abdominal Pain', '71'),
				(NULL, 'SOB', 'Shiftness of Breath', '71'),
				(NULL, 'OtherMed', 'Other Medical', '71'),
				(NULL, 'ALOC', 'Altered Mental Status', '71'),
				(NULL, 'DNV', 'Dizziness/Nausea/Vomiting', '71'),
				(NULL, 'TraumaOther', 'Trauma (Other)', '71'),
				(NULL, 'TraumaLower', 'Trauma (Lower Extremities)', '71'),
				(NULL, 'TraumaUpper', 'Trauma (Upper Extremities)', '71'),
				(NULL, 'TraumaHead', 'Trauma (Head)', '71'),
				
				(NULL, 'Bangs', 'Bangs', '72'),
				(NULL, 'CMC', 'CMC', '72'),
				(NULL, 'RMA', 'RMA', '72'),

				(NULL, '1', 'ModuleID', '76'),
				(NULL, 'Profile', 'PageName', '76')",
			"INSERT INTO `listing`
				(`ListingID`, `PageName`, `MaxItems`, `NewEntryPageName`, `CreateText`) VALUES
				(7, 'ShiftBoard', '100', 'CrewMember', 'Create New'),
				(8, 'TMList', '10', 'TM', 'Create New'),
				(9, 'PCRs', '10', 'PCR', 'Create New')",
			// TODO: 
			"INSERT INTO `listfield`
				(`ListFieldID`, `Position`, `ModuleFieldID`, `ListingID`, `IncludeLabel`, `LinkPageName`, `Width`) VALUES
				(NULL, '5', '59', '7', b'1', 'CrewMember', '1'),

				(NULL, '1', '73', '8', b'0', NULL, '1'),
				(NULL, '2', '74', '8', b'0', NULL, '1'),
				(NULL, '3', '75', '8', b'0', 'TM', '1'),
				(NULL, '5', '77', '8', b'0', NULL, '1'),
				
				(NULL, '1', '69', '9', b'0', 'PCR', '1'),
				(NULL, '2', '70', '9', b'0', NULL, '1'),
				(NULL, '3', '71', '9', b'0', NULL, '1'),
				(NULL, '4', '72', '9', b'0', NULL, '1')",
			"INSERT INTO `listby`
				(`ListByID`, `Rank`, `ModuleFieldID`, `ListingID`, `Direction`, `Orientation`, `Type`) VALUES
				(NULL, '1', '59', '7', b'1', b'0', b'0'),
				(NULL, '2', '54', '7', b'1', b'0', b'1'),
				(NULL, '3', '57', '7', b'1', b'0', b'1'),
				(NULL, '4', '56', '7', b'1', b'1', b'1'),

				(NULL, '1', '75', '8', b'1', b'0', b'0'),
				
				(NULL, '1', '70', '9', b'1', b'0', b'0')",
//			"INSERT INTO `listoption`
//				(`ListOptionID`, `PageName`, `ListingID`, `Title`) VALUES
//				(NULL, 'Profile', '1', 'View'),
//				(NULL, 'Profile', '1', 'Edit'),
//				(NULL, NULL, '1', 'Delete')",
			"INSERT INTO `listfilter`
				(`ListFilterID`, `ModuleFieldID`, `ListingID`, `Value`) VALUES		
				(NULL, '56', '7', ''),
				
				(NULL, '75', '8', ''),
				
				(NULL, '69', '9', '')",
			"DELETE FROM MFPrivilege",
			"DELETE FROM ModulePrivilege",
"INSERT INTO `formfield`
(`FormFieldID`, `ModuleFieldID`, `FormID`, `Pos_Top`, `Pos_Left`, `Pos_Width`, `Pos_Height`, `Mutable`, `IncludeLabel`, `Removable`) VALUES
(NULL, 73, 55, 27, -1, 150, 22, '1', '1', '1'),
(NULL, 74, 55, 27, 190, 150, 22, '1', '1', '1'),
(NULL, 75, 55, -1, -1, 230, 22, '1', '1', '1'),
(NULL, 77, 55, 81, -1, 300, 302, '1', '1', '1'),
(NULL, 76, 55, -1, 431, 200, 400, '1', '1', '1'),
(NULL, 50, 2, 146, 13, 182, 22, '1', '1', '1'),
(NULL, 51, 2, 170, -1, 182, 22, '1', '1', '1'),
(NULL, 53, 2, 230, 265, 150, 22, '1', '1', '1'),
(NULL, 79, 2, -1, 491, 250, 250, '1', '1', '1'),
(NULL, 52, 2, 230, -1, 150, 22, '1', '1', '1'),
(NULL, 69, 50, -1, -1, 200, 100, '1', '1', '1'),
(NULL, 70, 50, 23, 681, 150, 22, '1', '1', '1'),
(NULL, 71, 50, -1, 281, 250, 100, '1', '1', '1'),
(NULL, 72, 50, -1, 629, 150, 22, '1', '1', '1'),
(NULL, 80, 50, 47, 661, 100, 22, '1', '1', '1'),
(NULL, 54, 53, 23, 0, 150, 22, '1', '1', '1'),
(NULL, 56, 53, -1, -1, 198, 22, '1', '1', '1'),
(NULL, 57, 53, 72, 25, 150, 22, '1', '1', '1'),
(NULL, 58, 53, -1, 540, 350, 210, '1', '1', '1'),
(NULL, 78, 53, 233, 540, 150, 30, '1', '1', '1'),
(NULL, 59, 53, 96, 1, 150, 22, '1', '1', '1'),
(NULL, 60, 53, 147, 263, 150, 200, '1', '1', '1'),
(NULL, 61, 53, 147, -1, 200, 200, '1', '1', '1')"
		);
		foreach($defaultEntries as $defaultQuery)
			Database::getInstance()->query($defaultQuery);

		$newRoleQuery = "INSERT INTO `Role`
			(`RoleID`, `RoleName`, `Description`) VALUES
			('5', 'Attendant', 'Allowed to pick up attendant shifts.'),
			('6', 'Trainee', 'Allowed to sign-up for all TMs and GMS; only able to pick-up trainee shifts.'),
			('7', 'Crew Chief', 'Able to sign-up for all shift-types.'),
			('8', 'Admin Officer', 'Can create new GMs and TMs.  Can edit all fields in the Member module.'),
			('9', 'Scheduling Officer', 'Can create new shifts and crews.'),
			('10', 'Edit Profile', 'Allows one to edit ones profile.'),
			('11', 'Alumni', 'May sign-in and edit their profile, but unable to sign-up for any event.'),
			('12', 'ACCIT', 'Able to sign-up for all shift-types except Crew Chief.')";
		Database::getInstance()->query($newRoleQuery);

		$generalPrivilegesQuery = "INSERT INTO `generalprivilege`
			(`GeneralPrivilegeID`, `RoleID`, `Task`) VALUES
			(NULL, 6, 'Logon'),
			(NULL, 7, 'Logon'),
			(NULL, 8, 'Logon'),
			(NULL, 9, 'Logon'),
			(NULL, 10, 'Logon'),
			(NULL, 11, 'Logon'),
			(NULL, 12, 'Logon'),
			(NULL, 5, 'Logon')";
		Database::getInstance()->query($generalPrivilegesQuery);

$mfPrivilegesQuery = "INSERT INTO `mfprivilege`
(`MFPID`, `ModuleFieldID`, `RoleID`, `Task`) VALUES
(1, 1, 1, 'Create'),
(7, 1, 2, 'Create'),
(18, 1, 2, 'Read'),
(29, 1, 2, 'Write'),
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
(12, 6, 2, 'Create'),
(23, 6, 2, 'Read'),
(34, 6, 2, 'Write'),
(775, 6, 7, 'Read'),
(13, 7, 2, 'Create'),
(24, 7, 2, 'Read'),
(35, 7, 2, 'Write'),
(776, 7, 7, 'Read'),
(3, 8, 1, 'Create'),
(14, 8, 2, 'Create'),
(25, 8, 2, 'Read'),
(36, 8, 2, 'Write'),
(777, 8, 7, 'Read'),
(4, 9, 1, 'Create'),
(15, 9, 2, 'Create'),
(26, 9, 2, 'Read'),
(37, 9, 2, 'Write'),
(778, 9, 7, 'Read'),
(5, 10, 1, 'Create'),
(16, 10, 2, 'Create'),
(27, 10, 2, 'Read'),
(38, 10, 2, 'Write'),
(779, 10, 7, 'Read'),
(6, 11, 1, 'Create'),
(17, 11, 2, 'Create'),
(28, 11, 2, 'Read'),
(39, 11, 2, 'Write'),
(780, 11, 7, 'Read'),
(357, 20, 2, 'Create'),
(355, 20, 2, 'Read'),
(356, 20, 2, 'Write'),
(360, 21, 2, 'Create'),
(358, 21, 2, 'Read'),
(359, 21, 2, 'Write'),
(363, 22, 2, 'Create'),
(361, 22, 2, 'Read'),
(362, 22, 2, 'Write'),
(366, 23, 2, 'Create'),
(364, 23, 2, 'Read'),
(365, 23, 2, 'Write'),
(369, 24, 2, 'Create'),
(367, 24, 2, 'Read'),
(368, 24, 2, 'Write'),
(372, 25, 2, 'Create'),
(370, 25, 2, 'Read'),
(371, 25, 2, 'Write'),
(375, 26, 2, 'Create'),
(373, 26, 2, 'Read'),
(374, 26, 2, 'Write'),
(252, 50, 2, 'Create'),
(250, 50, 2, 'Read'),
(251, 50, 2, 'Write'),
(255, 51, 2, 'Create'),
(253, 51, 2, 'Read'),
(254, 51, 2, 'Write'),
(258, 52, 2, 'Create'),
(256, 52, 2, 'Read'),
(257, 52, 2, 'Write'),
(261, 53, 2, 'Create'),
(259, 53, 2, 'Read'),
(260, 53, 2, 'Write'),
(75, 54, 2, 'Create'),
(73, 54, 2, 'Read'),
(74, 54, 2, 'Write'),
(789, 54, 7, 'Read'),
(81, 56, 2, 'Create'),
(79, 56, 2, 'Read'),
(80, 56, 2, 'Write'),
(790, 56, 7, 'Read'),
(84, 57, 2, 'Create'),
(82, 57, 2, 'Read'),
(83, 57, 2, 'Write'),
(791, 57, 7, 'Read'),
(87, 58, 2, 'Create'),
(85, 58, 2, 'Read'),
(86, 58, 2, 'Write'),
(792, 58, 7, 'Read'),
(90, 59, 2, 'Create'),
(88, 59, 2, 'Read'),
(89, 59, 2, 'Write'),
(793, 59, 7, 'Read'),
(93, 60, 2, 'Create'),
(91, 60, 2, 'Read'),
(92, 60, 2, 'Write'),
(794, 60, 7, 'Read'),
(96, 61, 2, 'Create'),
(94, 61, 2, 'Read'),
(95, 61, 2, 'Write'),
(795, 61, 7, 'Read'),
(489, 69, 2, 'Create'),
(487, 69, 2, 'Read'),
(488, 69, 2, 'Write'),
(839, 69, 7, 'Create'),
(837, 69, 7, 'Read'),
(838, 69, 7, 'Write'),
(492, 70, 2, 'Create'),
(490, 70, 2, 'Read'),
(491, 70, 2, 'Write'),
(842, 70, 7, 'Create'),
(840, 70, 7, 'Read'),
(841, 70, 7, 'Write'),
(495, 71, 2, 'Create'),
(493, 71, 2, 'Read'),
(494, 71, 2, 'Write'),
(845, 71, 7, 'Create'),
(843, 71, 7, 'Read'),
(844, 71, 7, 'Write'),
(498, 72, 2, 'Create'),
(496, 72, 2, 'Read'),
(497, 72, 2, 'Write'),
(848, 72, 7, 'Create'),
(846, 72, 7, 'Read'),
(847, 72, 7, 'Write'),
(162, 73, 2, 'Create'),
(160, 73, 2, 'Read'),
(161, 73, 2, 'Write'),
(812, 73, 7, 'Read'),
(165, 74, 2, 'Create'),
(163, 74, 2, 'Read'),
(164, 74, 2, 'Write'),
(813, 74, 7, 'Read'),
(168, 75, 2, 'Create'),
(166, 75, 2, 'Read'),
(167, 75, 2, 'Write'),
(814, 75, 7, 'Read'),
(171, 76, 2, 'Create'),
(169, 76, 2, 'Read'),
(170, 76, 2, 'Write'),
(815, 76, 7, 'Read'),
(174, 77, 2, 'Create'),
(172, 77, 2, 'Read'),
(173, 77, 2, 'Write'),
(816, 77, 7, 'Read'),
(99, 78, 2, 'Create'),
(97, 78, 2, 'Read'),
(98, 78, 2, 'Write'),
(796, 78, 7, 'Read'),
(264, 79, 2, 'Create'),
(262, 79, 2, 'Read'),
(263, 79, 2, 'Write'),
(781, 79, 7, 'Read'),
(NULL, 80, 2, 'Read'),
(NULL, 80, 2, 'Write'),
(NULL, 80, 7, 'Read'),
(NULL, 80, 2, 'Create')";
		Database::getInstance()->query($mfPrivilegesQuery);

		$modulePriviligesQuery = "INSERT INTO `moduleprivilege`
			(`ModulePrivilegeID`, `RoleID`, `ModuleID`, `Task`) VALUES
(NULL, 1, 1, 'CreateInstance'),
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
(NULL, 2, 1, 'EditForm'),
(38, 2, 6, 'EditModuleProperties'),
(39, 2, 6, 'TransferRole'),
(40, 2, 6, 'CreateField'),
(41, 2, 6, 'DeleteField'),
(42, 2, 6, 'CreateInstance'),
(43, 2, 6, 'DeleteInstance'),
(44, 2, 6, 'CreateList'),
(45, 2, 6, 'CreateForm'),
(46, 2, 6, 'DeleteList'),
(47, 2, 6, 'DeleteForm'),
(48, 2, 6, 'EditList'),
(49, 2, 6, 'EditForm'),
(74, 2, 10, 'EditModuleProperties'),
(75, 2, 10, 'TransferRole'),
(76, 2, 10, 'CreateField'),
(77, 2, 10, 'DeleteField'),
(78, 2, 10, 'CreateInstance'),
(79, 2, 10, 'DeleteInstance'),
(80, 2, 10, 'CreateList'),
(81, 2, 10, 'CreateForm'),
(82, 2, 10, 'DeleteList'),
(83, 2, 10, 'DeleteForm'),
(84, 2, 10, 'EditList'),
(85, 2, 10, 'EditForm'),
(158, 2, 20, 'EditModuleProperties'),
(159, 2, 20, 'TransferRole'),
(160, 2, 20, 'CreateField'),
(161, 2, 20, 'DeleteField'),
(162, 2, 20, 'CreateInstance'),
(163, 2, 20, 'DeleteInstance'),
(164, 2, 20, 'CreateList'),
(165, 2, 20, 'CreateForm'),
(166, 2, 20, 'DeleteList'),
(167, 2, 20, 'DeleteForm'),
(168, 2, 20, 'EditList'),
(169, 2, 20, 'EditForm'),
(218, 2, 9, 'EditModuleProperties'),
(219, 2, 9, 'TransferRole'),
(220, 2, 9, 'CreateField'),
(221, 2, 9, 'DeleteField'),
(222, 2, 9, 'CreateInstance'),
(223, 2, 9, 'DeleteInstance'),
(224, 2, 9, 'CreateList'),
(225, 2, 9, 'CreateForm'),
(226, 2, 9, 'DeleteList'),
(227, 2, 9, 'DeleteForm'),
(228, 2, 9, 'EditList'),
(229, 2, 9, 'EditForm')";
		Database::getInstance()->query($modulePriviligesQuery);

		$firstNames = array("Bob", "Anne", "Henry", "Steve", "Brian", "Brittany", "Mark", "Fred", "Dwight", "Chelsea", "Nicole", "Ariel");
		$lastNames = array("Williams", "Smith", "Jones", "Twain", "Washington", "Deaver", "Johnson", "Brown", "Davis", "Miller", "Wilson", "Moore");
		$userRoles = array("6##8##5", "6##9##5##7", "5##6", "5##6","5##6","5##6","5##6","5##6","5##6","5##6", "11", "11");

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
				"11" => "$userName@ems.com");
			$moduleInstance->moduleFieldValuesAre($fieldsToModify);
			$moduleInstance->saveToDB();
		}

		$dates = array("06/05/2011", "05/15/2011", "05/22/2011", "05/29/2011");
		$topics = array("Weapons of Mass Destruction", "Slope Day TM", "Bang Operations", "MCI Scenarios");

		for($i = 0; $i < 4; $i++){
			$moduleInstance = ModuleInstance::newModuleInstance(10);
			$fieldsToModify = array(
				"73" => "7:00pm",
				"74" => "8:00pm",
				"75" => $dates[$i],
				"76" => "21##22##23##24##25##26##27##28##29##30##31",
				"77" => $topics[$i]);
			$moduleInstance->moduleFieldValuesAre($fieldsToModify);
			$moduleInstance->saveToDB();
		}

		$startTimes = array("1100", "1400", "1800", "2300");
		$endTimes = array("1400", "1800", "2300", "1100");
		$dates = array("05/15/2011", "05/16/2011", "05/17/2011", "05/18/2011", "05/19/2011", "05/20/2011", "05/21/2011");
		$categories = array("EMS1D", "EMS1N", "EMS1N", "EMS1N");
		$positions = array("CC", "ACCIT", "Attendant", "Trainee");
		$levels = array("7", "12", "5", "6");
		$members = array("");

		for($i = 0; $i < 7; $i++){
			$startDate = $dates[$i];
			for($j = 0; $j < 4; $j++){
				$startTime = $startTimes[$j];
				$endTime = $endTimes[$j];
				$category = $categories[$j];
				for($k = 0; $k < 4; $k++){
					$position = $positions[$k];
					$level = $levels[$k];
					$moduleInstance = ModuleInstance::newModuleInstance(6);
					$fieldsToModify = array(
						"54" => $startTime . " - " . $endTime,
						"56" => $startDate,
						"57" => $category,
						"59" => $position,
						"60" => $level,
						"58" => $startDate . ":" . $startTime . ":" . $position,
						"61" => $members[0]);
					$moduleInstance->moduleFieldValuesAre($fieldsToModify);
					$moduleInstance->saveToDB();
				}
			}
		}
	}
?>
