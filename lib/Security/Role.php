<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("lib/Security/Audit.php");
	require_once("lib/Security/Privilege.php");

	/// Represents a single role a user might have
	class Role{
		private static $roles;
		/// Descriptor-style Role creator.  If it has not alreay been loaded from
		///  the DB, load and return the requested RoleID
		public static function createRole($roleID, $loadPermissions=true){
			if(isset(self::$roles[$roleID]))
				return self::$roles[$roleID];
			
			$roleQuery = sprintf(
				"SELECT RoleName,Description
				FROM Role
				WHERE RoleID='%s'",
				mysql_real_escape_string($roleID));
			$roleObj = Database::getInstance()->query($roleQuery, 1, 1)->fetch_object();

			$privileges = array();
			if($loadPermissions){
				$mfPrivilegeQuery = sprintf(
					"SELECT ModuleFieldID, Task
					FROM MFPrivilege
					WHERE RoleID='%s'",
					mysql_real_escape_string($roleID));
				$mfPrivilegeResult = Database::getInstance()->query($mfPrivilegeQuery);
				while($mfPrivilegeObj = $mfPrivilegeResult->fetch_object()){
					$newPriv = new ModuleFieldPrivilege($mfPrivilegeObj->Task, $mfPrivilegeObj->ModuleFieldID);
					$privileges[$newPriv->toString()] = $newPriv;
				}

				$modulePrivilegeQuery = sprintf(
					"SELECT ModuleID, Task
					FROM ModulePrivilege
					WHERE RoleID='%s'",
					mysql_real_escape_string($roleID));
				$modulePrivilegeResult = Database::getInstance()->query($modulePrivilegeQuery);
				while($modulePrivilegeObj = $modulePrivilegeResult->fetch_object()){
					$newPriv = new ModulePrivilege($modulePrivilegeObj->Task, $modulePrivilegeObj->ModuleID);
					$privileges[$newPriv->toString()] = $newPriv;
				}

				$generalPrivilegeQuery = sprintf(
					"SELECT Task
					FROM GeneralPrivilege
					WHERE RoleID='%s'",
					mysql_real_escape_string($roleID));
				$generalPrivilegeResult = Database::getInstance()->query($generalPrivilegeQuery);
				while($generalPrivilegeObj = $generalPrivilegeResult->fetch_object()){
					$newPriv = new GeneralPrivilege($generalPrivilegeObj->Task);
					$privileges[$newPriv->toString()] = $newPriv;
				}
			}
			$newRole = new Role($roleObj->RoleName, $roleObj->Description, $privileges, $roleID);
			self::$roles[$roleID] = $newRole;
			return $newRole;
		}
		
		private $privileges_, $roleName_, $roleID_, $description_;
		public function __construct($roleName, $description, $privileges, $roleID=NULL){
			$this->roleID_ = $roleID;
			$this->roleName_ = $roleName;
			$this->privileges_ = $privileges;
			$this->description_ = $description;
		}

		/// Saves the properties of this Role, and all included privileges
		///  to the database.
		public function saveToDB(){
			if($this->roleID_ == NULL){
				$insertQuery = sprintf(
					"INSERT INTO Role
					(`RoleName`, `Description`) VALUES
					('%s', '%s')",
					mysql_real_escape_string($this->roleName_),
					mysql_real_escape_string($this->description_));
				Database::getInstance()->query($insertQuery, 2, 1);
			}
			else{
				$updateQuery = sprintf(
					"UPDATE Role
					SET RoleName='%s', Description='%s'
					WHERE RoleID='%s'",
					mysql_real_escape_string($this->roleName()),
					mysql_real_escape_string($this->description()),
					mysql_real_escape_string($this->roleID()));
				Database::getInstance()->query($updateQuery);
			}
			foreach($this->privileges_ as $privilege)
				$privilege->saveToDB($this->roleID_);
		}

		public function privilegeIs($privilege){
			$this->privileges_[$privilege->toString()] = $privilege;
		}

		/// @return True if the privilege exists, false otherwise
		public function privilege($privilege){
			if(isset($this->privileges_[$privilege->toString()]) && $this->privileges_[$privilege->toString()]->value())
				return true;
			return false;
		}

		public function roleName(){ return $this->roleName_; }
		public function roleID(){ return $this->roleID_; }
		public function description(){ return $this->description_; }
	}
?>
