#!/usr/bin/php
<?php
include dirname(__FILE__)."/libs/ast/AsteriskManager.php";
include dirname(__FILE__)."/libs/ini/ini_handler.php";
include dirname(__FILE__)."/libs/json/phpJson.class.php";

class AsteriskComposer
{
	
	public function __construct($DEBUG=false)
	{
		$this->DEBUG = $DEBUG;
		$this->ast = new Net_AsteriskManager(array('server' => '127.0.0.1', 'port' => '5038'));
		$this->auth = $this->get_auth();
	}
	public function get_auth(){
		$data = parse_ini("/etc/asterisk/manager.conf");
		$keys = array_keys($data);
		$username = "";
		$password = "";//$data["admin"]["secret"];
		if (isset($data[$keys[1]])) {
			$username = $keys[1];
			$password = $data[$keys[1]]["secret"];
		}
		$auth = array("username" => $username, "password" => $password);
		if ($this->DEBUG) {
			echo "AUTH: ".json_encode($auth)."\n";
		}
		return $auth;
	}
	public function get_sip_peers(){
		try{
			$username = $this->auth["username"];
			$password = $this->auth["password"];
			$this->ast->connect();
			$this->ast->login($username, $password);
			$data = $this->ast->getSipPeers();
			$arr = explode("\n", $data);
			$length = count($arr);
			$mainarr = array();
			$namearr = "";
			$allow_checkname = 0;
			$myarr = array();
			$counter = 0;
			foreach ($arr as $key => $value) {
				if(strlen($value) > 1){
					//$do = '"'.$value.'"';//str_replace("\n", '", "', $value);
					$value = str_replace("\r", "", $value);
					$doarr = split(": ", $value);
					$myarr[(string)$doarr[0]] = (string)$doarr[1];
					//echo $doarr[0];
					if(($doarr[0] === "ObjectName") && ($allow_checkname === $counter)){
						$namearr = $doarr[1]."_".$counter;
						$allow_checkname = $allow_checkname + 1;
					} else {
						$namearr = "check ".$counter;
					}
				} else {
					if (!empty($myarr) && (count($myarr) > 3)) {
						$mainarr[] = $myarr;
					}
					$myarr = array();
					$counter = $counter + 1;
					$allow_checkname = true;
				}
			}
			if ($this->DEBUG) {
				echo "SIP PEERS: ".json_encode($this->mainarr)."\n";
			}
			return $mainarr;
		} catch (PEAR_Exception $e) {
			echo $e->getMessage();
		}
		return array();
	}
}
function check_status(){
	$ac = new AsteriskComposer(false);
	$sip_peers = $ac->get_sip_peers();
	$err_peers = array();
	foreach ($sip_peers as $key => $value) {
		if ($value["Status"] === "UNKNOWN" || $value["Status"] === "UNREACHABLE") {
			$err_peers[] = $value;
		}
	}
	print_r($err_peers);
	if(count($err_peers) > 0){
		exec("/etc/init.d/iptables stop");
		sleep(15);
		$sip_peers = $ac->get_sip_peers();
		$ip_arr = array();
		foreach ($err_peers as $i => $err_val) {
			foreach ($sip_peers as $j => $sip_val) {
				if(($err_val["ObjectName"] === $sip_val["ObjectName"]) && ($sip_val["Status"] !== "UNKNOWN" || $sip_val["Status"] !== "UNREACHABLE")){
					$curr_ip = $sip_val["IPaddress"];
					if(preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', $curr_ip)){
						$ip_arr[] = $curr_ip;
						//exec("iptables -I INPUT -s ".$curr_ip." -j ACCEPT");
						exec("iptables -A ELASTIX_INPUT -s ".$curr_ip." -j ACCEPT");
						print_r($curr_ip);
					}
				}
			}
		}
		exec("/etc/init.d/iptables start");
	}

}
check_status();
?>