<?php
	// TODO: Disable errors from being shown to the user
	// error_reporting(0);
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("lib/Security/Audit.php");
	require_once("lib/Security/Roles.php");
	require_once("lib/Exception.php");
	require_once("lib/security/InstancePrivileges.php");
	require_once("lib/sessions/UserSession.php");

	class PrivilegeException extends CustomException {}
	
	// PENDING: Save a cache of the current user's roles on the server to reduce
	//	DB pressure.
	class Security{
		private static $userRoles = array();	/// An array of <userMIID => Roles>
		private static $instanceRoles = array();	//
		private static $disableSecurity = false;
		public static function disableSecurityIs($disableSecurity){ self::$disableSecurity = $disableSecurity; }

		/// Initializes and loads all roles for the given user
		/// @param $userMIID The user in question; if NULL, use the current user
		private static function initRoles(&$userMIID){
			if($userMIID == NULL)
				$userMIID = self::userMIID();
			if(!isset(self::$userRoles[$userMIID])){
				self::$userRoles[$userMIID] = new Roles($userMIID);
			}
		}
		
		/// @param $userMIID Whether the list will contain all possible roles,
		///		or just the roles currently given to the specified user.
		/// @return Array of RoleID => RoleName
		public static function roleList($includeInactive=false, $userMIID=NULL){
			$userRolesQuery = "";
			if(!isset($userMIID))
				$userRolesQuery = sprintf(
					"SELECT RoleID,RoleName
					FROM Role");
			else
				$userRolesQuery = sprintf(
					"SELECT Role.RoleID,Role.RoleName
					FROM UserRole JOIN Role ON Role.RoleID=UserRole.RoleID
					WHERE UserMIID='%s'",
					mysql_real_escape_string($userMIID));

			$activeRoles = array();
			if(isset($_SESSION["ActiveRoles"]))
				$activeRoles = explode("##", $_SESSION["ActiveRoles"]);

			$userRolesResult = Database::getInstance()->query($userRolesQuery);
			$roleNames = array();
			while($userRoleObj = $userRolesResult->fetch_object())
				if(!isset($_SESSION["ActiveRoles"]) || in_array($userRoleObj->RoleID, $activeRoles) || $includeInactive)
					$roleNames[$userRoleObj->RoleID] = $userRoleObj->RoleName;

			return $roleNames;
		}

		/// @param $moduleInstanceID The ModuleInstanceID we are attempting to access.
		///  Not applicable for all privilege types.
		/// @param $userMIID The ModuleInstanceID associated with a user.  If NULL
		///		will use current session values.
		/// @return Whether the specified privilege exists (True or False).
		public static function privilege($privilege, $moduleInstanceID=false, $userMIID=NULL){
			if(self::$disableSecurity)
				return true;
			self::initRoles($userMIID);
			return self::$userRoles[$userMIID]->privilege($privilege, $moduleInstanceID);
		}
		/// @return The ModuleInstanceID associated with the current user.  Returns
		///		NULL if none found.
		public static function userMIID(){
			if(isset($_SESSION['uid']))
				return $_SESSION['uid'];
			else
				return 0;
		}
	}

	// One could argue that $task should be an ENUM instead of a STRING.  I agree
	//  in general, however this could lead to inconsistencies between what data
	//  in the DB is supposed to represent, and what the PHP code interprets it
	//  as, if the ENUM (and in particular, it's order) was modified.
?>
