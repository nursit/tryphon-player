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

function tryphon_affiche_duree($duree){
	if (!$duree) return "";
	$h = $m = $s = "";
	if ($duree>3600){
		$h = intval(floor($duree)/3600);
		$duree -= intval($h*3600);
	}
	if ($duree>60){
		$m = intval(floor($duree)/60);
		$duree -= intval($m*60);
	}
	$s = $duree;
	return ($h?"$h:":"").str_pad($m,$h?2:1,"0",STR_PAD_LEFT).":".str_pad($s,2,"0",STR_PAD_LEFT);
}

function tryphon_url_cast($cast,$restreint=false){
	$url = "http://audiobank.tryphon.eu/casts/$cast";
	if ($restreint){
		include_spip('inc/filtres');
		$url = tryphon_url_tokenize($url);
	}
	return $url;
}

function tryphon_url_stream(){
	return "http://beta-stream.tryphon.eu/labas";
}

function tryphon_url_api_key_token($url,$ip_address){
	$key = (defined('_TRYPHON_API_KEY')?_TRYPHON_API_KEY:"a valid api key");
	if ($ip_address=="87.98.221.160")
		$ip_address = "176.31.236.173";
	$seconds = round(time()/300,0);
	$data = $key . "-" . $ip_address . "-" . $seconds;
	//var_dump($data);
	//var_dump($token);
	$token = hash("sha256",$data);
	return parametre_url($url,"token",$token);
}

function tryphon_url_tokenize($url){
	$joli = basename($url);
	// on passe l'url en relatif si possible, pour la raccourcir
	if (strncmp($url,"http://audiobank.tryphon.eu/",28)==0){
		$url = substr($url,28);
	}
	$url = url_absolue(_DIR_RACINE."tryphon.api/token/".base64_encode($url)."/$joli",defined('_DEV_LABAS')?"http://dev_la-bas.nursit.com/":"");
	return $url;
}

function tryphon_url_detokenize($url){
	if (strpos($url,"/tryphon.api/token/?u=")!==false){
		$url = parametre_url($url,"u");
	}
	elseif (strpos($url,"/tryphon.api/token/")!==false){
		$parts = explode("/",$url);
		$url = array_pop($parts);
		$url = array_pop($parts);
		$url = base64_decode($url);
		// on repasse l'url en absolu si besoin
		$url = url_absolue($url,"http://audiobank.tryphon.eu/");
	}
	return $url;
}

function tryphon_url_son_lowsec($url,$id_auteur){
	include_spip("inc/acces");
	if (intval($id_auteur) AND strpos($url,"/tryphon.api/token/")!==false){
		$url_son = tryphon_url_detokenize($url);
		$low_sec = afficher_low_sec($id_auteur,$url_son);
		$url = str_replace("/tryphon.api/token/","/tryphon.api/lowtoken/$id_auteur/$low_sec/",$url);
		if (defined('_DEV_LABAS')){
			$url = str_replace("/la-bas.org/","/dev_la-bas.nursit.com/",$url);
		}
	}
	return $url;
}


/**
 * Trouver la source ogg correspondante au mp3
 * @param $src
 * @return string
 */
function tryphon_source_ogg($src){
	$url_mp3 = tryphon_url_detokenize($src);
	if (preg_match(",https?://audiobank.tryphon.(?:org|eu)/casts/.*([.]mp3)?$,Uims",$url_mp3,$m)){
		$url_ogg = substr($url_mp3,0,-4).".ogg";
		if ($url_mp3==$src)
			return $url_ogg;
		else
			return tryphon_url_tokenize($url_ogg);
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

		$js = produire_fond_statique("javascript/tryphon-player.js");
		if ($m = filemtime($js)){
			$js .= "?$m";
		}
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
		$url = tryphon_url_detokenize($url);
		if (preg_match(",https?://audiobank.tryphon.(?:org|eu)/casts/(.*)([.](mp3|ogg))?$,Uims",$url,$m)){
			$cast = $m[1];
			if (strpos($cast,".")){
				$cast = explode(".",$cast);
				$cast = reset($cast);
			}
		}
	}
	return $cast;
}

/**
 * Mettre a jour un document cast Tryphon lors de l'enregistrement
 * Ne pas modifier lowsec quand on change le mot de passe
 * @param array $flux
 * @return array mixed
 */
