ALTER TABLE llx_asset_workstation_task ADD INDEX ( fk_workstation );
ALTER TABLE llx_projet_task_extrafields ADD INDEX ( fk_of );
ALTER TABLE llx_projet_task_extrafields ADD INDEX ( fk_gantt_parent_task );
ALTER TABLE llx_projet_task ADD INDEX ( dateo );
ALTER TABLE llx_projet_task ADD INDEX ( datee );
ALTER TABLE llx_projet_task ADD INDEX ( progress );
ALTER TABLE llx_projet_task ADD INDEX ( planned_workload );
ALTER TABLE llx_projet_task ADD INDEX dateo_datee (dateo, datee);
ALTER TABLE llx_projet ADD INDEX ( fk_statut );
ALTER TABLE llx_assetOf ADD INDEX ( status );
ALTER TABLE llx_projet ADD INDEX ( dateo );
ALTER TABLE llx_projet ADD INDEX ( datee );
ALTER TABLE llx_actioncomm_extrafields CHANGE fk_workstation fk_workstation INT( 11 ) NOT NULL DEFAULT '0';
ALTER TABLE llx_actioncomm_extrafields ADD INDEX ( fk_workstation ) ;
ALTER TABLE llx_actioncomm ADD INDEX ( fk_action ) ;
ALTER TABLE llx_commande_fournisseurdet ADD INDEX ( fk_product ) ;
ALTER TABLE llx_commande_fournisseurdet ADD INDEX ( fk_commande ) ;
ALTER TABLE llx_commande ADD INDEX ( fk_availability ) ;
ALTER TABLE llx_commande ADD INDEX ( fk_incoterms ) ;