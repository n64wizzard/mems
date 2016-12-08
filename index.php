<?php
	ob_start('ob_gzhandler');

	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Exception.php");
	require_once("lib/Database.php");
	require_once("lib/Security/Security.php");
	require_once("lib/page/Page.php");
	require_once("lib/sessions/UserSession.php");
	require_once("lib/sessions/ProxyCheck.php");

	$pageName = isset($_GET["Page"]) && $_GET["Page"] != "" ? $_GET["Page"] : NULL;
	$moduleInstanceID = isset($_GET["MIID"]) && $_GET["MIID"] != "" ? $_GET["MIID"] : NULL;

	if(count(ProxyCheck::checkHeaders($_SERVER)) >= 1)
		throw new LogonException("Attempting to access site behind a proxy. IID($pageInstanceID); Page($pageName); PID($pageID)");

	// Check for a valid session
	$userSession = new User();

	$page = NULL;
	if(isset($moduleInstanceID) && preg_match('/^[0-9]{1,10}$/', $moduleInstanceID) != 1)
		throw new InvalidArgumentException("Invalid ModuleInstanceID: MIID($ModuleInstanceID); Page($pageName)");

	if(isset($pageName) && preg_match('/^[a-zA-Z]{1,20}$/', $pageName) == 1)
		$page = Page::createPage($pageName, $moduleInstanceID);
	else
		throw new InvalidArgumentException("Invalid or missing PageName: "
			. "MIID(" . Utils::valueIfSet($moduleInstanceID)
			. "); Page(" . Utils::valueIfSet($pageName) . ")");

	if($page->forceLogin() != 0 && ($userSession->timestamp() + $page->forceLogin() < time() || !$userSession->logged())){
		$userSession->logOut();
		$page = Page::createPage("Logon");
	}

	print($page->toHTML());

	ob_end_flush();
?>
