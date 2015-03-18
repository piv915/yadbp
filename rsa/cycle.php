<pre>
<?php
/**
 * Sat Feb 20 04:38:27 MSK 2010
 * Author: Vasiliy.Ivanovich@gmail.com
 *
 * $Id$
 *
 */
ini_set('display_errors', 'On');
error_reporting(E_ALL|E_STRICT);
ini_set('include_path', '.:' . $_SERVER['DOCUMENT_ROOT'] . '/include/');
require_once('config.php');
define('INFO_LOG', 'rsa.log');
$req_id = abs(crc32(md5(microtime(1))));

info_log("req $req_id");

function rsa_new_keyfile() {
	global $req_id;

	info_log("$req_id >>> rsa: make new key from openssl (384 bit)");
	$rs = openssl_pkey_new(array('private_key_bits' => 384, 'private_key_type' => OPENSSL_KEYTYPE_RSA));
	if(!$rs) return false;

	if(!openssl_pkey_export($rs, $privkey)) return false;

	$pubkey=openssl_pkey_get_details($rs);
	if(!$pubkey) return false;

	$pubkey=isset($pubkey["key"]) ? $pubkey["key"] : false;
	if($pubkey === false) return false;

	return $privkey.$pubkey;
}

function rsa_get_keyfile() {
	global $req_id;

	try {
		$db = DBConnection::get();

		$db->query("select key_id, key_tm, keys_text from rsa_keys order by key_id desc limit 1");
		$row  = $db->fetch(ROW_ASSOC);

		info_log("$req_id >>> rsa: fetch last key for sign");

		$request_new = false;
		if(!$row) {
			info_log("$req_id >>> rsa: no keys in table");
			$request_new = true;
		}
		else {
			$key_tm = (int)$row['key_tm'];
			if(SYSTIME-$key_tm > RSA_KEY_LIFETIME) {
				// need new key
				$request_new = true;
				info_log("$req_id >>> rsa: fetched key too old, will make new");
			}
			else {
				$key_id = (int)$row['key_id'];
				info_log("$req_id >>> rsa: return saved key [id=$key_id]");
				return array('key_id' => $key_id, 'keys_text' => $row['keys_text']);
			}
		}

		if($request_new)
// NEW KEY FILE REQUEST
		{
			info_log("$req_id >>> rsa: requesting new key");
			if($mutex = DBMutex::get('new_rsa_key', DB_HOST, DB_USER, DB_PASSWORD, DB_NAME_MAIN)) {
				try {
					$new_key = rsa_new_keyfile();
					if($new_key === false)
						throw new Exception("openssl return false");

					$db->query(sprintf("insert into rsa_keys(key_id, key_tm, keys_text) values(NULL, %d, '%s')", SYSTIME, $db->escape($new_key)));
					info_log("$req_id >>> rsa: saved new key into db");
					$db->query("select last_insert_id()");
					$row = $db->fetch(ROW_NUM);
					$key_id = (int)$row[0];
					info_log("$req_id >>> rsa: new key_id = $key_id");

					$mutex = null;

				} catch (Exception $e) {
					$mutex = null;
					throw $e;
				}
				info_log("$req_id >>> rsa: return new key [id=$key_id]");
				return array('key_id' => $key_id, 'keys_text' => $new_key);
			}
			else
				throw new Exception("Can't get mutex new_rsa_key");
		}
	} catch (Exception $e) {
		info_log("$req_id >>> rsa: EXCEPTION {$e->getMessage()}");
		log_exception($e);
		return false;
	}

}

function rsa_sign_string($input) {
	global $req_id;

	try {
		if(!is_string($input))
			throw new Exception("rsa_sign_string(): \$input not a string, actually ". gettype($input));

		$rsa_keyfile = rsa_get_keyfile();
		$key_id = $rsa_keyfile['key_id'];
		$keyfile = $rsa_keyfile['keys_text'];

		$pk = openssl_pkey_get_private($keyfile);
		if($pk===false) {
			throw new Exception("openssl_pkey_get_private return false");
		}

		$now = time();
		$text = $input . ';'.$key_id.';'.$now;
		$rs = openssl_sign($text, $sign, $pk, OPENSSL_ALGO_SHA1);
		if($rs === false) {
			throw new Exception("openssl_sign return false");
		}
		openssl_free_key($pk);

		info_log("$req_id >>> rsa: input signed with key [id=$key_id] at [$now] // ".date("Y.m.dH:i:s",$now));
//		return array($sign, $key_id, $now);
		$sign = base64_encode($sign);
		return ';'.$key_id.';'.$now.';'.$sign;

	} catch (Exception $e) {
		info_log("$req_id >>> rsa: EXCEPTION {$e->getMessage()}");
		log_exception($e);
		return false;
	}
}

function rsa_verify_data($text, $signature) {
	global $req_id;

	try {

		list($_null,$key_id,$time,$sign) = explode(';',$signature, 4);

		$now = time();

//		$sign_corrupted = false;

		$key_id = (int)$key_id;
		$time = (int)$time;
		$sign = @base64_decode($sign,true);

		if($key_id <=0 || $time <= 0 || $time > $now || $sign===false) {
			throw new Exception("signature corrupted sign=[$signature]");
		}

		$db = DBConnection::get();

		$db->query(sprintf("select key_tm, keys_text from rsa_keys where key_id = %d", $key_id));
		$row = $db->fetch(ROW_ASSOC);
		if(!$row)
			throw new Exception("key id=[$key_id] not found.");

		$pbk = openssl_get_publickey($row['keys_text']);
		if($pbk === false)
			throw new Exception("openssl_get_publickey return false");

		$text .= ';'.$key_id.';'.$time;
		$rs = openssl_verify($text, $sign, $pbk);
		if($rs === -1)
			throw new Exception("openssl_verify error");
		if($rs === 1) {
			return array(($now-$time), ($now-(int)$row['key_tm']), $time, (int)$row['key_tm']);
		}
		else {
			return 0;
		}
		openssl_free_key($pbk);
	} catch (Exception $e) {
		info_log("$req_id >>> rsa: EXCEPTION {$e->getMessage()}");
		log_exception($e);
		return -1;
	}
}

$result = rsa_sign_string("voseg");
var_dump($result);

$check = rsa_verify_data("voseg",$result);
var_dump($check);

/*

$data = array(
	'forum_id' => 5148,
	'user_id'  => 10427
);
$text = base64_encode(serialize($data));

print "text = $text\n";

$priv_key = openssl_get_privatekey($rsa_keyfile);
openssl_sign($text, $sign, $priv_key, OPENSSL_ALGO_SHA1);
$sign = base64_encode($sign);

print "sign = $sign \n";

$http_text = base64_encode($text . ";2301;" . $sign);

print "request = $http_text\n";
*/

?>
</pre>
