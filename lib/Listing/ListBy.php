<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("lib/Module/FieldFactory.php");

	/// ListBy stores all the information needed to sort the Listing entries,
	/// and/or group then by some common value
	class ListBy{
		private $rank_, $moduleField_, $direction_, $orientation_, $type_;

		/// Loads a ListBy object from the DB
		static public function createListBy($listByID) {
			$listByQuery = sprintf(
				"SELECT *
				FROM ListBy
				WHERE ListByID='%s'",
				mysql_real_escape_string($listByID));
			$listByObj = Database::getInstance()->query($listByQuery, 1, 1)->fetch_object();

			return new ListBy($listByObj->Rank, $listByObj->ModuleFieldID, $listByObj->Direction, $listByObj->Orientation, $listByObj->Type);
		}
		public function __construct($rank, $moduleFieldID, $direction, $orientation, $type) {
			$this->rank_ = $rank;
			$this->moduleField_ = FieldFactory::createModuleField($moduleFieldID);
			$this->direction_ = $direction;
			$this->orientation_ = $orientation;
			$this->type_ = $type;
		}
		public function rank(){ return $this->rank_; }
		public function moduleFieldID(){ return $this->moduleField_->moduleFieldID(); }
		public function moduleFieldIDIs($moduleFieldID){
			$this->moduleField_ = FieldFactory::createModuleField($moduleFieldID);
		}
		public function moduleFieldLabel(){ return $this->moduleField_->label(); }
		public function direction(){ return $this->direction_; }
		public function directionIs($direction){ $this->direction_ = $direction; }
		public function orientation(){ return $this->orientation_; }
		public function orientationIs($orientation){ $this->orientation_ = $orientation; }
		public function type(){ return $this->type_; }
		public function typeIs($type){ $this->type_ = $type; }

		/// @return The portion of a mySQL query needed to enforce this filter
		public function toSQL(){
			$iniArray = Utils::iniSettings();
			$output = sprintf(
				" IF(MFI.ModuleFieldID=%s, AES_DECRYPT(MFI.Value, '%s'), 0) %s",
				mysql_real_escape_string($this->moduleFieldID()),
				mysql_real_escape_string($iniArray["passCode"]),
				($this->direction() ? " ASC" : " DESC"));
			return $output;
		}

		/// @return A string with the HTML code used to show this filter on any
		///  list options pane
		public function toHTML($moduleFields){
			$rank = $this->rank();
			$d0 = !$this->direction() ? "checked='checked'" : "";
			$d1 = $this->direction() ? "checked='checked'" : "";
			$o0 = !$this->orientation() ? "checked='checked'" : "";
			$o1 = $this->orientation() ? "checked='checked'" : "";
			$g = $this->type() ? "checked='checked'" : "";

			$output = "<select name='by_{$rank}_field'>\n";
			foreach($moduleFields as $moduleField){
				if(!$moduleField->hidden()){
					$selected = $this->moduleFieldID() == $moduleField->moduleFieldID() ? "SELECTED" : "";
					$output .= "<option value='{$moduleField->moduleFieldID()}' $selected>{$moduleField->label()}</option>\n";
				}
			}
			$output .= "</select>\n";
			$output .= <<<EOD
<input type='radio' name='by_{$rank}_dir' value='1' $d1>Asc</input>
<input type='radio' name='by_{$rank}_dir' value='0' $d0>Desc</input>
<input type="checkbox" name="by_{$rank}_type" value="1" $g/>Group
<input type='radio' name='by_{$rank}_or' value='0' $o0>Horizontal</input>
<input type='radio' name='by_{$rank}_or' value='1' $o1>Vertical</input>
EOD;
			return $output;
		}
	}
?>