function tryphon_pre_edition($flux){
	if ($flux['args']['table']=="spip_documents"
	  AND $id_document=intval($flux['args']['id_objet'])
	  AND $flux['args']['action']=='modifier'){
		if (isset($flux['data']['fichier']))
			$source = $flux['data']['fichier'];
		else {
			$source = sql_getfetsel("fichier","spip_documents","id_document=".intval($id_document)." AND distant='oui'");
		}
		if ($source
		  AND $cast = tryphon_is_url_cast($source)){
			$infos = tryphon_renseigner_cast($cast);
			$flux['data'] = array_merge($flux['data'],$infos);
		}
	}

	if ($flux['args']['table']=="spip_auteurs"
	  AND $id_auteur=intval($flux['args']['id_objet'])
	  AND $flux['args']['action']=='modifier'){

		if (isset($flux['data']['low_sec'])
		  AND !strlen($flux['data']['low_sec'])) {
			unset($flux['data']['low_sec']);
		}

	}

	return $flux;
}

function tryphon_post_edition($flux){
	if ($flux['args']['table']=="spip_articles"
	  AND $id_article=intval($flux['args']['id_objet'])){
		$champs = array('descriptif','chapo','texte','ps','visuel');
		$set = array();
		foreach($champs as $champ){
			if (isset($flux['data'][$champ])){
				preg_match_all(",https?://audiobank.tryphon.(?:org|eu)/casts/\w*([.]mp3)\b,ims",$flux['data'][$champ],$matches,PREG_SET_ORDER);
				if ($matches){
					foreach($matches as $match){
						$url = $match[0];
						if ($cast = tryphon_is_url_cast($url)){
							$url = tryphon_url_cast($cast).".mp3";
							$url_tok = tryphon_url_tokenize($url);
							if (!$id_document = sql_getfetsel("id_document","spip_documents","distant='oui' AND (fichier=".sql_quote($url)." OR fichier=".sql_quote($url_tok).")")){
								$ajouter_documents = charger_fonction("ajouter_documents","action");
								// TODO insertion document en base ici
								$file = array(
									'distant' => true,
									'tmp_name' => $url,
									'name' => basename($url),
								);
								// eviter de declencher les conflits
								$save = $_POST;$_POST = array();
								$ids = $ajouter_documents(0,array($file),"article",$id_article,"document");
								$_POST = $save;
								$id_document = reset($ids);
							}
							if ($id_document){
								if (!isset($set[$champ]))
									$set[$champ] = $flux['data'][$champ];
								$set[$champ] = str_replace($match[0],"<emb$id_document>",$set[$champ]);
							}
						}
					}
				}
			}
		}
		if (count($set)){
			$save = $_POST;$_POST = array();
			article_modifier($id_article,$set);
			$_POST = $save;
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
	static $infos = array();
	if (isset($infos[$cast])){
		return $infos[$cast];
	}
	$url = tryphon_url_cast($cast);
	$url_mp3 = "http://audiobank.tryphon.eu/casts/$cast.mp3";
	$url_ogg= "http://audiobank.tryphon.eu/casts/$cast.ogg";

	$infos[$cast] = array(
		'restreint' => 0,
		'distant' => 'oui',
		'media' => 'audio',
		'mode' => 'document',
		'fichier' => $url_mp3,
		'extension' => 'mp3',
	);
	include_spip("inc/distant");
	include_spip("inc/filtres");

	// recuperer les infos
	if ($json = recuperer_page("$url.json")
	  AND $json = json_decode($json,true)){
		if (isset($json['protected']) AND $json['protected']){
			$infos[$cast]['restreint'] = 1;
			$url_mp3 = tryphon_url_tokenize($url_mp3);
			$url_ogg = tryphon_url_tokenize($url_ogg);
			$infos[$cast]['fichier'] = $url_mp3;
		}

		if (isset($json['title']))
			$infos[$cast]['titre'] = $json['title'];
		if (isset($json['author']))
			$infos[$cast]['credits'] = $json['author'];
		if (isset($json['duration']))
			$infos[$cast]['duree'] = $json['duration'];
		if (isset($json['formats']['mp3']['size']))
			$infos[$cast]['taille'] = $json['formats']['mp3']['size'];
		#var_dump($json);
		#var_dump($infos[$cast]);
	}

	return $infos[$cast];
}

function tryphon_afficher_complement_objet($flux){
	$flux['data'] .= recuperer_fond("prive/squelettes/inclure/documents-databank",array('id_objet'=>$flux['args']['id'],'objet'=>$flux['args']['type']));
	return $flux;
}