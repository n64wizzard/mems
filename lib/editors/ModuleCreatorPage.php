<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("lib/Form/Form.php");
	require_once("lib/Listing/Listing.php");
	require_once("lib/page/NavMenu.php");
	require_once("lib/page/Page.php");
	require_once("lib/editors/ModuleCreatorForm.php");
	require_once("lib/Security/Security.php");

	class ModuleCreatorPage extends Page{
		private $moduleName_, $moduleCreatorID_, $editModuleID_, $nameField_;
		public function __construct($pageName){
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

			$moduleName = isset($_GET["moduleName"]) ? $_GET["moduleName"] : NULL;
			$moduleQuery = sprintf(
					"SELECT ModuleID
					FROM Module
					WHERE Name='%s'",
					mysql_real_escape_string($moduleName));
			$moduleObj = Database::getInstance()->query($moduleQuery)->fetch_object();
			$editModuleID = $moduleObj ? $moduleObj->ModuleID : NULL;
			$this->editModuleID_ = $editModuleID;
			$this->moduleName_ = $moduleName;
			$this->pageTitle_ = $pageObj->PageTitle;
			$this->pageName_ = $pageObj->PageName;
			$this->removable_ = $pageObj->Removable;
			$this->forceLogin_ = $pageObj->ForceLogin;
			$this->pageContents_ = array();
			$moduleID = $pageObj->ModuleID;
			$this->moduleCreatorID_ = $moduleID;

			$this->populatePageContents($moduleID, $editModuleID);
		}
		function curPageURL() {
			$pageURL = 'http';
			if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
			$pageURL .= "://";
			if (isset($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
			} else {
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
			}
			return $pageURL;
		}
		private function populatePageContents($moduleCreatorID, $editModuleID=NULL){
			$moduleFieldQuery = sprintf(
					"SELECT ModuleFieldID
					FROM ModuleField
					WHERE ModuleID='%s' AND
							Hidden='0'",
					mysql_real_escape_string($editModuleID));
			$moduleFieldResult = Database::getInstance()->query($moduleFieldQuery);

			$moduleNameFieldQuery = sprintf(
					"SELECT ModuleFieldID
					FROM ModuleField
					WHERE ModuleID='%s' AND
							Name='ModuleName'",
					mysql_real_escape_string($moduleCreatorID));
			$moduleNameFieldResult = Database::getInstance()->query($moduleNameFieldQuery)->fetch_object()->ModuleFieldID;
			
			$this->nameField_ = FieldFactory::createModuleField($moduleNameFieldResult);
			$moduleQuery = sprintf(
					"SELECT Name
					FROM Module
					WHERE Hidden='0'");
			$moduleResult = Database::getInstance()->query($moduleQuery);
			while($moduleObj = $moduleResult->fetch_assoc()){
				$this->nameField_->optionsIs($moduleObj);
			}

			while($moduleFieldObj = $moduleFieldResult->fetch_object()){
				$moduleFieldID = $moduleFieldObj->ModuleFieldID;
				$newModuleCreatorForm = new ModuleCreatorForm($moduleCreatorID, $editModuleID, $this->moduleName_, $moduleFieldID);
				$this->pageContents_[] = $newModuleCreatorForm;
			}
			//$newModuleCreatorForm = new ModuleCreatorForm($moduleCreatorID, $editModuleID, $this->moduleName_);
			//$this->pageContents_[] = $newModuleCreatorForm;
		}

		public function toHTML(){
			$output = $this->pageHeader() . "\n";
			if($this->moduleName_) {
				$output .= "<label style='font-size:12pt;' for='moduleName'>Module Name: </label><input id='moduleName' type='Text' value='{$this->moduleName_}' />";
				if(Security::privilege(new GeneralPrivilege("DeleteModule")))
					$output .= "\n<input type='Submit' class='DeleteModuleButton' onclick=\"if(confirm('Delete Module?')){deleteModule('{$this->editModuleID_}'); clearAndReload();}\" value='Delete Module' />";

				$output .= "<br /><br /><div id=\"accordion\">";
				foreach($this->pageContents_ as $form){
					$output .= "\n";
					$output .= <<<EOD
	<h3><a href="#">{$form->formName()}</a></h3>
	<div id="form_{$form->formID()}_{$form->moduleID()}_{$form->moduleName()}_{$form->moduleFieldID()}">
		{$form->toHTML()}
	</div>
EOD;
				}
				$output .= "</div>";

				if(Security::privilege(new ModulePrivilege("CreateField", $this->editModuleID_)))
					$output .= "<br/><input type='submit' class='CreateFieldButton' onclick=\"newModuleField('{$this->moduleCreatorID_}', '{$this->editModuleID_}', '{$this->moduleName_}');\" value='New Field' />\n";

				if(Security::privilege(new ModulePrivilege("EditModuleProperties", $this->editModuleID_)))
					$output .= "<br/><br/><input type='submit' class='ModuleSaveAll' value='Save all Fields' onclick='moduleCreatorPageSubmit({$this->editModuleID_}); return false;' />\n";

				$output .= "\n<a href='#' onclick='clearAndReload();' style='font-size:12pt;'>Switch Module</a>";
				$output .= "<script type='text/javascript'>initForm();</script>";
			}
			else {
				$output .= "{$this->nameField_->toHTML(150, 20, true, "", -1)}";
				$output .= "&nbsp<input type='Submit' class='EditModuleButton' value='Edit Module' onclick='redirectToModule(\"{$this->curPageURL()}\", {$this->nameField_->moduleFieldID()}); return false;' />";
				
				if(Security::privilege(new GeneralPrivilege("CreateModule"))) {
					$output .= "\n<form onsubmit='newModule(\"{$this->curPageURL()}\", prompt(\"Enter new module name: \")); return false;'>";
					$output .= "</br><input class='CreateModuleButton' type='Submit' value='Create New Module' /></form>";
				}
			}
			$output .= "\n" . $this->pageFooter();

			return $output;
		}
	}
?>
