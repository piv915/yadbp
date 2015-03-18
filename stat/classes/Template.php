<?php

class Template {
	protected $name;
	protected $template;
	protected $data;
	protected $content;
	private $buffer;
	private $nodes;
	
	public function __construct($name) {
		$file = FSPATH . '/views/' . str_replace('.', '/', $name) . '.html';
		if(is_file($file)) {
			$this->name = $name;
			$this->template = file_get_contents($file);
		} else 
			throw new Exception("Template [$name] not found");
		$this->data = array();
		$this->nodes = array();
	}
	
	public function addText($slot, $text) {
		$d =& $this->data;
		settype($text, 'string');
		
		if(!isset($d[$slot]))
			$d[$slot][] = $text;
		else {
			/* ставим указатель в конец массива и получаем последний ключ */
			end($d[$slot]); $key = key($d[$slot]);
			
			/* и ссылку на последний элемент */
			$last =& $d[$slot][$key];
			
			if(is_object($last)) 
				$d[$slot][] = $text;
			else 
				$d[$slot][$key] .= $text;
		}
	}
	
	public function addViewAsText($slot, $name) {
		$file = FSPATH . '/views/' . str_replace('.', '/', $name) . '.html';
		if(is_file($file)) {
			$this->addText($slot, file_get_contents($file));
		} else 
			throw new Exception("View [$name] not found");
	}
	
	public function addNode($slot, $name, $path, $class='Template') {
		$d =& $this->data;
		$node = new $class($path);
		$d[$slot][] = $node;
		$this->nodes[$name] = $node;
	}
	
	public function dump() {
		print "<pre>";
			var_dump($this);
		print "</pre>";
	}
	
	public function clearData() {
		$this->data = array();
	}
	
	protected function __getSlots() {
		$tpl =& $this->template;
		
		$fields = array();
		
		$opTag = '<field ';	$clTag = '/>';
		$opAttr = 'name="';	$clAttr = '"';
		$opTagL = 7; $clTagL = 2;
		
		$pos = 0;
		$st = 1;
		
		for(;;) {
			switch ($st) {
					case 1:
						$ctp = 0; $len = 0; $crT = ''; $atrName = '';
						$otp = mb_strpos($tpl, $opTag, $pos, 'ASCII');
						if($otp === false) {
							$st  = 100;
						} else {
							$pos = $otp + $opTagL;
							$st = 2;	
						}
					break;
					
					case 2:
						$ctp = mb_strpos($tpl, $clTag, $pos, 'ASCII');
						if($ctp === false) {
							$st = 100;	
						} else {
							$ctp += $clTagL;
							$pos = $ctp;
							$len = $ctp - $otp; 
							$st = 3;
						}
					break;
				
					case 3:
						$crT = mb_substr($tpl, $otp, $len, 'ASCII');
//						print $crT;
						$st = 4;
					break;
					
					case 4:
						$oap = mb_strpos($crT, $opAttr, 0, 'ASCII');
						$st = (false !== $oap) ? 5 : 1;
					break;
					
					case 5:
						$atrName = '';
						for ($i = $oap + 6; $i < $len; $i++) {
							$ch = $crT{$i};
							if ($ch == '"') {
								$i = $len;
							} else {
								$atrName .= $ch;
							}
						}
						if(strlen($atrName)) 
							$fields[] = array($atrName, $otp, $ctp); //$len);
						$st = 1;
					break;
					
					case 100:
					default:
						break 2;
				}	
		}
		// 1 - вне тега
		// 2 - открывающий найден, ищем закрывающий тэг
		// 3 - выделение тэга целиком
		// 4 - поиск аттрибута, ищем name="
		// 5 - читаем аттрибут
		// 100 - выход
		
		return $fields;
	}
	
	public function __toString() {
		
		/* получаем слоты с координатами из шаблона */
		$tSlots = $this->__getSlots();

		/* склеиваем данные в каждом слоте с рекурсивным вызовом
		  узлов шаблонов и помещаем в буфер */
		$b =& $this->buffer;
		$b = "";

		foreach ($this->data as $slot => $data) {
			$b[$slot] = "";
			foreach ($data as $node) 
				$b[$slot] .= $node;
		}

		$c =& $this->content;
		$c = '';
	
		/* заменяем поля на значения */
		$pos = 0;
		foreach ($tSlots as $entry) {
			$slot = $entry[0];
			$c .= mb_substr($this->template, $pos, $entry[1] - $pos, 'ASCII');
			unset($repl);
			if(isset($b[$slot]))
				$repl =& $b[$slot];
			else 
				$repl = '';
			$c .= $repl;
			$pos = $entry[2];
		}

		$c .= mb_substr($this->template, $pos, (mb_strlen($this->template, 'ASCII') - $pos), 'ASCII');
		
		return $this->content;
	}
	
	public function display() {
		print $this;	
	}
	
	public function node($nodePath) {
		$parts = explode('.', $nodePath, 2);
		$name = $parts[0];
		if(!isset($this->nodes[$name]))
			throw new Exception("There is no node named as [$name]");
		else 
			return (isset($parts[1]))
					? $this->nodes[$name]->node($parts[1])
					: $this->nodes[$name];
			
	}
	
	public function __get($name) {
		if(isset($this->nodes[$name]))
			return $this->nodes[$name];
		else 
			throw new Exception("There is no node named as [$name]");
	}
}

?>