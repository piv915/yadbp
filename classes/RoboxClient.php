<?php


class RoboxClient {
	
	private $db;
	
	public function __construct() {
	
		$this->db = DBConnection::get();
	
	}

	public function issueInvoice($account_number, $out_sum, $currencyMark) {
	
		settype($account_number, 'integer');
		$sum = sprintf("%.2f", $out_sum);
		$mark = (string)$currencyMark;
		
		$db =& $this->db;
		$db->query(sprintf('select acc_id from wallet_accounts where acc_id  = %d', 
				$account_number));

		if(false === ($row = $db->fetch(ROW_ASSOC)))
			throw new OutOfRangeException("Invalid account number: $account_number");

		$invID = $this->invoiceID();
		$now = time();
		try {
			$db->query(sprintf("insert into ps_rbx_invoices values(%d,%d,%d,0,0,0,%.2f,'%s')",
				$invID, $account_number, $now, $sum, $db->escape($mark)
			));
		} catch (Exception $e) {
			if($e->getCode() == MYSQL_FOREIGN_KEY_ERROR)
				throw new Exception("Invalid currency: [$mark]");
			else 
				throw $e;
		}
		
		return $invID;
	}
	
	public function invoiceCRC($invID) {
		$db =& $this->db;
		$db->query(sprintf("select inv_id, acc_id, out_sum from ps_rbx_invoices where inv_id = %d", $invID));
		if(!$row = $db->fetch(ROW_ASSOC))
			throw new Exception("RBX Invoice No [$invID] not found");
		
		$outSum = sprintf("%.2f", $row['out_sum']);
		$string = RBX_LOGIN . ":{$outSum}:{$invID}:" . RBX_PASS1 . ":shpacc_id={$row['acc_id']}";
		
		return strtoupper(md5($string));
	}
	
	private function invoiceID() {
		
		$db =& $this->db;
		$db->query("insert into _seq_ps_rbx_invoiceID () values ()");
		$db->query("select last_insert_id()");
		$row = $db->fetch(ROW_NUM);
		$id = (int)$row[0];
		$db->query(sprintf("delete from _seq_ps_rbx_invoiceID where id < %d", $id));
		return ($id ^ 0xAAAA);
	
	}
	
	public function getRates($outSum=null, $currencyMark=null) {

		$db =& $this->db;
		$db->query("set names utf8");
		$db->query("select mark, name from ps_rbx_currencies_list where accept = 1 order by mark");
		
		$weAccept = array();
		
		while ($row = $db->fetch(ROW_ASSOC)) {
			$weAccept[$row['mark']] = $row['name'];
		}
		
		$rbxAccept = $this->rbxXMLReq_Rates($outSum, $currencyMark);
		
		$merge = array_intersect_key($rbxAccept, $weAccept);
//		$merge = $rbxAccept;
		
		foreach ($merge as $mark => $rate) {
			$merge[$mark] = array('name' => $weAccept[$mark], 'rate' => $rate);
//			$merge[$mark] = array('name' => $mark, 'rate' => $rate);
		}
		
		return $merge;
	}
	
	private function rbxXMLReq_Rates($outSum=null, $currencyMark=null) {
		
		/* подготовка XML запроса */
		
		$xdoc = new DOMDocument('1.0', 'UTF-8');
		$root = $xdoc->createElement('robox.rate.req');
		$xdoc->appendChild($root);
		$elm = $xdoc->createElement('merchant_login', RBX_LOGIN);
		$root->appendChild($elm);
		
		if(!is_null($outSum)) {
			$elm = $xdoc->createElement('out_cnt', $outSum);
			$root->appendChild($elm);
		}
		
		if(!is_null($currencyMark)) {
			$elm = $xdoc->createElement('in_curr', $currencyMark);
			$root->appendChild($elm);
		}
		
		$XML_req = $xdoc->saveXML();
		
		/* отправка XML запроса */
		$curl = new Curl();
		$curl->post(RBX_CURRATES_URL, $XML_req);
		
		$XML_result = $curl->result();
		$XML_result = str_replace('encoding="windows-1251"',
		 'encoding="utf-8"' , iconv('windows-1251', 'utf-8', $XML_result));
		
		/* разбор XML ответа */
		$xdoc = new DOMDocument();
		$xdoc->preserveWhiteSpace = false;
		
		if(!$xdoc->loadXML($XML_result))
			throw new Exception("XML response can't be parsed");
		
		$opCodeTag = $xdoc->getElementsByTagName('retval')->item(0);
		
		if(is_null($opCodeTag))
			throw new Exception("RBX_Server not return opCode");
		
		$opCode = $opCodeTag->nodeValue;
		
		if($opCode != 0) 
			throw new Exception("RBX_Server return error code: [$opCode]");
		
		/* определяем получать курс или значение валюты для конкретной суммы */
		$fetchTagName = is_null($outSum) ? 'value' : 'ins_per_Xout';
		
		$rateList = $xdoc->getElementsByTagName('rate');
		
		$result = array();
		
		foreach ($rateList as $rateTag) {
			$rateRec = array();
			foreach ($rateTag->childNodes as $node) {
				if($node->nodeName == 'in_curr' || $node->nodeName == $fetchTagName)
					$rateRec[$node->nodeName] = $node->nodeValue;
			}
			$result[$rateRec['in_curr']] = sprintf("%.2f", $rateRec[$fetchTagName]);
		}
		
		return $result;
	}
	
