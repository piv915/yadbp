<?php

if(!defined('VIEWS_PATH'))
	define('VIEWS_PATH', $_SERVER['DOCUMENT_ROOT'] . '/views');

class Template {
	protected $name;
	protected $template;
	protected $data;
	protected $content;
	private $buffer;
	private $nodes;
	private $lang;
	protected $slots;
	private $apc_store = false;
	private $apc_time = 900;
	
	public function __construct($name, $lang=null, $ext='html', $use_apc=null) {
		global 
			$config;
		$__config  = $config;
		if(is_null($lang)) $lang = $config['lang'];
		$glob_apc = ($__config && isset($__config['cache_tpl_apc']))
						? (bool)$__config['cache_tpl_apc']
						: false;
						
		$this->apc_store = (is_null($use_apc))
						? $glob_apc
						: ($glob_apc && (bool)$use_apc);
			
		$file = VIEWS_PATH . '/' . $lang . '/' . str_replace('.', '/', $name) . '.' . $ext;

		if ($this->apc_store && $this->template = apc_fetch('template:' . $file)) {}
		else 
		{
				if(is_file($file)) {
					$this->template = file_get_contents($file);
					if ($this->apc_store)
						apc_store('template:' . $file, $this->template, $this->apc_time);
				} else 
					throw new Exception("Template [$name] not found");
		}
		
		if ($this->apc_store && $this->slots = apc_fetch('slots:' . $file)) {}
		else
		{
		    $this->slots = $this->__getSlots();
		    if ($this->apc_store)
		    	apc_store('slots:' . $file, $this->slots, $this->apc_time);
		}

		$this->name = $name;
		$this->data = array();
		$this->nodes = array();
		$this->lang = $lang;
	}
	
	public function addText($slot, $text) {
		$d =& $this->data;
		settype($text, 'string');
		
		if(!isset($d[$slot]))
			$d[$slot][] = $text;
		else {
			/* СЃС‚Р°РІРёРј СѓРєР°Р·Р°С‚РµР»СЊ РІ РєРѕРЅРµС† РјР°СЃСЃРёРІР° Рё РїРѕР»СѓС‡Р°РµРј РїРѕСЃР»РµРґРЅРёР№ РєР»СЋС‡ */
			end($d[$slot]); $key = key($d[$slot]);
			
			/* Рё СЃСЃС‹Р»РєСѓ РЅР° РїРѕСЃР»РµРґРЅРёР№ СЌР»РµРјРµРЅС‚ */
			$last =& $d[$slot][$key];
			
			if(is_object($last)) 
				$d[$slot][] = $text;
			else 
				$d[$slot][$key] .= $text;
		}
	}
	
	public function addViewAsText($slot, $name, $ext='html') {
		$file = VIEWS_PATH . '/' . $this->lang . '/' . str_replace('.', '/', $name);
		$file .= '.' . $ext;
		
		if(is_file($file)) {
			$this->addText($slot, file_get_contents($file));
		} else 
			throw new Exception("View [$name] not found");
	}
	
	public function addNode($slot, $name, $path, $ext='html', $class='Template') {
		$d =& $this->data;
		$node = new $class($path, $this->lang, $ext);
		$d[$slot][] = $node;
		$this->nodes[$name] = $node;
		return $node;
	}
	
	public function setNode($slot, $name, $node) {
		$d =& $this->data;
		if(!$node instanceof Template)
			throw new Exception("Node $slot not a Template;");
		$d[$slot][] = $node;
		$this->nodes[$name] = $node;
		return $node;
	}
	
	public function addTextToAllNodes($slot, $text) {
		
		foreach ($this->nodes as $node) {
			$node->addTextToAllNodes($slot, $text);
		}
		$this->addText($slot, $text);
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
		
		$opTag = '{$ ';	$clTag = '}';
		$opAttr = 'name=[';	$clAttr = ']';
		$opTagL = 3; $clTagL = 1; $opaL = 6;
		
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
						for ($i = $oap + $opaL; $i < $len; $i++) {
							$ch = $crT{$i};
							if ($ch == $clAttr) {
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
		// 1 - РІРЅРµ С‚РµРіР°
		// 2 - РѕС‚РєСЂС‹РІР°СЋС‰РёР№ РЅР°Р№РґРµРЅ, РёС‰РµРј Р·Р°РєСЂС‹РІР°СЋС‰РёР№ С‚СЌРі
		// 3 - РІС‹РґРµР»РµРЅРёРµ С‚СЌРіР° С†РµР»РёРєРѕРј
		// 4 - РїРѕРёСЃРє Р°С‚С‚СЂРёР±СѓС‚Р°, РёС‰РµРј name="
		// 5 - С‡РёС‚Р°РµРј Р°С‚С‚СЂРёР±СѓС‚
		// 100 - РІС‹С…РѕРґ
		
		return $fields;
	}
	
	public function __toString() {
		
		/* РїРѕР»СѓС‡Р°РµРј СЃР»РѕС‚С‹ СЃ РєРѕРѕСЂРґРёРЅР°С‚Р°РјРё РёР· С€Р°Р±Р»РѕРЅР° */
//		$tSlots = $this->__getSlots();
		$tSlots = $this->slots;

		/* СЃРєР»РµРёРІР°РµРј РґР°РЅРЅС‹Рµ РІ РєР°Р¶РґРѕРј СЃР»РѕС‚Рµ СЃ СЂРµРєСѓСЂСЃРёРІРЅС‹Рј РІС‹Р·РѕРІРѕРј
		  СѓР·Р»РѕРІ С€Р°Р±Р»РѕРЅРѕРІ Рё РїРѕРјРµС‰Р°РµРј РІ Р±СѓС„РµСЂ */
		$b =& $this->buffer;
		$b = "";

		foreach ($this->data as $slot => $data) {
			$b[$slot] = "";
			foreach ($data as $node) 
				$b[$slot] .= $node;
		}

		$c =& $this->content;
		$c = '';
	
		/* Р·Р°РјРµРЅСЏРµРј РїРѕР»СЏ РЅР° Р·РЅР°С‡РµРЅРёСЏ */
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
		if(defined('DEBUG') && DEBUG) 
		{
			if(stripos($this->content, '<!DOCTYPE') === 0)
			{
				$this->content = preg_replace(
					'#<body(.*?)>#i', 
					'<body$1>'.'<div class="tplwrap" id="tpl_'.$this->name.'">', 
					$this->content);
				$this->content = preg_replace('#</body>#i', '</div></body>', $this->content);
				
				return $this->content;
			}
			else 
				return '<div class="tplwrap" id="tpl_'.$this->name.'">' . $this->content . "</div>";
		}
		else 
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