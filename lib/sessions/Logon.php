<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("lib/form/Form.php");

	/// Creates a specialized form used logon
	class LogonForm extends Form{
		public function toHTML($editMode=false){
			// Form's editmode is fine for the Logon page, no need to duplicate it here
			if($editMode)
				return parent::toHTML($editMode);

			$formID = $this->formID();
			$totalHeight = $this->totalFormHeight(10);

			$output = "";
			$output .= "<form onsubmit='submitLogin(); return false;' method='post' id='logonForm'>";
			$output .= "<div id='formContainer' style='width:100%;height:{$totalHeight}px;'>";
			
			foreach($this->formFields_ as $formField)
                $output .= $formField->toHTML() . "\n";

			$output .= <<<EOD
<div style='clear:both;'></div>
</div>
<div style='clear:both;'>
<div id="form_result_{$formID}" style="display:none"></div>
<input name="Submit" type="submit" value="Submit" class="FormSubmit" />
</div>
</form>
<br/><br/>
<a href='index.php?Page=NewReg' id="newRegButton">Sign-Up</a>
<a href='' id="forgotPWButton">Forgot Password</a>
<script type="text/javascript">
	initForm();
</script>
EOD;
			return $output;
		}
	}
?>
