<?php
/**ZERN™ Framework ~ an evolving, robust platform for rapid & efficient development of modem responsive applications and APIs;
 * Built by ODAO™ [www.osawere.com] using PHP, SQL, HTML, CSS, JS & derived technology.
 * © July 2019 | beta 1.0 | Apache License, Version 2.0
 * ===================================================================================================================
 * Dependency » ~
 * PHP | .zern::FW ~ the framework's main class
 **/

class ZERN {
	private $konfig;


	//========== INITIALIZE ==========//
	public function init($zone='o9JA')
	{
		/*** Disable App when MODE is undefined or set to off ***/
		if(!defined('oAPPMODE') || oAPPMODE == '' || oAPPMODE == 'OFF'){exit;}

		/*** Separators ***/
		defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR);
		defined('PS') ? null : define('PS', '/');

		mb_internal_encoding("UTF-8");
		ini_set('session.cache_limiter', 'public');
		session_cache_limiter(false);

		#TODO ~ validate timezone's input
		if($zone == 'o9JA'){$zone = 'Africa/Lagos';}
		date_default_timezone_set($zone);
	}
	//==========** END **==========//


	//========== IP VALIDATOR [returns true/false] ==========//
	public function isIP($host='')
	{
		if(empty($host)){return 'ZE428-IV1: [Argument Required]';}
		else {
			$parts = parse_url($host);
			if(!isset($parts['host'])){$o = (bool)ip2long($host);}
			else {$o = (bool)ip2long($parts['host']);}
			return $o;
		}
		return false;
	}
	//==========** END **==========//


	//========== BASEPATH [root directory & url] ==========//
	public function BP($task = 'oDIR', $path='oPREP')
	{
		if(empty($task)){exit('ZE428-BP1: [Argument Required]');}
		elseif(empty($path)){exit('ZE428-BP2: [Argument Required]');}
		elseif($task != 'oDIR' && $task != 'oHOST'){exit( 'ZE406-BP1: [Invalid Argument Input]');}
		else {
			$o = '';
			if($path == 'oPREP'){
				if($task == 'oHOST' && !empty($_SERVER["SERVER_NAME"])){
					$o = $_SERVER["SERVER_NAME"];
					if(!empty($this->konfig)){
						$konfig = $this->konfig;
						if(($this->isIP($o) || $o == 'localhost') && !empty($konfig['ifip'])){$o = $o.PS.$konfig['ifip'];}
					}
				}
				elseif($task == 'oDIR' && !empty($_SERVER["DOCUMENT_ROOT"])){
					$o = $_SERVER["DOCUMENT_ROOT"];
				}
			}
			else {
				if($task == 'oHOST'){
					#TODO ~ check that $path is valid host
					$o = $path;
				}
				elseif($task == 'oDIR'){
					$pathinfo = pathinfo($path);
					if(!empty($pathinfo['dirname'])){$o = $pathinfo['dirname'].DS;}
				}
			}
			return $o;
		}
	}
	//==========** END **==========//


	//========== SET BASEPATH ==========//
	public function setBP($task='', $path='')
	{
		$o = $this->BP($task, $path);
		if(!empty($o['oERROR'])){return $o;}
		elseif(!empty($o) && $o !== false){
			if($task == 'oHOST'){$this->oHost = $o;}
			elseif($task == 'oDIR'){$this->oDir = $o;}
			return true;
		}
		return false;
	}
	//==========** END **==========//


	//========== INITIALIZE APP & SET PROPERTIES ==========//
	public function initApp($konfig='oKONFIG')
	{
		/*** Maintain PHP Session ***/
		if(class_exists('oSession')){oSession::start();}

		/*** Set Konfig Variable ***/
		if(empty($konfig) || $konfig == 'oKONFIG'){
			global $oKonfig;
			if(!empty($oKonfig)){$konfig = $oKonfig;}
		}

		if(empty($konfig) || !is_array($konfig)){exit('Configuration is required');}
		$this->konfig = $konfig;

		/*** Continue Setting Variables ***/
		foreach($konfig as $label => $value){
			if(is_array($value) && $label != 'link_allowed'){
				foreach($value as $sub_label => $sub_value){
					$subLabel = $label . '_' . $sub_label;
					$this->$subLabel = $sub_value;
				}
			}
			else {
				$this->$label = $value;
			}
		}

		$this->setRoute();
		$this->setURL();
		if(isset($this->db_name)){$this->setDB();}
	}
	//==========** END **==========//


	//========== GET CONFIGURATION ==========//
	public function getKonfig($label='oKONFIG')
	{
		if(empty($label)){return 'ZE428-GK1: [Argument Required]';}
		else {
			$o = $this->konfig;
			if(empty($o)){return 'ZE404-GK1: [No Data]';}
			elseif(!is_array($o)){return 'ZE406-GK1: [Invalid Data]';}
			else {
				if($label == 'oKONFIG'){return $o;}
				elseif(in_array($label, $o)){return $o[$label];}
			}
		}
		return false;
	}
	//==========** END **==========//


	//========== SET ROUTE ==========//
	public function setRoute($eval=''){
		if(class_exists('oURL')){
			if(empty($this->route)){
				$this->oRoute = oURL::route($eval);
			}
			$uriData = oURL::uriData();
			if(!empty($uriData)){
				$this->oURI = $uriData['uri'];
				$this->oLink = $uriData['link'];
				$this->oAction = $uriData['action'];
				$this->oCase = $uriData['case'];
			}
		}
		return;
	}
	//==========** END **==========//



	//========== SET BASEURL ==========//
	public function setURL(){
		if(!empty($this->oHost)){$o = $this->oHost;}
		elseif(defined('zernURL')){$o = zernURL;}
		elseif(!empty($_SERVER["SERVER_NAME"])){$o = $_SERVER["SERVER_NAME"];}

		if(!empty($o)){
			if(oKit::hasSSL()){$o = 'https://'.$o;} else {$o = 'http://'.$o;}
			$this->oURL = $o;
			return true;
		}
		return false;
	}
	//==========** END **==========//


	//========== CONFIGURE DATABASE & MAKE CONNCTION ==========//
	public function setDB()
	{
		$db = array();
		if(!empty($this->db_name)){$db['name'] = $this->db_name; unset($this->db_name);} else {$db['name'] = 'zenq';}
		if(!empty($this->db_user)){$db['user'] = $this->db_user; unset($this->db_user);} else {$db['user'] = 'zenq';}
		if(!empty($this->db_pass)){$db['pass'] = $this->db_pass; unset($this->db_pass);} else {$db['pass'] = 'ZenQ';}
		if(!empty($this->db_host)){$db['host'] = $this->db_host; unset($this->db_host);} else {$db['host'] = 'localhost';}
		if(!empty($this->db_table)){$db['table'] = $this->db_table; unset($this->db_table);} else {$db['table'] = 'userz';}
		if(!empty($this->db_driver)){$db['driver'] = $this->db_driver; unset($this->db_driver);} else {$db['driver'] = 'PDO';}
		if(!empty($db)){
			#TODO ~ determine driver from config
			if(class_exists('oPDO')){
				$zernDB = new oPDO($db);
				if(is_object($zernDB)){
					$this->DB = $zernDB;

					/*** Call Auth ***/
					if(class_exists('oAuth')){
						if(!empty($this->oURL)){$zernAuth = new oAuth($this->DB, $this->oURL, $this->oLink);}
						else {$zernAuth = new oAuth($this->DB);}
						if(is_object($zernAuth)){$this->Auth = $zernAuth;}
					}
				}
			}
		}
		return;
	}
	//==========** END **==========//


	//========== FILE LOADER [only add files] ==========//
	public static function inc($file='', $eval='oREQUIRED'){
		if(!empty($file) && !is_file($file)){$file = $file.'.php';}
		if(file_exists($file)){require $file;}
		elseif($eval == 'oREQUIRED'){
			if(!defined('oAPPMODE') || oAPPMODE != 'dev'){
				exit('ZE404-IF: '.basename($file));
			}
			else {
				exit('Missing Library: '.$file);
			}
		}
	}
	//==========** END **==========//


	//========== DEBUG ==========//
	public static function dbug($data, $printAs='', $continue='oYEAP'){
		if($printAs == 'oJSON'){jsonResp($data);}
		else {
			echo '<p><em>Debugging</em></p><hr>';
			if($printAs == 'oPRINT'){print_r($data);}
			elseif($printAs == 'oDUMP'){var_dump($data);}
			elseif($printAs == 'oEXPORT'){var_export($data);}
			elseif($data === true){echo "Bool: TRUE";}
			elseif($data === false){echo "Bool: FALSE";}
			elseif(is_int($data)){echo $data;}
			elseif(is_string($data) && $printAs == 'string'){echo $data;}
			elseif(is_array($data)){
				foreach ($data as $key => $value){
					if(is_array($value)){
						foreach ($value as $valueKey => $valueSub){
							echo ' <strong>'.$key."</strong>['".$valueKey."']".': '.$valueSub.'<br>';
						}
					}
					else {
						echo '<strong>'.$key.':</strong> '. $value.'<br>';
					}
				}
			}
			else {
				var_dump($data);
			}
		}
		if($continue == 'oNOPE'){exit;}
	}
	//==========** END **==========//
}
?>