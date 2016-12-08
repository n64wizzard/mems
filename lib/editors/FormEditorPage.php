<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Exception.php");
	require_once("lib/Database.php");
	require_once("lib/Security/Security.php");
	require_once("lib/page/Page.php");

	/// All general properties of a form
	// TODO: Find a way to use this class within Form (to consolidate)
	class FormInfo{
		private $formID_, $pageName_, $moduleID_, $description_, $removable_;

		public function __construct($formID, $pageName, $moduleID, $description, $removable){
			$this->formID_ = $formID;
			$this->pageName_ = $pageName;
			$this->moduleID_ = $moduleID;
			$this->description_ = $description;
			$this->removable_ = $removable;
		}
		public function formID(){ return $this->formID_; }
		public function pageName(){ return $this->pageName_; }
		public function moduleID(){ return $this->moduleID_; }
		public function description(){ return $this->description_; }
		public function removable(){ return $this->removable_; }
	}

	/// A number of static functions that create different elements of the pages related to the form editor
	class FormEditorPage{
		/// @return Array of Modulename => array of FormInfo objects
		private static function formList(){
			$formsQuery = sprintf(
				"SELECT Module.Name AS ModuleName, FormID, Module.ModuleID, Page.PageName, Page.Description, Page.Removable
				FROM Form JOIN Page 
					ON Form.PageName=Page.PageName
				JOIN Module
					ON Module.ModuleID=Page.ModuleID
				WHERE Module.Hidden=b'0'");
			$formsResult = Database::getInstance()->query($formsQuery);

			$formList = array();
			while($formObj = $formsResult->fetch_object()){
				if(!isset($formList[$formObj->ModuleName]))
					$formList[$formObj->ModuleName] = array();
				$formList[$formObj->ModuleName][] = new FormInfo($formObj->FormID,
														$formObj->PageName,
														$formObj->ModuleID,
														$formObj->Description,
														$formObj->Removable);
			}
			return $formList;
		}

		/// @return An HTML string that contains info and buttons for every form
		public static function formListPage(){
			$pageContents = "<div id='formList'>";

			$atLeastOneFormCreate = false;
			foreach(self::formList() as $moduleName => $forms){
				$pageContents .= "<div id='{$forms[0]->moduleID()}'><h4>$moduleName</h4>\n";
				foreach($forms as $formInfo){
					$atLeastOneFormCreate |= Security::privilege(new ModulePrivilege("CreateForm", $formInfo->moduleID()));

					$editSettingsLink = Security::privilege(new ModulePrivilege("EditForm", $formInfo->moduleID()))
							? "<a href='#' class='EditFormSettings' id='{$formInfo->formID()}'>Properties</a>"
							: "";
					$editLink = Security::privilege(new ModulePrivilege("EditForm", $formInfo->moduleID()))
							? "<a href='FormEditor.php?Page={$formInfo->pageName()}&formID={$formInfo->formID()}'>Edit Form</a>"
							: "";
					$deleteLink = Security::privilege(new ModulePrivilege("DeleteForm", $formInfo->moduleID())) && $formInfo->removable()
							? "<a id='delete_{$formInfo->formID()}' href='' class='DeleteFormLink'>Delete</a>"
							: "";

					$pageContents .= <<<EOD
<div class="FormListEntry">
<div class="FormName">{$formInfo->pageName()}</div>
<div class='FormLink'>$editSettingsLink</div>
<div class='FormLink'>$editLink</div>
<div class='FormLink'>$deleteLink</div>
<div class='FormDescription'>{$formInfo->description()}</div>
</div>
EOD;
				}
				$pageContents .= "</div>\n";
			}
			$pageContents .= "</div><br/>";
			if($atLeastOneFormCreate)
				$pageContents .= "<div id='newFormButton'>Create New Form</div>";
			$pageContents .= "<script type='text/javascript'>formEditorScript();</script>";
			$newPage = new Page("Forms", "Forms", $pageContents, 600);
			return $newPage;
		}

		/// @return An HTML string used to edit a form
		public static function formEditor($pageName, $formID){
			$newForm = new Form($formID);

			$existingFields = array();
			foreach($newForm->formFields() as $formField)
				$existingFields[] = $formField->moduleFieldInstance()->moduleFieldID();

			$moduleFieldOptions = "";
			$module = Module::createModule($newForm->moduleID());
			foreach($module->moduleFields() as $moduleField)
				if((!$moduleField->hidden() || $moduleField->unique())
						&& array_search($moduleField->moduleFieldID(), $existingFields) === false)
					$moduleFieldOptions .= "<option value='{$moduleField->moduleFieldID()}'>{$moduleField->name()}</option>\n";

			$pageContents = "";
			$pageContents .= <<<EOD
<div id="formFieldSettings">
<div class='FormEditorOption'>
	<label style="float:left;" for="moduleFieldName">Field Name: </label>
	<input type="text" id="moduleFieldName" style="display:none;float:left;" readonly />
	<select style="float:left;" id="moduleFieldID" />
	$moduleFieldOptions
	</select>
</div>
<div class='FormEditorOption'>
	<label for="width">Width: </label>
	<input type="text" id="width" size="5" />
</div>
<div class='FormEditorOption'>
	<label for="height">Height: </label>
	<input type="text" id="height" size="5" />
</div>
<div class='FormEditorOption'>
	<label for="mutable">Mutable: </label>
	<input type="checkbox" id="mutable" />
</div>
<div class='FormEditorOption'>
	<label for="includeLabel">Include Label: </label>
	<input type="checkbox" id="includeLabel" />
</div>
<button style="float:clear;" id='updateFormField'>Create New</button>
<button style="float:right;display:none;" id='deleteFormField'>Delete</button>
</div>
<br/><br/>
EOD;

			$pageContents .= $newForm->toHTML(true);
			$pageContents .= <<<EOD
<br/><br/>
<button id='saveForm'>Save Changes</button>
<script type="text/javascript">
	jQuery.data(document.body, "formID", '$formID');
	initFormEditor();
</script>
EOD;
			
			$page = new Page("Edit: " . $pageName, $pageName, $pageContents, 36000);
			return $page;
		}
	}
?>
