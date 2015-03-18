<?php

	abstract class Block 
	{
		protected $content = null;
		protected $ready = false;
		private $tmplfile = null;
		
		abstract public function __construct();
		public function make($transform=0,&$p_more=null){}
		
		public function get_content(){
			if($this->ready)
				return $this->content;
			return null;
		}
		
		public function display(){
			if ($this->ready) {
				if(is_array($this->content)) {
					echo join('<br/>',$this->content);
				} else {
					echo $this->content;
				}
				return true;
			}
			return false;
		}
	}

?>