<?php
/**
 * Fichier gérant l'installation du plugin Tryphon
 *
 * @plugin     Tryphon
 * @copyright  2014
 * @author     Cédric
 * @licence    GNU/GPL
 * @package    SPIP\Tryphon\Pipelines
 */


/**
 * Declaration des champs complementaires sur la table auteurs, pour les profils
 *
 * @param  $tables
 * @return
 */
function tryphon_declarer_tables_objets_sql($tables){

	$tables['spip_documents']['field']['restreint'] = "tinyint DEFAULT 0 NOT NULL";
	$tables['spip_documents']['champs_editables'][] = 'restreint';

	return $tables;
}


/**
 * Fonction d'installation et de mise à jour du plugin Tryphon
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @param string $version_cible
 *     Version du schéma de données dans ce plugin (déclaré dans paquet.xml)
 * @return void
**/
function tryphon_upgrade($nom_meta_base_version, $version_cible) {
	$maj = array();

	$maj['create'] = array(
		array('maj_tables', array('spip_documents')),
	);
	$maj['0.4.0'] = array(
		array('maj_tables', array('spip_documents')),
	);

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}
