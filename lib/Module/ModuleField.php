<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Security/Audit.php");
	require_once("lib/Database.php");
	require_once("lib/Module/FieldOption.php");

	/// ModuleField is subclassed in order to create non-abstract modulefields.
	///  The only two required functions the children must implement are type()
	///  and toHTML(), however, there are many other helper function they may choose
	///  to override.
    abstract class ModuleField{
		// All fields are described in InitDatabase
		protected $name_, $label_, $type_, $description_, $unique_, $moduleID_,
				$regex_, $defaultValue_, $options_, $moduleFieldID_, $hidden_;

		public function __construct($name, $label, $type, $description, $unique,
				$regex, $defaultValue, $options, $hidden, $moduleFieldID=NULL, $moduleID=NULL){
			$this->name_ = $name;
			$this->label_ = $label;
			$this->type_ = $type;
			$this->description_ = $description;
			$this->regex_ = $regex;
			$this->defaultValue_ = $defaultValue;
			$this->hidden_ = $hidden;
			$this->moduleID_ = $moduleID;
			$this->unique_ = $unique;
			$this->options_ = $options;
			$this->moduleFieldID_ = $moduleFieldID;
		}

		/// Loads all FieldOptions from the DB
		public static function initOptions($moduleFieldID){
			$moduleFieldOptionsQuery = sprintf(
				"SELECT ModuleFieldOptionID
				FROM ModuleFieldOption
				WHERE ModuleFieldID='%s'",
				mysql_real_escape_string($moduleFieldID));
			$moduleFieldOptionsResult = Database::getInstance()->query($moduleFieldOptionsQuery);
			$options = array();
			while($rowObj = $moduleFieldOptionsResult->fetch_object())
				$options[] = FieldOption::createFieldOption($rowObj->ModuleFieldOptionID);

			return $options;
		}

		/// Adds new Field Options to the module field
		/// @param $clear Whether to clear the existing options
		public function optionsIs($options, $clear=false) {
			if($clear)
				$this->options_ = array();
			foreach($options as $option)
				$this->options_[] = new FieldOption($this->moduleFieldID(), $option, $option);
		}

		/// Checks whether a value satisfies the requirements of this field type
		///		May be over-riden to check for special cases (to provide more error details)
		/// @return Empty string is valid, error text if invalid
		public function validate($value){
			return preg_match($this->regex(), $value) === 1 ? "" : "Invalid value";
		}
		
		/// @return The HTML code used to display this field as a filter
		public function filterHTML($filterValues, $idPrefix){
			//{$this->moduleFieldID()}
			$currValue = isset($filterValues["value"]) ? $filterValues["value"] : "";
			$output = <<<EOD
<input name="{$idPrefix}value" type="text" value="{$currValue}" />\n
EOD;
			return $output;
		}
		
		/// @return The subset of a WHERE clause to enforce a filter associated with this field
		public function filterSQL($filterValues){
			if($filterValues == "" || count($filterValues) == 0 || $filterValues["value"] == "")
				return "TRUE";

			$iniArray = Utils::iniSettings();
			// Note: we have to convert the Value to type CHAR, in order to have case-insensitive checking
			$output = sprintf(
				"(MFI.ModuleFieldID='%s' AND CONVERT(AES_DECRYPT(MFI.Value, '%s'), CHAR) REGEXP '%s')",
				mysql_real_escape_string($this->moduleFieldID()),
				mysql_real_escape_string($iniArray["passCode"]),
				mysql_real_escape_string($filterValues["value"]));

			return $output;
		}
		
		public function defaultValue(){ return $this->defaultValue_; }
		public function name(){ return $this->name_; }
		public function description(){ return $this->description_; }
		public function regex(){ return $this->regex_; }
		public function hidden(){ return $this->hidden_; }
		public function label(){ return $this->label_; }
		public function moduleID(){ return $this->moduleID_; }

		/// @return A string of HTML code containing the label for an input field
		public function labelHTML(){
			$output = "";
			$output .= "<label for='field_{$this->moduleFieldID()}'>{$this->label()}</label>";
			return $output;
		}
		public function moduleFieldID(){ return $this->moduleFieldID_; }
		public function unique(){ return $this->unique_; }

		/// @return String that will be matched to the 'Type' attribute of Module Fields in the DB
		/// Ideally would be abstract, but PHP does not allow abstract static functions.
		static public function type(){ throw new BadFunctionCallException("Cannot call type() in ModuleField."); }

		/// @return A string of HTML code to be used in a form
		abstract public function toHTML($width, $height, $mutable, $currentValue, $moduleInstanceID);

		/// Function used by Listings.  Should only be over-ridden by field types that cannot display their
		///  non-mutable html within one line
		/// @return HTML string that a listing can use to display this field's current value
		public function listingHTML($width, $currentValue, $moduleInstanceID){
			return $this->toHTML($width, "22", false, $currentValue, $moduleInstanceID);
		}

		/// Field types can perform any specific extra operations within this function.
		///		The default action is nothing.
		public function saveToDB($newValue, $moduleInstanceID){ return $newValue; }
		
		/// Searches options for the one with the given label, and then returns that object
		public function option($label){
			foreach($this->options_ as $option)
				if($option->optionLabel() == $label)
					return $option;
			return NULL;
		}

		/// @return HTML form fields that a user can use in the Module Editor to
		///  specify any field-specific options
		public function showOptions() {
			return "";
		}

		/// Complement to the showOptions() function, now we need to save the
		///  options to the DB.  As long as we name our fields correctly above,
		///  we should not need to override this function
		public function saveOptions($optionIDs, $optionValues) {
			for($i = 0; $i < count($optionIDs); $i++) {
				try {
					$optionsQuery = sprintf(
							"UPDATE ModuleFieldOption
							SET OptionValue='%s'
							WHERE ModuleFieldID='%s'
							AND OptionLabel='%s'",
							mysql_real_escape_string($optionValues[$i]),
							mysql_real_escape_string($this->moduleFieldID()),
							mysql_real_escape_string($optionIDs[$i]));
					$optionsResult = Database::getInstance()->query($optionsQuery, 2, 1);
				}
				catch(MySQLException $e) {
					$optionsQuery = sprintf(
							"INSERT INTO ModuleFieldOption
							(ModuleFieldID, OptionLabel, OptionValue)
							VALUES ('%s', '%s', '%s')",
							mysql_real_escape_string($this->moduleFieldID()),
							mysql_real_escape_string($optionIDs[$i]),
							mysql_real_escape_string($optionValues[$i]));
					$optionsResult = Database::getInstance()->query($optionsQuery);
				}
			}
		}
	}
?>
