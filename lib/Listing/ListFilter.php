<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");

	class ListFilter{
		private $moduleField_, $values_, $filterCount_;

		/// Look-up and create a ListFilter from the DB
		static public function createListFilter($listFilterID, $filterCount){
			$listFilterQuery = sprintf(
				"SELECT *
				FROM ListFilter
				WHERE ListFilterID='%s'",
				mysql_real_escape_string($listFilterID));
			$listFilterObj = Database::getInstance()->query($listFilterQuery, 1, 1)->fetch_object();
			return new ListFilter($listFilterObj->ModuleFieldID,
						$filterCount,
						Utils::explodeWithKeys(";", ":", $listFilterObj->Value));
		}
		public function __construct($moduleFieldID, $filterCount, $values){
			$this->values_ = $values;
			$this->moduleField_ = FieldFactory::createModuleField($moduleFieldID);
			$this->filterCount_ = $filterCount;
		}
		public function moduleFieldID(){ return $this->moduleField_->moduleFieldID(); }

		/// @return SQL that if used correctly, enforces the attributes of this filter
		public function filterSQL(){
			return $this->moduleField_->filterSQL($this->values_);
		}
		public function toHTML($moduleFields){
			$output = "";
			$output .= "<select name='filter_{$this->filterCount_}_field'>\n";

			// Create the list of list fields to choose from
			foreach($moduleFields as $moduleField)
				if(!$moduleField->hidden()){
					$selected = $this->moduleFieldID() == $moduleField->moduleFieldID() ? "SELECTED" : "";
					$output .= "<option value='{$moduleField->moduleFieldID()}' onclick='changeFilterField({$this->filterCount()}, {$moduleField->moduleFieldID()})' $selected>{$moduleField->label()}</option>\n";
				}

			$output .= "</select>\n";
			$output .= $this->moduleField_->filterHTML($this->values_, "filter_{$this->filterCount_}_");
			return $output;
		}
		public function filterCount(){ return $this->filterCount_; }
		public function valuesIs($values){ $this->values_ = $values; }
	}
?>
