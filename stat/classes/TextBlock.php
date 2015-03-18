<?php
	class TextBlock extends Block 
	{
		public function __construct($p_lang,$p_layout) {
			
			$template_name = str_replace('../', '', ($p_lang . '/' . $p_layout . '.php'));
			
			if(CACHE_TEMPLATES_IN_APC) {
				
				if(false === ($this->content = apc_fetch($template_name))) {
				
					$file_name = VIEWS_DIR . $template_name;
					if(is_file($file_name) && is_readable($file_name)) {
						$this->content = file_get_contents($file_name);
						apc_store($template_name, $this->content, CACHE_TEMPLATES_APC_TIME);
					}
					else 
						throw new FileNotFoundException("Template [$file_name] not exists or not readable.");
				} /*else 
					error_log("Loaded from APC: $template_name");*/
					
			} else {
				
				$file_name = VIEWS_DIR . $template_name;
				if(is_file($file_name) && is_readable($file_name)) 
					$this->content = file_get_contents($file_name);
				else 
					throw new FileNotFoundException("Template [$file_name] not exists or not readable.");
			
			}
//			$fn = VIEWS_DIR . $p_lang . '/' . $p_layout . '.php';
//			if(is_file($fn) && is_readable($fn)) 
//				//$this->content = file_get_contents($fn);
//			if(defined('SHOWTPL'))
//			{	$this->content = 
//				'<div style="width: 100%; border: solid 1px red">
//				<div style="width: 100%; text-align: left; font-size: 9px; color:red">' 
//					. '/' . $p_lang . '/' . $p_layout . '.php'
//					. '</div> ' . file_get_contents($fn) . '</div>'; }
//			else $this->content = file_get_contents($fn);
//			else 
//				throw new Exception('Template file ' . $fn . ' not exists or not readable');
		}
		
		public function make($transform=0,&$p_more=null) {
			if($transform)
				$this->transform($p_more);
		
			$this->ready = true;
		}
		
		public function make2(&$p_data,$dyn=false) {
			
			$this->transform($p_data,$dyn);
			$this->ready = true;
		
		}

		private function transform(&$p_data,$dyn=false) {
			if(!is_array($p_data)) $p_data = array();
			$cont = &$this->content;
			// slots search in $cont
			$ld = '{#'; // left delimiter
			$rd = '#}'; // right delimiter
			
			$slots = array();
			$nd = &$ld; $ofs = 0; $in = 0; $sp = 0;
			while(false !== ($f = strpos($cont,$nd,$ofs))) {
				$ofs = $f+2;
				if($in) {
					$nd = &$ld;
					$slots[(substr($cont,$sp+2, $f - $sp - 2))] = '';
				} else {
					$nd = &$rd;
					$sp = $f;
				}
				$in ^=  1;
			}
			
			// replace
						
			if(!$dyn) {
				// what fill
				$fill = array_intersect_key($p_data,$slots);
				$slots = array_merge($slots,$fill);		

				foreach ($slots as $key => $value)
					$cont = str_replace($ld . $key . $rd, $value,$cont);
			} else {
				$r_cont = '';
				foreach ($p_data as $n => $a) {
					if(!is_array($a)) $a = array();
					
					$add = $cont;
					$fill = array_intersect_key($a,$slots);
					$r_slots = array_merge($slots,$fill);

					foreach ($r_slots as $key => $value) {
						$add = str_replace($ld . $key . $rd, $value, $add);
					}
					$r_cont .= $add;
				}
				
				$this->content = $r_cont;
			}
		} // end transform
	}
?>