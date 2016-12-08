<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("lib/Exception.php");
	require_once("lib/module/ModuleInstance.php");
	require_once("lib/sessions/ZebraSession.php");
	$session = new Zebra_Session();

	class LogonException extends CustomException{}
	class User{
		private static $cookieName_ = "memsLogin";
		private $failedLoginAttempt_, $userID_;

		/// Sets all session variables to their defaults, which in-effect,
		///  logs-out the user
		static public function sessionDefaults(){
			$_SESSION['logged'] = false;
			$_SESSION['uid'] = 0;
			$_SESSION['username'] = '';
			$_SESSION['cookie'] = 0;
			$_SESSION['timestamp'] = 0;
			$_SESSION['remember'] = false;
			unset($_SESSION["ActiveRoles"]);
		}

		/// Creates the object, and determines whether or not this user is already logged-in
		public function __construct(){
			Security::disableSecurityIs(true);
			$this->userID_ = "";
			$this->failedLoginAttempt_ = false;
			Database::getInstance();
			
			try{
				if(isset($_SESSION['logged']) && $_SESSION['logged'] === true)
					$this->checkSession();
				elseif(isset($_COOKIE[User::$cookieName_]) && $_COOKIE[User::$cookieName_] !== 0)
					$this->checkRemembered($_COOKIE[User::$cookieName_]);
			}
			catch(LogonException $e){ Audit::logError($e); }
			Security::disableSecurityIs(false);
		}
		
		/// Checks that the user has logged-in, has not just failed a logon attempt, and has permission to log-in.
		/// @param True if logged-in, false if not
		public function logged(){
			if(!isset($_SESSION['logged']) || !isset($_SESSION['uid']))
				return false;
			

			return $_SESSION['logged'] == true &&
					$this->failedLoginAttempt_ != true &&
					Security::privilege(new GeneralPrivilege("Logon"), $_SESSION['uid']);
		}

		/// @param returns the Module Instance ID associated with the specified user name
		private static function userMIID($userName){
			$iniArray = Utils::iniSettings();
			$userCheckQuery = sprintf(
					"SELECT MI.ModuleInstanceID, MI.ModuleID
					FROM ModuleInstance AS MI JOIN ModuleFieldInstance AS MFI ON MI.ModuleInstanceID=MFI.ModuleInstanceID
					JOIN ModuleField AS MF ON MFI.ModuleFieldID=MF.ModuleFieldID
					WHERE MF.Name='UserName' AND AES_DECRYPT(MFI.Value, '%s')='%s'",
					mysql_real_escape_string($iniArray["passCode"]),
					mysql_real_escape_string($userName));
					//mysql_real_escape_string(Utils::encrypt($userName)));
			$userCheckResult = Database::getInstance()->query($userCheckQuery);

			if($userCheckResult->num_rows != 1)
				return NULL;
			
			$userCheckObj = $userCheckResult->fetch_object();
			return $userCheckObj->ModuleInstanceID;
		}

		/// Checks submitted credentials against the database values
		/// @param $remember Whether to not to set a cookie to avoid having to
		///  to type in the username/password again soon
		/// @return True upon success, otherwise throws an exception
		public function authenticateLogon($userName, $password, $remember){
			$userMIID = self::userMIID($userName);
			if(!isset($userMIID))
				throw new LogonException("Invalid username attempt: $userName");

			$_SESSION['uid'] = $userMIID;
			if(!Security::privilege(new GeneralPrivilege("Logon"), false, $_SESSION['uid']))
				throw new LogonException("Insufficient privileges to logon: " . $_SESSION['uid']);

			// We have to disable security here because its complicated to ensure
			// we have the right privileges to the fields we need
			Security::disableSecurityIs(true);
			$moduleInstance = ModuleInstance::createModuleInstance($userMIID);
			$password = Utils::hashPassword($password);

			if($moduleInstance->moduleFieldInstance(NULL, "Password")->currentValue() == $password){
				$this->setSession($moduleInstance, $userName, $moduleInstance->moduleFieldInstance(NULL, "CurrentCookie")->currentValue(), $remember);
				$_SESSION['timestamp'] = time();
				Security::disableSecurityIs(false);

				return true;
			}
			else{
				$this->failedLoginAttempt_ = true;
				$this->logout();
				Security::disableSecurityIs(false);
				throw new LogonException("Invalid username/password combination");
			}
		}
		
		// @param $userID The ModuleInstanceID assocated with this user
		protected function setSession(ModuleInstance $moduleInstance, $username, $cookie, $remember, $init=true){
			$this->userID_ = $moduleInstance->moduleInstanceID();
			$_SESSION['uid'] = $this->userID_;
			$_SESSION['username'] = $username;
			$_SESSION['cookie'] = $cookie;
			$_SESSION['logged'] = true;

			if($remember)
				$this->updateCookie($cookie, true);
			if($init) {
				$moduleInstance->moduleFieldInstance(NULL, "CurrrentSession")->currentValueIs(session_id());
				$moduleInstance->moduleFieldInstance(NULL, "IPAddress")->currentValueIs($_SERVER['REMOTE_ADDR']);
				$moduleInstance->saveToDB();
			}
		}

		/// Updates the cookie on the user's computer
		protected function updateCookie($cookie, $save){
			$_SESSION['cookie'] = $cookie;
			if($save){
				// TODO: Probably should store cookie as a hash on the client
				$cookie = serialize(array($_SESSION['username'], $cookie));
				setcookie(User::$cookieName_, $cookie, time() + 31104000);
			}
		}

		/// Checks to see if the user has a valid cookie set
		protected function checkRemembered($cookie) {
			list($username, $cookie) = @unserialize($cookie);
			if(!$username or !$cookie)
				throw new LogonException("Username or cookie not found");

			$userMIID = self::userMIID($userName);
			if(isset($userMIID)){
				$moduleInstance = ModuleInstance::createModuleInstance($userMIID);
				$cookieFieldInstance = $moduleInstance->moduleFieldInstance(NULL, "CurrentCookie");

				if($cookieFieldInstance->currentValue() == $cookie)
					$this->setSession($moduleInstance, $username, $cookie, true);
			}
		}

		/// @return the timestamp of this session, or 0 if not logged-in
		public function timestamp(){
			return isset($_SESSION['timestamp']) ? $_SESSION['timestamp'] : 0;
		}
		public function logOut(){
			self::sessionDefaults();
		}

		/// Checks the session variables to make sure we should not be asked to
		///  log-in
		protected function checkSession() {
			if(!isset($_SESSION['username']) || $_SESSION['username'] == '')
				$this->logOut();

			$userMIID = self::userMIID($_SESSION['username']);
			if(!isset($userMIID))
				$this->logOut();
			else{
				$moduleInstance = ModuleInstance::createModuleInstance($userMIID);
				if($moduleInstance->moduleFieldInstance(NULL, "CurrrentSession")->currentValue() != session_id())
					$this->logOut();
				// TODO: Probably should store cookie as a hash on the client
				if($moduleInstance->moduleFieldInstance(NULL, "CurrentCookie")->currentValue() != $_SESSION['cookie'])
					$this->logOut();
				if($moduleInstance->moduleFieldInstance(NULL, "IPAddress")->currentValue() != $_SERVER['REMOTE_ADDR'])
					$this->logOut();

				$this->setSession($moduleInstance, $_SESSION['username'], $_SESSION['cookie'], false, false);
			}
		}
	}
?>
