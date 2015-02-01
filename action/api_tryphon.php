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
		$url = array_pop($arg);
		$url = base64_decode($url);
		// on repasse l'url en absolu si besoin
		$url = url_absolue($url,"http://audiobank.tryphon.org/");
	}

	switch($action){
		case "lowtoken":
			if (count($arg)>2){
				$id_auteur = $arg[1];
				$cle = $arg[2];
				include_spip('inc/acces');
				// si pas de droit, on redirige vers l'url fournie par tryphon_test_acces
				if (function_exists("tryphon_test_acces") AND $r=tryphon_test_acces($url,$id_auteur)){
					$url = $r;
				}
				// sinon on verifie la cle lowsec avant de poser le jeton tryphon
				elseif (verifier_low_sec($id_auteur,$cle,$url)){
					$url = tryphon_url_api_key_token($url,$GLOBALS['ip']);
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
				$GLOBALS['redirect'] = tryphon_url_api_key_token($url,$GLOBALS['ip']);
			}
			break;
	}
}