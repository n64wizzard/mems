<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Security/Audit.php");
	require_once("lib/Module/ModuleField.php");
	require_once("lib/Form/FormField.php");
	
	/// Creates a DatePicker within a form, and a DateRangePicker for list filters
	class DateField extends ModuleField{
		static public function type(){ return "Date"; }
		public function toHTML($width, $height, $mutable, $currentValue, $moduleInstanceID){
			$output = "";

			$width -= 16;	// Since the calendar button takes up pretty much 16 pixels
			if($mutable)
				$output .= <<<EOD
<input class='DatePicker ValidateField' name='{$this->name()}' id='field_{$this->moduleFieldID()}' type='text' value='$currentValue' style='width:{$width}px;' />
<script type="text/javascript">
	$("#field_" + {$this->moduleFieldID()} + "").datepicker({
		showOn: 'button',
		buttonImageOnly: true,
		buttonImage: 'resources/icons/calendar.gif',
		gotoCurrent: true,
		//changeYear: true,
		showAnim: 'drop'
		});
</script>
EOD;
			else
				$output .= StaticField::html($width, $height, $currentValue);

			return $output;
		}
		/// The DateField is different that many other fields most notably within
		///  this function.  Instead of using another DatePicker to use as a filter,
		///  we use a different type: the Date Range Picker.
		/// @param $filterValues Array of values
		public function filterHTML($filterValues, $idPrefix){
			$output = "";

			$date = date("m/d/Y");
			if(array_key_exists("date", $filterValues) !== false)
				$date = $filterValues["date"];
			else
				$date = date("m/d/Y - 12/31/2026");

			$output .= <<<EOD
<input class='DateRangePicker' name='{$idPrefix}date' id='field_{$this->moduleFieldID()}' type='text' value='$date'  />
<script type="text/javascript">
	$(".DatePicker").trigger("DatePicker");	// In case the new input we just changed was a DatePicker
	filterDateRangePickers();
</script>
EOD;

			return $output;
		}
		public function filterSQL($filterValues){
			$output = "";

			$iniArray = Utils::iniSettings();
			$date = date("m/d/Y");
			if(array_key_exists("date", $filterValues))
				$dates = explode(" - ", $filterValues["date"]);
			else	// TODO: Find a way to set the default value to be the current week
				$dates = array(date("m/d/Y"), "12/31/2026");

			// Parse date into start/end components
			$startDate = $dates[0];
			$endDate = isset($dates[1]) ? $dates[1] : $startDate;

			$dateFormat = "%m/%d/%Y";
			$output = sprintf(
				"(MFI.ModuleFieldID='%s' AND STR_TO_DATE(AES_DECRYPT(MFI.Value, '%s'), '%s') BETWEEN STR_TO_DATE('%s', '%s') AND STR_TO_DATE('%s', '%s'))",
				mysql_real_escape_string($this->moduleFieldID()),
				mysql_real_escape_string($iniArray["passCode"]),
				$dateFormat,
				mysql_real_escape_string($startDate),
				$dateFormat,
				mysql_real_escape_string($endDate),
				$dateFormat);

			return $output;
		}
    }
?>
