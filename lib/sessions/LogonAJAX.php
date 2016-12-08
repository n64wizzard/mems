<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Database.php");
	require_once("lib/Security/Security.php");
	require_once("lib/sessions/UserSession.php");
	require_once("lib/sessions/ZebraSession.php");
	require_once("lib/sessions/ProxyCheck.php");

	/// Check that the username and password were correct.  If not, we return false,
	///	 and force the user to log-in again.  Otherwise, we return true which forwards the
	///  the user to the page they had originally requested
	/// @return "1" upon success, otherwise can return any other string
	function submitCredentials($userName, $password, $remember){
		$userSession = new User();
		try{
			return $userSession->authenticateLogon($userName, $password, $remember);
		}catch(LogonException $e){
			Audit::logError($e);
			return 'Error';
		}
	}
	
	if(isset($_POST['command'])){
		if($_POST['command'] == 'login'){
			$userName = isset($_POST["username"]) ? $_POST["username"] : NULL;
			$password = isset($_POST["password"]) ? $_POST["password"] : NULL;
			$remember = false;//isset($_GET["value"]) ? $_GET["value"] : NULL;
			print(submitCredentials($userName, $password, $remember));
		}
	}
?>
