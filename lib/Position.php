<?php
	/// Position is just a simple class to store position information
	final class Position{
		private $left_, $top_, $width_, $height_;
		function __construct($left, $top, $width, $height){
			$this->left_ = $left == "" ? 0 : $left;
			$this->top_ = $top == "" ? 0 : $top;
			$this->width_ = $width == "" ? 0 : $width;
			$this->height_ = $height == "" ? 0 : $height;
		}

		// TODO: Remove the following function, and instead just do this inside of whatever function calls this function
		public function toHTML($id, $innerHTML=NULL){
			$output = "<div class='FormElement' id='div_formfield_$id' style='left:{$this->left()}px;top:{$this->top()}px;'>";
			if(isset($innerHTML)){
				$output .= "\n" . $innerHTML . "\n</div>\n";
			}
			return $output;
		}
		public function top(){ return $this->top_; }
		public function left(){ return $this->left_; }
		public function width(){ return $this->width_; }
		public function height(){ return $this->height_; }
	}
?>
