<?php
	ob_start('ob_gzhandler');

	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Exception.php");
	require_once("lib/editors/FormEditorPage.php");
	require_once("lib/sessions/UserSession.php");
	require_once("lib/sessions/ProxyCheck.php");

	if(count(ProxyCheck::checkHeaders($_SERVER)) >= 1)
		throw new LogonException("Attempting to access site behind a proxy: Permissions Page MID: ($moduleID)");

	$formID = isset($_GET["formID"]) && $_GET["formID"] != "" ? $_GET["formID"] : NULL;
	if(isset($formID) && preg_match('/^[0-9]{1,10}$/', $formID) != 1)
		throw new InvalidArgumentException("Invalid formID: $formID");
	$pageName = isset($_GET["Page"]) && $_GET["Page"] != "" ? $_GET["Page"] : NULL;
	if(isset($pageName) && preg_match('/^[a-z0-9A-Z]{1,20}$/', $pageName) != 1)
		throw new InvalidArgumentException("Invalid Page Name: $pageName");

	$page = NULL;
	if(isset($formID) && isset($pageName))
		$page = FormEditorPage::formEditor($pageName, $formID);
	else
		$page = FormEditorPage::formListPage();

	$userSession = new User();
	if($page->forceLogin() != 0 && ($userSession->timestamp() + $page->forceLogin() < time() || !$userSession->logged())){
		$userSession->logOut();
		$page = Page::createPage("Logon");
	}

	print($page->toHTML());

	ob_end_flush();
?>
