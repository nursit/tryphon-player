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


/**
 * insertion des traitements oembed dans l'ajout des documents distants
 * reconnaitre une URL oembed (car provider declare ou decouverte automatique active)
 * et la pre-traiter pour recuperer le vrai document a partir de l'url concernee
 *
 * @param array $flux
 * @return array
 */
function tryphon_renseigner_document_distant($flux) {
	#var_dump($flux);

	http://audiobank.tryphon.org/casts/bjwmsv7a.mp3
	// on tente de récupérer les données oembed
	if ($source = $flux['source']
	  AND preg_match(",https?://audiobank.tryphon.org/casts/(.*)([.](mp3|ogg))?$,Uims",$source,$m)){
		$cast = $m[1];
		#var_dump($m);
		$doc = tryphon_renseigner_cast($cast);
		return $doc;
	}

	return $flux;
}

function tryphon_renseigner_cast($cast){
	$url = "http://audiobank.tryphon.org/casts/$cast";
	$url_mp3 = "http://audiobank.tryphon.org/casts/$cast.mp3";
	$url_ogg= "http://audiobank.tryphon.org/casts/$cast.ogg";

	$infos = array(
		'restreint' => 0,
		'distant' => 'oui',
		'media' => 'audio',
		'mode' => 'document',
	);
	include_spip("inc/distant");
	include_spip("inc/filtres");
	if (!$res = recuperer_page($url_mp3,false,true)){
		$infos['restreint'] = 1;
		$url_mp3 = url_absolue(_DIR_RACINE."tryphon.api/token/?u=".urlencode($url_mp3));
		$url_ogg = url_absolue(_DIR_RACINE."tryphon.api/token/?u=".urlencode($url_ogg));
	}
	$infos['fichier'] = $url_mp3;
	$infos['extension'] = 'mp3';

	// recuperer les infos
	if ($embed = recuperer_page($url)){
		#var_dump($embed);
		preg_match_all(",<span\s*class=\"tp-(author|title)\">(.*)</span>,Uims",$embed,$matches,PREG_SET_ORDER);
		foreach($matches as $match){
			$texte = tryphon_importe_texte($match[2]);
			if ($match[1]=='author')
				$infos['credits'] = $texte;
			elseif ($match[1]=='title')
				$infos['titre'] = $texte;
		}
	}

	return $infos;
}

function tryphon_importe_texte($texte){
	$texte = trim(unicode2charset(html2unicode($texte)));
	// &#x;
	$vu = array();
	if (preg_match_all(',&#x0*([0-9a-f]+);,iS', $texte, $regs, PREG_SET_ORDER))
		foreach ($regs as $reg){
			if (!isset($vu[$reg[0]]))
				$vu[$reg[0]] = caractere_utf_8(hexdec($reg[1]));
		}
	return str_replace(array_keys($vu), array_values($vu), $texte);
}

#var_dump(tryphon_renseigner_document_distant(array('source'=>'http://audiobank.tryphon.org/casts/bjwmsv7a.mp3')));
#die();