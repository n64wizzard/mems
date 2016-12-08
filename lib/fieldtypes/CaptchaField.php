<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Security/Audit.php");
	require_once("lib/Module/ModuleField.php");
	require_once("lib/Form/FormField.php");
	require_once("lib/sessions/recaptchalib.php");
	
	/// Completely Automated Public Turing test to tell Computers and Humans Apart
	///	 Does not save any important data to the DB
    class CaptchaField extends ModuleField{
		static public function type(){ return "Captcha"; }
		public function toHTML($width, $height, $mutable, $currentValue, $moduleInstanceID){
			$publickey = "6LdZg8ISAAAAADctOcZua1mVJLPjT38vIl8HChsx";
			$output = "";
			
			// Create a hidden input with id="field_captcha"
			// Upon submit, concat and copy text in recaptcha_response_field and recaptcha_challenge_field to it
			$output .= recaptcha_get_html($publickey, NULL, true);

			$output .= <<<EOD
<input name='CAPTCHA' id='field_{$this->moduleFieldID()}' type='hidden' value='' />
<script type="text/javascript">
	$('#recaptcha_response_field').change(function() {
		$("#field_{$this->moduleFieldID()}").val($("#recaptcha_response_field").val() + "##" + $("#recaptcha_challenge_field").val());
	});
	// Since each CAPTCHA can only be submitted once,
	//	in case something was wrong with the form, we want to have a new one ready
	$('form').submit(function() {
		Recaptcha.reload();
	});
</script>
EOD;
			return $output;
		}
		public function validate($value){
			$privatekey = "6LdZg8ISAAAAAJd5ZascEq4bjp1v8k7xsKTDkfcd";
			$values = explode("##", $value);
			if(count($values) != 2)
				return "Captcha parsing error";
			$resp = recaptcha_check_answer($privatekey,
				$_SERVER["REMOTE_ADDR"],
				$values[1],
				$values[0]);
			if(!$resp->is_valid)
				return $resp->error;
			else
				return "";
		}
	}
?>
