<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("lib/Security/Audit.php");
	require_once("lib/Security/Role.php");
	require_once("lib/sessions/UserSession.php");

	/// A collection of roles
	class Roles{
		private $roles_,	/// An array of Role Objects
				$roleUsage_, /// Array of roleID => array of moduleInstanceIDs, or true
				$userMIID_;

		/// @param $userMIID The ModuleInstanceID associated with a user.
		/// @param $roleNames (String Array) Roles we want to apply for this session.
		///		If NULL, load all available.
		public function __construct($userMIID, $roleIDs=NULL){
			$this->userMIID_ = $userMIID;
			$this->roles_ = array();
			$this->roleUsage_ = array();

			// When no user is logged-in, use the "Anonymous user"
			// TODO: Instead, should just use RoleID='1', in case the name is changed
			if($userMIID == 0){
				$userRolesQuery = sprintf(
					"SELECT RoleID
					FROM Role
					WHERE RoleName='Anonymous'");
				$userRoleObj = Database::getInstance()->query($userRolesQuery, 2, 1)->fetch_object();
				$role = Role::createRole($userRoleObj->RoleID);
				$this->roles_[$userRoleObj->RoleID] = $role;
				$this->roleUsage_[$userRoleObj->RoleID] = true;

				return ;
			}

			// Load-up the instance Privilege roles
			$instanceRolesQuery = sprintf(
				"SELECT RoleID, ModuleInstanceID
				FROM InstancePrivilege
				WHERE UserMIID='%s'",
				mysql_real_escape_string($userMIID));
			$instanceRolesResult = Database::getInstance()->query($instanceRolesQuery);

			while($instanceRoleObj = $instanceRolesResult->fetch_object()){
				$newRole = Role::createRole($instanceRoleObj->RoleID);
				$this->roles_[$instanceRoleObj->RoleID] = $newRole;
				if(!isset($this->roleUsage_[$instanceRoleObj->RoleID]))
					$this->roleUsage_[$instanceRoleObj->RoleID] = array();

				$this->roleUsage_[$instanceRoleObj->RoleID][] = $instanceRoleObj->ModuleInstanceID;
			}
			
			$userRolesQuery = sprintf(
				"SELECT RoleID
				FROM UserRole
				WHERE UserMIID='%s'",
				mysql_real_escape_string($userMIID));
			$userRolesResult = Database::getInstance()->query($userRolesQuery);

			while($userRoleObj = $userRolesResult->fetch_object()){
				if(!isset($roleNames) || array_search($userRoleObj->RoleID, $roleIDs) !== false){
					$role = Role::createRole($userRoleObj->RoleID);
					$this->roles_[$userRoleObj->RoleID] = $role;
					$this->roleUsage_[$userRoleObj->RoleID] = true;
				}
			}
		}

		public function saveToDB(){}

		/// @return TRUE if privilege exists, false otherwise
		public function privilege($privilege, $moduleInstanceID=false){
			// Only search through the instance roles of the moduleInstanceID is set/right
			foreach($this->roleUsage_ as $roleID => $moduleInstanceIDs){
				if(!isset($_SESSION["ActiveRoles"]) || array_search($roleID, explode("##", $_SESSION["ActiveRoles"])) !== false){
					// If we can use this role for all moduleInstanceIDs,
					// If we don't care about instance privileges (or at least this privilege type doesn't care)
					// Or if the current MIID is one for which we have a valid role
					if($moduleInstanceIDs === true
							|| $moduleInstanceID === false
							|| array_search($moduleInstanceID, $moduleInstanceIDs) !== false)
						if($this->roles_[$roleID]->privilege($privilege))
							return true;
				}
			}

			return false;
		}
	}
?>
