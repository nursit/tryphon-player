<?php
/**
 * Fichier gérant l'api du player Tryphon
 *
 * @plugin     Tryphon
 * @copyright  2014
 * @author     Cédric
 * @licence    GNU/GPL
 * @package    SPIP\Tryphon\action
 */


function action_api_tryphon_dist(){

	$arg = _request('arg');
	$arg = explode("/",$arg);
	$action = reset($arg);

	if (!$url = _request('u')){
		$url = array_pop($arg);
		$url = urldecode($url);
		if (strpos($url,"/")===false AND strpos($url,"=.")!==false){
			$url = explode(".",$url);
			$url = reset($url);
			$url = base64_decode($url);
		}
		elseif(strpos($url,"//")==false){
			$url = array_pop($arg);
			$url = base64_decode($url);
		}
	}

	switch($action){
		case "lowtoken":
			if (count($arg)>2){
				$id_auteur = $arg[1];
				$cle = $arg[2];
				include_spip('inc/acces');
				if (verifier_low_sec($id_auteur,$cle,$url)){
					$url = tryphon_tokenize_url($url);
				}
			}
			$GLOBALS['redirect'] = $url;
			break;
		case "token":
			// si pas de droit, on redirige vers l'url fournie par tryphon_test_acces
			if (function_exists("tryphon_test_acces") AND $r=tryphon_test_acces($url)){
				$GLOBALS['redirect'] = $r;
			}
			else {
				$GLOBALS['redirect'] = tryphon_tokenize_url($url);
			}
			break;
	}
}

function tryphon_tokenize_url($url){
	$key = (defined('_TRYPHON_API_KEY')?_TRYPHON_API_KEY:"a valid api key");
	$ip_address = $GLOBALS['ip'];
	if ($ip_address=="87.98.221.160")
		$ip_address = "176.31.236.173";
	$seconds = round(time()/300,0);
	$data = $key . "-" . $ip_address . "-" . $seconds;
	//var_dump($data);
	//var_dump($token);
	$token = hash("sha256",$data);
	return parametre_url($url,"token",$token);
}