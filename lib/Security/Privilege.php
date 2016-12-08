<?php
	/// Note: Since PHP does not allow abstract static functions, tasks() is not a
	///  a function of this class, however, it must be implemented in each of
	///  its children
	abstract class Privilege{
		protected $value_, $task_;
		public function __construct($task){
			$this->value_ = true;
			$this->task_ = $task;
		}
		public function valueIs($value){ $this->value_ = $value; }
		public function value(){ return $this->value_; }
		public function task(){ return $this->task_; }

		/// Saves this privilege to the DB
		abstract public function saveToDB($roleID);

		/// Returns an unique string representing this privilege.  This is so
		///  we can store and access privileges from a hashmap, using this as the key.
		abstract public function toString();
		// abstract public static function tasks();
	}
	class ModuleFieldPrivilege extends Privilege{
		static public function tasks(){ 
			return array(
				"Read" => 'Read',
				"Write" => "Write",
				"Create" => "Create");
			}
		private $moduleFieldID_;

		/// @param $task An entry from the tasks() array
		/// @param $moduleFieldID The ID associated with some Module Field
		public function  __construct($task, $moduleFieldID) {
			parent::__construct($task);
			$this->moduleFieldID_ = $moduleFieldID;
		}
		public function task(){ return $this->task_; }
		public function moduleFieldID(){ return $this->moduleFieldID_; }
		public function toString(){ return "ModuleField:" . $this->moduleFieldID_ . ":" . $this->task_; }
		public function saveToDB($roleID){
			if($this->value())
				$query = sprintf("
					INSERT INTO MFPrivilege
					(`RoleID`, `ModuleFieldID`, `Task`) VALUES
					('%s', '%s', '%s')
					ON DUPLICATE KEY UPDATE `RoleID`=`RoleID`",
					mysql_real_escape_string($roleID),
					mysql_real_escape_string($this->moduleFieldID()),
					mysql_real_escape_string($this->task()));
			else
				$query = sprintf("
					DELETE FROM MFPrivilege
					WHERE RoleID='%s' AND ModuleFieldID='%s' AND Task='%s'",
					mysql_real_escape_string($roleID),
					mysql_real_escape_string($this->moduleFieldID()),
					mysql_real_escape_string($this->task()));

			Database::getInstance()->query($query);
		}
	}

	class ModulePrivilege extends Privilege{
		static public function tasks(){
			return array(
				"EditModuleProperties" => "Can modify general Module attributes",
				"TransferRole" => "Can transfer Role privileges to another via Instance Privileges",
				"CreateField" => "Can create new module fields",
				"DeleteField" => "Can delete existing module fields",
				"CreateInstance" => 'Can create new instances',
				"DeleteInstance" => "Can delete existing instances",
				"CreateList" => "Can create new associated Listings",
				"CreateForm" => "Can create new associated Forms",
				"DeleteList" => "Can delete existing associated Forms",
				"DeleteForm" => "Can delete existing associated listings",
				"EditList" => "Can edit the attributes of associated Listings",
				"EditForm" => "Can edit the attributes of associated Forms");
		}
		private $moduleID_;

		/// @param $task An entry from the tasks() array
		/// @param $moduleID The ID associated with some Module
		public function  __construct($task, $moduleID) {
			parent::__construct($task);
			$this->moduleID_ = $moduleID;
		}
		public function task(){ return $this->task_; }
		public function moduleID(){ return $this->moduleID_; }
		public function toString(){ return "Module:" . $this->moduleID_ . ":" . $this->task_; }
		public function saveToDB($roleID){
			if($this->value())
				$query = sprintf("
					INSERT INTO ModulePrivilege
					(`RoleID`, `ModuleID`, `Task`) VALUES
					('%s', '%s', '%s')
					ON DUPLICATE KEY UPDATE `RoleID`=`RoleID`",
					mysql_real_escape_string($roleID),
					mysql_real_escape_string($this->moduleID()),
					mysql_real_escape_string($this->task()));
			else
				$query = sprintf("
					DELETE FROM ModulePrivilege
					WHERE RoleID='%s' AND ModuleID='%s' AND Task='%s'",
					mysql_real_escape_string($roleID),
					mysql_real_escape_string($this->moduleID()),
					mysql_real_escape_string($this->task()));

			Database::getInstance()->query($query);
		}
	}

	class GeneralPrivilege extends Privilege{
		public static function tasks(){ 
			return array("EditRole" => "Can modify the privileges accorded to a Role",
						"ChangeRoles" => "Can change others' roles",
						"CreateRole" => "Can change others' roles",
						"DeleteRole" => "Can change others' roles",
						"NavBarEdit" => "Can edit the navigation bar",
						"CreateModule" => "Can create new modules",
						"DeleteModule" => "Can delete existing modules",
						"Logon" => "Can logon to the web site");
		}

		/// @param $task An entry from the tasks() array
		///		Note: a user can only add privileges to others that they themselves have.
		public function  __construct($task) {
			parent::__construct($task);
		}
		public function toString(){ return "General: " . $this->task_; }
		public function saveToDB($roleID){
			if($this->value())
				$query = sprintf("
					INSERT INTO GeneralPrivilege
					(`RoleID`, `Task`) VALUES
					('%s', '%s')
					ON DUPLICATE KEY UPDATE `Task`=`Task`",
					mysql_real_escape_string($roleID),
					mysql_real_escape_string($this->task()));
			else
				$query = sprintf("
					DELETE FROM GeneralPrivilege
					WHERE RoleID='%s' AND Task='%s'",
					mysql_real_escape_string($roleID),
					mysql_real_escape_string($this->task()));

			Database::getInstance()->query($query);
		}
	}
?>
