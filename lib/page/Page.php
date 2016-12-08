<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("lib/Form/Form.php");
	require_once("lib/sessions/Logon.php");
	require_once("lib/Listing/Listing.php");
	require_once("lib/page/NavMenu.php");

	/// This class is designed to make creating the full HTML page simple and consistent
	class Page{
		protected $pageTitle_,
				$pageName_,
				$pageContents_,
				$forceLogin_;

		/// Creates and loads a page from the DB
		public static function createPage($pageName, $moduleInstanceID=NULL){
			$pageQuery = NULL;
			if(isset($pageName))
				$pageQuery = sprintf(
					"SELECT PageTitle,Removable,PageName,ModuleID,ForceLogin
					FROM Page
					WHERE PageName='%s'",
					mysql_real_escape_string($pageName));
			else
				throw new InvalidArgumentException("Page object constructor cannot be called with 0 arguments");

			$pageObj = Database::getInstance()->query($pageQuery, 1, 1)->fetch_object();
			$pageName = $pageObj->PageName;
			$pageContents = NULL;
			$moduleID = $pageObj->ModuleID;

			$formIDQuery = sprintf(
					"SELECT FormID
					FROM Form
					WHERE PageName='%s'",
					mysql_real_escape_string($pageName));
			$formIDResult = Database::getInstance()->query($formIDQuery);

			$listIDQuery = sprintf(
					"SELECT ListingID
					FROM Listing
					WHERE PageName='%s'",
					mysql_real_escape_string($pageName));
			$listIDResult = Database::getInstance()->query($listIDQuery);

			// Each page will be associated with one form or listing
			if($formIDResult->num_rows == 1){
				if($pageName == "Logon")
					$newForm = new LogonForm($formIDResult->fetch_object()->FormID, $moduleInstanceID, $moduleID);
				else
					$newForm = new Form($formIDResult->fetch_object()->FormID, $moduleInstanceID, $moduleID);
				$pageContents = $newForm->toHTML();
			}
			elseif($listIDResult->num_rows == 1){
				$newListing= new Listing($listIDResult->fetch_object()->ListingID, $moduleInstanceID);
				$pageContents = $newListing->toHTML();
			}
			else
				throw new InvalidArgumentException("No form or listing found for pagename: " . $pageName);

			return new Page($pageObj->PageTitle, $pageName, $pageContents, $pageObj->ForceLogin);
		}

		/// @param $forceLogin The number of seconds that are allowed to pass since login in order
		///  to be allowed to access this page.  Unlimited (ie no login required) if 0.
		public function __construct($pageTitle, $pageName, $pageContents, $forceLogin){
			$this->pageTitle_ = $pageTitle;
			$this->pageName_ = $pageName;
			$this->forceLogin_ = $forceLogin;
			$this->pageContents_ = $pageContents;
		}
		public function toHTML(){
			$output = $this->pageHeader() . "\n";
			$output .= $this->pageContents_;
			$output .= "\n" . $this->pageFooter();

			return $output;
		}

		// TODO: Use Google CDN to load jQuery library
		// https://code.google.com/apis/libraries/devguide.html
		protected function pageHeader(){
			// TODO: Make the menu name a parameter to this function, in case we want
			//  to provide a different menu for external pages
			$navigation = new NavMenu('Default');
			$iniArray = Utils::iniSettings();

			$output = <<<EOD
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
<title>{$iniArray['orgName']} - {$this->pageTitle()}</title>
<link rel="stylesheet" type="text/css" href="css/mems_style.css" />
<link rel="stylesheet" type="text/css" href="css/jquery-ui-1.8.10.custom.css" />
<link rel="stylesheet" type="text/css" href="css/ui.daterangepicker.css" />
<link rel="stylesheet" type="text/css" href="css/jquery.fileupload-ui.css" />
<script type="text/javascript" src="javascript/jquery-1.5.1.min.js"></script>
<script type="text/javascript" src="javascript/jquery-ui-1.8.10.custom.min.js"></script>
<script type="text/javascript" src="javascript/jquery.qtip.min.js"></script>
<script type="text/javascript" src="javascript/mems_javascript.js"></script>
<script type="text/javascript" src="javascript/jquery.daterangepicker.js"></script>
<script type="text/javascript" src="javascript/jquery.json.js"></script>
<script type="text/javascript" src="javascript/jquery.jec-1.3.1.js"></script>
<script type="text/javascript" src="javascript/jquery.fileupload.js"></script>
<script type="text/javascript" src="javascript/jquery.fileupload-ui.js"></script>
</head>
<body>
<div id="header" class="header">
<div id="header_inner">

</div>
<div id="navigation">
<div id="navButtons">
{$navigation->toHTML()}
</div>
<div id="activeRoleButton"></div>
<div style='clear:both;'></div>
</div>
</div>
<div id="main">
<h3> {$this->pageTitle()} </h3>
EOD;
			return $output;
		}
		protected function pageFooter(){
			// TODO: Implement the 'About' and 'Feeback'' buttons
			$output = <<<EOD
</div>
<div id="footer" class="footer" >
Copyright 2011 MEMS | About MEMS | Feedback
</div>
</body>
</html>
EOD;
			return $output;
		}
		protected function pageTitle(){ return $this->pageTitle_; }
		public function forceLogin(){ return $this->forceLogin_; }
	}
?>
