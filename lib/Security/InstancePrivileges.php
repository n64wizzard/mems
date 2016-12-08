<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("lib/Security/Audit.php");

	/// A single Instance Privilege
	class InstancePrivilege{
		private $instancePrivilegeID_, $userMIID_, $roleID_, $roleName_, $moduleInstanceID_;
		public function  __construct($userMIID, $roleID, $moduleInstanceID, $roleName="", $instancePrivilegeID=NULL){
			$this->instancePrivilegeID_ = $instancePrivilegeID;
			$this->userMIID_ = $userMIID;
			$this->roleID_ = $roleID;
			$this->moduleInstanceID_ = $moduleInstanceID;
			$this->roleName_ = $roleName;
		}
		public function userMIID(){ return $this->userMIID_; }
		public function roleID(){ return $this->roleID_; }
		public function roleName(){ return $this->roleName_; }
		public function moduleInstanceID(){ return $this->moduleInstanceID_; }
		public function instancePrivilegeID(){ return $this->instancePrivilegeID_; }
		public function instancePrivilegeIDIs($instancePrivilegeID){ $this->instancePrivilegeID_ = $instancePrivilegeID; }
	}

	/// This class represents what instance privileges a given user has
	class InstancePrivileges{
		private $privileges_; /// Array of instancePrivilegeID => InstancePrivilege Object

		/// @return An InstancePrivileges object containing all instance privileges
		///  owned by the given user
		public static function createInstancePrivileges($userMIID){
			$instancePrivsQuery = sprintf(
				"SELECT *
				FROM InstancePrivilege
				WHERE UserMIID='%s'",
				mysql_real_escape_string($userMIID));
			$instancePrivsResult = Database::getInstance()->query($instancePrivsQuery);

			$privileges = array();
			while($instancePrivObj = $instancePrivsResult->fetch_object())
				$privileges[] = new InstancePrivilege($userMIID, $instancePrivObj->RoleID,
									$instancePrivObj->ModuleInstanceID, $instancePrivObj->InstancePrivilegeID);
			return new InstancePrivileges($privileges);
		}

		/// @return An InstancePrivileges object containing all instance privileges
		///  that affect the given module instance
		public static function moduleInstancePrivileges($moduleInstanceID){
			$instancePrivsQuery = sprintf(
				"SELECT *
				FROM InstancePrivilege AS IP JOIN Role AS R
					ON R.RoleID=IP.RoleID
				WHERE ModuleInstanceID='%s'",
				mysql_real_escape_string($moduleInstanceID));
			$instancePrivsResult = Database::getInstance()->query($instancePrivsQuery);

			$privileges = array();
			while($instancePrivObj = $instancePrivsResult->fetch_object())
				$privileges[] = new InstancePrivilege($instancePrivObj->UserMIID, $instancePrivObj->RoleID,
									$instancePrivObj->ModuleInstanceID, $instancePrivObj->RoleName, $instancePrivObj->InstancePrivilegeID);

			return new InstancePrivileges($privileges);
		}
		public function __construct($privileges){
			$this->privileges_ = $privileges;
		}
		public function privileges(){ return $this->privileges_; }

		/// Creates a new privilege and adds it to the DB
		public function privilegeIs($instancePrivilege){
			foreach($this->privileges_ as $privilege)
				if($privilege->roleID() == $instancePrivilege->roleID() 
						&& $privilege->moduleInstanceID() == $instancePrivilege->moduleInstanceID()
						&& $privilege->userMIID() == $instancePrivilege->userMIID())
					return "Privilege already exists";
				
			// Save new instancePrivilege to the DB
			$insertQuery = sprintf(
				"INSERT INTO InstancePrivilege
				(`RoleID`, `UserMIID`, `ModuleInstanceID`) VALUES
				('%s', '%s', '%s')",
				mysql_real_escape_string($instancePrivilege->roleID()),
				mysql_real_escape_string($instancePrivilege->userMIID()),
				mysql_real_escape_string($instancePrivilege->moduleInstanceID()));
			try{ Database::getInstance()->query($insertQuery, 2, 1); }
			catch(MySQLException $e){
				echo $e->getMessage();
				Audit::logError($e);
				return "Error saving new privilege";
			}
			$instancePrivilege->instancePrivilegeIDIs(Database::getInstance()->insertID());

			$this->privileges_[$instancePrivilege->instancePrivilegeID()] = $instancePrivilege;
			return $instancePrivilege->instancePrivilegeID();
		}

		/// Removes an instance privilege from the DB
		/// @param $ignoreExistance Whether we should try and delete the priv from the DB
		///  even if it does not exist locally
		public function deletePrivilege($instancePrivilegeID, $ignoreExistance){
			if(!isset($this->privileges_[$instancePrivilegeID]) && !$ignoreExistance)
				return "Privilege does not exist";

			// Save the instance privilege deletion to the DB
			$deleteQuery = sprintf("
				DELETE FROM InstancePrivilege
				WHERE InstancePrivilegeID='%s'",
				mysql_real_escape_string($instancePrivilegeID));
			try{ Database::getInstance()->query($deleteQuery, 2, 1); }
			catch(MySQLException $e){
				Audit::logError($e);
				return "Error deleting privilege from database";
			}
			unset($this->privileges_[$instancePrivilegeID]);

			return "";
		}
	}
?>
