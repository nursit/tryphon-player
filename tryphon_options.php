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

function tryphon_url_cast($cast,$restreint=false){
	$url = "http://audiobank.tryphon.eu/casts/$cast";
	if ($restreint){
		include_spip('inc/filtres');
		$url = url_absolue(_DIR_RACINE."tryphon.api/token/?u=".urlencode($url));
	}
	return $url;
}

function tryphon_url_stream(){
	return "http://beta-stream.tryphon.eu/labas";
}

function tryphon_url_son_lowsec($url,$id_auteur){
	include_spip("inc/acces");
	if (intval($id_auteur) AND strpos($url,"/tryphon.api/token/")!==false){
		$url_son = parametre_url($url,"u");
		$low_sec = afficher_low_sec($id_auteur,$url_son);
		$url = str_replace("/tryphon.api/token/","/tryphon.api/lowtoken/$id_auteur/$low_sec/",$url);
		$url = str_replace("/la-bas.org/","/dev_la-bas.nursit.com/",$url);
	}
	return $url;
}


/**
 * Trouver la source ogg correspondante au mp3
 * @param $src
 * @return string
 */
function tryphon_source_ogg($src){
	if (preg_match(",https?://audiobank.tryphon.(?:org|eu)/casts/.*([.]mp3)?$,Uims",$src,$m)
	  OR (strpos($src,"/tryphon.api/token/?u=")!==false AND substr($src,-4)==".mp3")){
		$src = substr($src,0,-4).".ogg";
		return $src;
	}
	return "";
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
 * Detecter une URL cast tryphon, qui peut etre directe ou via tryphon.api/token/
 * et extraire le numero de cast
 * @param string $url
 * @return string
 */
function tryphon_is_url_cast($url){
	$cast = "";
	if ($url){
		if (strpos($url,"/tryphon.api/token/?u=")!==false){
			$url = parametre_url($url,"u");
		}
		if (preg_match(",https?://audiobank.tryphon.(?:org|eu)/casts/(.*)([.](mp3|ogg))?$,Uims",$url,$m)){
			$cast = $m[1];
		}
	}
	return $cast;
}

/**
 * Mettre a jour un document cast Tryphon lors de l'enregistrement
 * @param array $flux
 * @return array mixed
 */
function tryphon_pre_edition($flux){
	if ($flux['args']['table']=="spip_documents"
	  AND $id_document=intval($flux['args']['id_objet'])
	  AND $flux['args']['action']=='modifier'){
		if (isset($flux['fichier']))
			$source = $flux['fichier'];
		else {
			$source = sql_getfetsel("fichier","spip_documents","id_document=".intval($id_document)." AND distant='oui'");
		}
		if ($source
		  AND $cast = tryphon_is_url_cast($source)){
			$infos = tryphon_renseigner_cast($cast);
			$flux['data'] = array_merge($flux['data'],$infos);
		}
	}
	return $flux;
}

/**
 * insertion des traitements sur documents tryphon dans l'ajout des documents distants
 * reconnaitre une URL tryphon
 * et la pre-traiter pour recuperer les meta-infos du son (pretection, duree, titre, auteur)
 *
 * @param array $flux
 * @return array
 */
function tryphon_renseigner_document_distant($flux) {
	#var_dump($flux);

	http://audiobank.tryphon.org/casts/bjwmsv7a.mp3
	// on tente de récupérer les données oembed
	if ($source = $flux['source']
		AND $cast = tryphon_is_url_cast($source)){
		$doc = tryphon_renseigner_cast($cast);
		return $doc;
	}

	return $flux;
}

/**
 * Aller chercher les infos d'un CAST
 * @param $cast
 * @return array
 */
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
	if (!$res = recuperer_page($url_mp3,false,true,0)){
		$infos['restreint'] = 1;
		$url_mp3 = url_absolue(_DIR_RACINE."tryphon.api/token/?u=".urlencode($url_mp3),"http://dev_la-bas.nursit.com/");
		$url_ogg = url_absolue(_DIR_RACINE."tryphon.api/token/?u=".urlencode($url_ogg));
		$url_lowsec = tryphon_url_son_lowsec($url_mp3,250);
		$res = recuperer_page($url_lowsec,false,true,0);
	}
	if ($res AND preg_match(",Content-Length:\s*(\d+)$,Uims",$res,$m))
		$infos['taille'] = $m[1];
	$infos['fichier'] = $url_mp3;
	$infos['extension'] = 'mp3';

	// recuperer les infos
	if ($json = recuperer_page("$url.json")
	  AND $json = json_decode($json,true)){
		#var_dump($json);
		if (isset($json['title']))
			$infos['titre'] = $json['title'];
		if (isset($json['author']))
			$infos['credits'] = $json['author'];
		if (isset($json['duration']))
			$infos['duree'] = $json['duration'];
	}

	return $infos;
}

function tryphon_afficher_complement_objet($flux){
	$flux['data'] .= recuperer_fond("prive/squelettes/inclure/documents-databank",array('id_objet'=>$flux['args']['id'],'objet'=>$flux['args']['type']));
	return $flux;
}