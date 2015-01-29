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

	switch($action){
		case "token":
			$url = _request('u');
			// si pas de droit, on redirige vers l'url fournie par tryphon_test_acces
			if (function_exists("tryphon_test_acces") AND $r=tryphon_test_acces($url)){
				$GLOBALS['redirect'] = $r;
			}
			else {
				$key = (defined('_TRYPHON_API_KEY')?_TRYPHON_API_KEY:"a valid api key");
				$ip_address = $GLOBALS['ip'];
				$seconds = round(time()/300,0);
	      $data = $key . "-" . $ip_address . "-" . $seconds;
				//var_dump($data);
				//var_dump($token);
				$token = hash("sha256",$data);
				$GLOBALS['redirect'] = parametre_url($url,"token",$token);
			}
			break;
	}
}
