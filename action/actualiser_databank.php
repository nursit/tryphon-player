<?php
/**
 * Mettre a jour les documents databank d'un objet
 *
 * @plugin     Tryphon
 * @copyright  2014
 * @author     Cédric
 * @licence    GNU/GPL
 * @package    SPIP\Tryphon\action
 */


function action_actualiser_databank_dist($arg=null){

	if (is_null($arg)){
		$securiser_action = charger_fonction("securiser_action","inc");
		$arg = $securiser_action();
	}

	list($objet,$id_objet) = explode("-",$arg);

	$ids = sql_allfetsel("id_document","spip_documents_liens","id_objet=".intval($id_objet)." AND objet=".sql_quote($objet));
	$ids = array_map('reset',$ids);
	$documents = sql_allfetsel("*","spip_documents","distant=".sql_quote('oui')." AND ".sql_in('id_document',$ids));
	if ($documents){
		include_spip("action/editer_document");
		foreach($documents as $document){
			if ($cast = tryphon_is_url_cast($document['fichier'])){
				// il suffit de faire modifier, pre-edition va charger les infos automatiquement
				document_modifier($document['id_document'],array('fichier'=>$document['fichier']));
			}
		}
	}
}
