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

function tryphon_insert_head_css($flux){
	if ($f = find_in_path("css/tryphon.css")){
		$f = timestamp($f);
		$flux .= "<link href='$f' rel='stylesheet' />\n";
	}
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

/**
 * Inserer le js d'init du player Tryphon pour les liens faits a la main
 * @param $flux
 * @return string
 */
function tryphon_affichage_final($flux){
	if ($GLOBALS['html']
	  AND strpos($flux,"tryphon.eu")!==false
		AND strpos($flux,"Tryphon.Player.setup")===false) {

		$js = timestamp(produire_fond_statique("javascript/tryphon-player.js"));
		lire_fichier(find_in_path("javascript/tryphon-player-init.js"),$js_init);
		$ins = "\n<script type=\"application/javascript\">var tryphon_player_script='$js';\n$js_init</script>";
		if ($p = stripos($flux,"</body>")){
			$flux = substr_replace($flux,$ins,$p,0);
		}
		else {
			$flux .= $ins;
		}
	}
	return $flux;
}