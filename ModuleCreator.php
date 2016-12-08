<?php
	ob_start('ob_gzhandler');

	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Exception.php");
	require_once("lib/Database.php");
	require_once("lib/Security/Security.php");
	require_once("lib/editors/ModuleCreatorPage.php");
	require_once("lib/sessions/UserSession.php");
	require_once("lib/sessions/ProxyCheck.php");

	if(count(ProxyCheck::checkHeaders($_SERVER)) >= 1)
		throw new LogonException("Attempting to access site behind a proxy. IID($pageInstanceID); Page($pageName); PID($pageID)");

	// Check for a valid session
	$userSession = new User();

	$page = new ModuleCreatorPage("ModuleCreator");
	
	if($page->forceLogin() != 0 && ($userSession->timestamp() + $page->forceLogin() < time() || !$userSession->logged())){
		$userSession->logOut();
		$page = Page::createPage("Logon");
	}

	print($page->toHTML());

	ob_end_flush();
?>