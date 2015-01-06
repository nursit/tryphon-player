<?php
/**
 * Fichier gérant les pipelines du plugin Tryphon
 *
 * @plugin     Tryphon
 * @copyright  2014
 * @author     Cédric
 * @licence    GNU/GPL
 * @package    SPIP\Tryphon\Pipelines
 */


/**
 * Inserer le js d'init du player Tryphon
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