	public function check_OpState($invID) {
		
		$db = DBConnection::get();
		$now = SYSTIME;
		$state = $this->rbxXMLReq_OpState($invID);

		if($state instanceof DataObject) {
			$op_statetm  = $state->date;
			$state = $state->state;
		} else {
			$op_statetm = 0;
		}
		
		$db->query(
			sprintf("update ps_rbx_invoices set last_check_tm = %d, op_state = %d, op_state_tm = %d where inv_id = %d",
				$now, $state, $op_statetm, $invID
			)
		);
		
		return $state;
	}
	
	public function rbxXMLReq_OpState($invID) {

		settype($invID, 'integer');
		
		/* подготовка XML запроса */
		
		$xdoc = new DOMDocument('1.0', 'UTF-8');
		$root = $xdoc->createElement('robox.opstate.req');
		$xdoc->appendChild($root);
		
		$elm = $xdoc->createElement('merchant_login', RBX_LOGIN);
		$root->appendChild($elm);
		
		$elm = $xdoc->createElement('merchant_invid', $invID);
		$root->appendChild($elm);
		
		$rqCRC = strtoupper(md5(RBX_LOGIN . ':' . $invID . ':' . RBX_PASS2));
		
		$elm = $xdoc->createElement('crc', $rqCRC);
		$root->appendChild($elm);
		
		$XML_req = $xdoc->saveXML();
		
		/* отправка XML запроса */
		$curl = new Curl();
		$curl->verifyPeer(true, RBX_CACERT);
		$curl->post(RBX_OPSTATE_URL, $XML_req);
		
		$XML_result = $curl->result();
		$XML_result = str_replace('encoding="windows-1251"',
		 'encoding="utf-8"' , iconv('windows-1251', 'utf-8', $XML_result));
		 		
		/* разбор XML ответа */
		$xdoc = new DOMDocument();
		$xdoc->preserveWhiteSpace = false;
		
		if(!$xdoc->loadXML($XML_result))
			throw new Exception("XML response can't be parsed");
		
		$opCodeTag = $xdoc->getElementsByTagName('retval')->item(0);
		
		if(is_null($opCodeTag))
			throw new Exception("RBX_Server not return opCode");
		
		$opCode = $opCodeTag->nodeValue;
		
		if($opCode != 0 && $opCode != 10) /* retval = 10 - операция не найдена */
			throw new Exception("RBX_Server return error code: [$opCode]");

		if($opCode) return RBX_OPSTATE_NOTFOUND;
		
		$opStateTag  = $xdoc->getElementsByTagName('opstate')->item(0);
		if(is_null($opStateTag))
			throw new Exception("RBX_Server not return opState");
			
		$stateTag = $opStateTag->getElementsByTagName('state')->item(0);
		if(is_null($stateTag))
			throw new Exception("RBX_Server not return opState");
			
		$state = $stateTag->nodeValue;
		
		if($state != RBX_OPSTATE_COMPLETE) return $state;
		
		$sumTag = $opStateTag->getElementsByTagName('out_cnt')->item(0);
		if(is_null($sumTag))
			throw new Exception("RBX_Server not return amount of curr. (out_cnt)");
		$sum = $sumTag->nodeValue;
		
		$dateTag = $xdoc->getElementsByTagName('date')->item(0);
		if(is_null($dateTag))
			$date = 0;
		else {
			$tmp = split('[ :-]', $dateTag->nodeValue);
			$date = mktime($tmp[3], $tmp[4], $tmp[5], $tmp[1], $tmp[2], $tmp[0]);
		}
		
		$do = new DataObject();
		$do->state = $state;
		$do->sum = $sum;
		$do->date = $date;

		return $do;
	}
	
}

?>