<?php
/**
 * Fichier gérant les options du plugin Tryphon
 *
 * @plugin     Tryphon
 * @copyright  2014
 * @author     Cédric
 * @licence    GNU/GPL
 * @package    SPIP\Tryphon\Pipelines
 */

/**
 * Inserer le js d'init du player Tryphon pour les liens faits a la main
 * @param $flux
 * @return string
 */
function tryphon_insert_head($flux){
	$js = timestamp(produire_fond_statique("javascript/tryphon-player.js"));
	$js_init = timestamp(find_in_path("javascript/tryphon-player-init.js"));

	$flux .= "<script type=\"application/javascript\">var tryphon_player_script='$js';</script>\n";
	$flux .= "<script src=\"$js_init\" type=\"application/javascript\"></script>\n";

	return $flux;
}

function tryphon_can_play($url){
	if (function_exists("tryphon_test_acces") AND $r=tryphon_test_acces($url)){
		return false;
	}
	return true;
}

function tryphon_url_cast($cast){
	return "http://audiobank.tryphon.eu/casts/$cast";
}

function tryphon_url_stream(){
	return "http://beta-stream.tryphon.eu/labas";
}