<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("lib/Position.php");
	require_once("lib/Module/ModuleInstance.php");
	require_once("lib/Form/FormField.php");

	class Form{
		protected $moduleInstance_, $formID_, $formFields_, $pageName_, $moduleID_;

		public function __construct($formID, $moduleInstanceID=-1, $moduleID=NULL){
			$formQuery = sprintf(
				"SELECT *
				FROM Form
				WHERE FormID='%s'",
				mysql_real_escape_string($formID));
			$formObj = Database::getInstance()->query($formQuery, 1, 1)->fetch_object();
			$this->formID_ = $formID;
			$this->pageName_ = $formObj->PageName;

			if(!isset($moduleID)){
				$moduleQuery = sprintf(
						"SELECT ModuleID
						FROM Page
						WHERE PageName='%s'",
						mysql_real_escape_string($formObj->PageName));
				$moduleID = Database::getInstance()->query($moduleQuery, 1, 1)->fetch_object()->ModuleID;
			}
			$this->moduleID_ = $moduleID;
			$this->moduleInstance_ = ModuleInstance::createModuleInstance($moduleInstanceID, $moduleID);
			$this->formFields_ = array();

			$formFieldsQuery = sprintf(
				"SELECT FormFieldID,ModuleFieldID
				FROM FormField
				WHERE FormID='%s'
				ORDER BY ModuleFieldID",
				mysql_real_escape_string($formID));
			$formFieldsResult = Database::getInstance()->query($formFieldsQuery);

			while($formFieldObj = $formFieldsResult->fetch_object()){
				$formFieldID = $formFieldObj->FormFieldID;
				$moduleFieldID = $formFieldObj->ModuleFieldID;
				$newFormField = FormField::createFormField($formFieldID, $this->moduleInstance()->moduleFieldInstance($moduleFieldID));

				// Form Fields are outputted in order of their top position, followed by their left position
				//  This is important to support proper tabbing between fields, otherwise the order matters little
				$key = str_pad($newFormField->position()->top(), 5, "0", STR_PAD_LEFT) . ":" . str_pad($newFormField->position()->left(), 5, "0", STR_PAD_LEFT);
				$this->formFields_[$key] = $newFormField;
            }
			ksort($this->formFields_, SORT_STRING);
		}
        protected function formID(){ return $this->formID_; }
		protected function moduleInstance(){ return $this->moduleInstance_; }
        public function formFields(){ return $this->formFields_; }
		public function moduleID(){ return $this->moduleID_; }
		public function formFieldIs($formField){ $this->formFields_[] = $formField; }

		/// @param $padding Some fields may innacurately report their height (such as CAPTCHA).
		///  To resolve, we just add some wiggle-room (which visually looks fine too).
		/// @return The total height of all form fields, plus the padding
		protected function totalFormHeight($padding=50){
			$totalHeight = 0;
			foreach($this->formFields_ as $formField)
				$totalHeight = $formField->position()->height() + $formField->position()->top() > $totalHeight ?
								$formField->position()->height() + $formField->position()->top() : $totalHeight;
			$totalHeight += $padding;

			return $totalHeight;
		}

		/// @return A string containing the HTML code of this form and all of its fields
		/// @param $editMode If true, all fields are mutable, but there is submit ability
		public function toHTML($editMode=false){
			$formID = $this->formID();
			$miid = $this->moduleInstance()->moduleInstanceID();

			if($editMode)
				foreach($this->formFields_ as $formField)
					$formField->mutableIs(true);

			$totalHeight = $this->totalFormHeight();

			$output = "";

			if(!$editMode)
				$output .= "<form onsubmit=\"formSubmit('$formID', '$miid'); return false;\" method='post' id='form_$formID'>\n";
			$output .= "<div id='formContainer' style='width:100%;height:{$totalHeight}px;'>";

			// Including the following data makes it much easier in the JS code to send
			//  back the proper data in AJAX calls.  Also, it means we don't have to try and encode
			//  these values in other html tags (such as the 'id' attribute of the form).
			if(!$editMode)
				$output .= <<<EOD
<script type="text/javascript">
	jQuery.data(document.body, "moduleInstanceID", '$miid');
	jQuery.data(document.body, "moduleID", '{$this->moduleID()}');
	jQuery.data(document.body, "moduleName", '{$this->moduleInstance()->module()->name()}');
</script>
EOD;

			// We only want to show the "Submit" button if the user is actually able to edit anything
			$atLeastOneMutable = false;
			foreach($this->formFields_ as $key => $formField){
                $output .= $formField->toHTML() . "\n";
				$atLeastOneMutable |= ($formField->mutable()
					&& (Security::privilege(new ModuleFieldPrivilege("Write", $formField->moduleFieldInstance()->moduleFieldID()))
					 || Security::privilege(new ModuleFieldPrivilege("Create", $formField->moduleFieldInstance()->moduleFieldID()))
					));
			}

			$output .= <<<EOD
<div style='clear:both;'></div>
</div>
<div style='clear:both;'>
<div id="form_result_{$formID}" style="display:none"></div>
EOD;

			if(!$editMode){
				if($atLeastOneMutable)
					$output .= "<input name='Submit' type='submit' value='Submit' class='FormSubmit' />\n";
				if($miid != NULL && Security::privilege(new ModulePrivilege("TransferRole", $this->moduleID())))
					$output .= "<div id='instancePrivDialogButton'></div>\n";
				if($miid != NULL && Security::privilege(new ModulePrivilege("DeleteInstance", $this->moduleID())))
				$output .= "<div class='FormDelete'>Delete</div>\n";
				$output .= "<script type='text/javascript'>initForm();</script>\n";
			}
			$output .= "</div>\n</form>\n";
			return $output;
		}

		// PENDING: Eventually should also save general Form Properties here
		/// Saves all of this Form's form fields to the DB
		public function saveToDB(){
			foreach($this->formFields_ as $formField)
				$formField->saveToDB($this->formID(), $this->moduleID());
		}
	}
?>
