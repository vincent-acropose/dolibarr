TRUNCATE TABLE llx_c_payment_term;
INSERT INTO llx_c_payment_term (rowid, code, sortorder, active, libelle, libelle_facture, fdm, nbjour, decalage, module) VALUES
(1, 'RECEP', 1, 1, 'A réception de facture', 'Réception de facture', 0, 0, NULL, NULL),
(2, '30D', 2, 1, '30 jours', 'Réglement à 30 jours', 0, 30, NULL, NULL),
(3, '30DENDMONTH', 3, 0, '30 jours fin de mois', 'Réglement à 30 jours fin de mois', 1, 30, NULL, NULL),
(4, '60D', 4, 1, '60 jours', 'Réglement à 60 jours', 0, 60, NULL, NULL),
(5, '60DENDMONTH', 5, 0, '60 jours fin de mois', 'Réglement à 60 jours fin de mois', 1, 60, NULL, NULL),
(6, 'PT_ORDER', 6, 0, 'A réception de commande', 'A réception de commande', 0, 0, NULL, NULL),
(7, 'PT_DELIVERY', 7, 0, 'Livraison', 'Règlement à la livraison', 0, 0, NULL, NULL),
(8, 'PT_5050', 8, 0, '50 et 50', 'Règlement 50% à la commande, 50% à la livraison', 0, 0, NULL, NULL),
(9, '45D', NULL, 1, '45 jours', 'Règlement à 45 jours', 0, 45, NULL, NULL),
(10, '90D', NULL, 1, '90 jours', 'Règlement à 90 jours', 0, 90, NULL, NULL),
(11, '85D', NULL, 1, '85 jours', 'Règlement à 85 jours', 0, 85, NULL, NULL),
(12, '75D', NULL, 1, '75 jours', 'Règlement à 75 jours', 0, 75, NULL, NULL),
(13, '100D', NULL, 1, '100 jours', 'Règlement à 100 jours', 0, 100, NULL, NULL),
(14, '70D', NULL, 1, '70 jours', 'Règlement à 70 jours', 0, 70, NULL, NULL);

ALTER TABLE llx_societe MODIFY COLUMN import_key varchar(36);
ALTER TABLE llx_societe MODIFY COLUMN nom varchar(80);
ALTER TABLE llx_socpeople MODIFY COLUMN import_key varchar(36);
ALTER TABLE llx_product MODIFY COLUMN import_key varchar(36);
ALTER TABLE llx_agefodd_stagiaire MODIFY COLUMN import_key varchar(36);
ALTER TABLE llx_agefodd_session_stagiaire MODIFY COLUMN import_key varchar(36);
ALTER TABLE llx_propal MODIFY COLUMN import_key varchar(36);
ALTER TABLE llx_facture MODIFY COLUMN import_key varchar(36);
ALTER TABLE llx_facturedet MODIFY COLUMN import_key varchar(36);
ALTER TABLE llx_agefodd_place ADD COLUMN import_key varchar(36) DEFAULT NULL;
ALTER TABLE llx_agefodd_formation_catalogue ADD COLUMN import_key varchar(36) DEFAULT NULL;
ALTER TABLE llx_user ADD COLUMN import_key varchar(36) DEFAULT NULL;

ALTER TABLE llx_societe ADD INDEX idx_llx_societe_import_key (import_key);
ALTER TABLE llx_propal ADD INDEX idx_llx_propal_import_key (import_key);
ALTER TABLE llx_agefodd_session ADD INDEX idx_llx_agefodd_session_import_key (import_key);
ALTER TABLE llx_user ADD INDEX idx_llx_user_import_key (import_key);
ALTER TABLE llx_socpeople ADD INDEX idx_llx_socpeople_import_key (import_key);



TRUNCATE TABLE llx_extrafields;
INSERT INTO llx_extrafields (name, entity, elementtype, tms, label, type, size, fieldunique, fieldrequired, pos, param) VALUES
('ent_002', 1, 'societe', '2013-08-26 13:57:02', 'Gestionnaire', 'sellist', '', 0, 0, 3, 'a:1:{s:7:"options";a:1:{s:16:"user:login:rowid";N;}}'),
('ent_001', 1, 'societe', '2013-08-24 10:39:29', 'Secteur d''activité', 'sellist', '', 0, 0, 1, 'a:1:{s:7:"options";a:1:{s:19:"c_typent:libelle:id";N;}}'),
('ent_003', 1, 'societe', '2013-09-01 12:02:48', 'Prospecteur', 'sellist', '', 0, 0, 2, 'a:1:{s:7:"options";a:1:{s:16:"user:login:rowid";N;}}'),
('con_001', 1, 'socpeople', '2013-08-24 20:00:08', 'Etat', 'radio', '', 0, 0, 1, 'a:1:{s:7:"options";a:2:{i:1;s:5:"Actif";i:2;s:7:"Inactif";}}'),
('con_003', 1, 'socpeople', '2013-08-24 12:05:58', 'Service', 'sellist', '', 0, 0, 3, 'a:1:{s:7:"options";a:1:{s:19:"c_typent:libelle:id";N;}}'),
('con_004', 1, 'socpeople', '2013-08-24 12:06:09', 'Fonction', 'sellist', '', 0, 0, 4, 'a:1:{s:7:"options";a:1:{s:19:"c_typent:libelle:id";N;}}'),
('con_006', 1, 'socpeople', '2013-08-24 20:00:49', 'Destinataire', 'checkbox', '', 0, 0, 6, 'a:1:{s:7:"options";a:3:{i:1;s:10:"Invitation";i:2;s:9:"Catalogue";i:3;s:5:"Voeux";}}'),
('con_008', 1, 'socpeople', '2013-08-24 12:07:12', 'Origine du contact', 'sellist', '', 0, 0, 8, 'a:1:{s:7:"options";a:1:{s:19:"c_typent:libelle:id";N;}}'),
('con_009', 1, 'socpeople', '2013-08-24 12:07:25', 'Précisions sur l''origine', 'varchar', '255', 0, 0, 9, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
('con_005', 1, 'socpeople', '2013-09-27 16:10:07', 'Nature du contact', 'radio', '', 0, 0, 5, 'a:1:{s:7:"options";a:4:{i:1;s:3:"VIP";i:2;s:9:"Principal";i:3;s:10:"Secondaire";s:0:"";N;}}'),
('con_002', 1, 'socpeople', '2013-08-24 15:17:33', 'Langue de communication', 'radio', '', 0, 0, 2, 'a:1:{s:7:"options";a:3:{i:1;s:9:"Français";i:2;s:7:"Anglais";i:3;s:5:"Autre";}}'),
('con_007', 1, 'socpeople', '2013-08-24 20:01:01', 'Fidélité', 'radio', '', 0, 0, 7, 'a:1:{s:7:"options";a:3:{i:1;s:6:"Silver";i:2;s:4:"Gold";i:3;s:8:"Platinum";}}'),
('con_010', 1, 'socpeople', '2013-08-24 12:52:16', 'Membre de Magellan', 'radio', '', 0, 0, 10, 'a:1:{s:7:"options";a:2:{i:1;s:3:"Oui";i:2;s:3:"Non";}}'),
('con_012', 1, 'socpeople', '2013-08-24 15:16:36', 'Sujets d''intérêt', 'checkbox', '', 0, 0, 11, 'a:1:{s:7:"options";a:20:{i:1;s:5:"Achat";i:2;s:10:"A distance";i:3;s:10:"Changement";i:4;s:10:"Commercial";i:5;s:13:"Communication";i:6;s:8:"Conflits";i:7;s:16:"Dévpt personnel";i:8;s:10:"Diversité";i:9;s:12:"Expatriation";i:10;s:6:"Export";i:11;s:7:"Finance";i:12;s:14:"Interculturel ";i:13;s:9:"Juridique";i:14;s:10:"Management";i:15;s:9:"Marketing";i:16;s:13:"Négociation ";i:17;s:4:"Pays";i:18;s:6:"Projet";i:19;s:12:"Retour expat";i:20;s:2:"RH";}}'),
('con_011', 1, 'socpeople', '2013-08-29 08:03:43', 'Zones géographiques', 'checkbox', '', 0, 0, 12, 'a:1:{s:7:"options";a:13:{i:1;s:11:"Afrique Est";i:2;s:13:"Afrique Ouest";i:3;s:11:"Afrique Sud";i:4;s:14:"Amérique Nord";i:5;s:13:"Amérique Sud";i:6;s:15:"Asie Ext Orient";i:7;s:12:"Asie Sud Est";i:8;s:10:"Europe Est";i:9;s:12:"Europe Ouest";i:10;s:11:"Europe Nord";i:11;s:7:"Maghreb";i:12;s:8:"Océanie";s:0:"";N;}}'),
('fo_tarifjour_01', 1, 'socpeople', '2013-09-01 08:42:16', 'Tarif jour du consultant', 'int', '10', 0, 0, 13, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
('comment', 1, 'propal', '2013-09-01 11:14:27', 'Commentaire', 'text', '2000', 0, 0, 1, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
('ent_002', 1, 'agefodd_session', '2013-09-01 20:19:56', 'Gestionnaire', 'varchar', '255', 0, 0, 1, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
('cd_domaine', 1, 'agefodd_formation_catalogue', '2013-10-20 12:05:28', 'Domaine', 'select', '', 0, 0, 1, 'a:1:{s:7:"options";a:16:{s:5:"00000";s:5:"00000";s:3:"COA";s:3:"COA";s:3:"COF";s:3:"COF";s:5:"COMER";s:10:"Commercial";s:4:"COMM";s:13:"Communication";s:3:"CON";s:3:"CON";s:5:"EFPRO";s:27:"Efficacité professionnelle";s:5:"EXPAT";s:12:"Expatriation";s:3:"FOR";s:3:"FOR";s:3:"FRF";s:24:"Formations de formateurs";s:4:"JURI";s:9:"Juridique";s:6:"MARKET";s:9:"Marketing";s:4:"MGNT";s:10:"Management";s:4:"PAYS";s:14:"Expertise pays";s:2:"RH";s:19:"Ressources humaines";s:3:"SIC";s:24:"Interculturel transverse";}}');


ALTER TABLE llx_societe_extrafields ADD COLUMN ent_001 text DEFAULT NULL;
ALTER TABLE llx_societe_extrafields ADD COLUMN ent_002 text DEFAULT NULL;
ALTER TABLE llx_societe_extrafields ADD COLUMN ent_003 text DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN con_001 text DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN con_002 text DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN con_003 text DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN con_004 text DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN con_005 text DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN con_006 text DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN con_007 text DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN con_008 text DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN con_009 varchar(255) DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN con_010 text DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN con_011 text DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN con_012 text DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN fo_tarifjour_01 integer DEFAULT NULL;
ALTER TABLE llx_propal_extrafields ADD COLUMN comment text DEFAULT NULL;
ALTER TABLE llx_agefodd_session_extrafields ADD COLUMN ent_002 varchar(255) DEFAULT NULL;
ALTER TABLE llx_agefodd_formation_catalogue_extrafields ADD COLUMN cd_domaine text DEFAULT NULL;

TRUNCATE TABLE llx_const;
INSERT INTO llx_const (rowid, name, entity, value, type, visible, note, tms) VALUES
(465, 'MAIN_LANG_DEFAULT', 1, 'auto', 'chaine', 0, '', '2013-09-25 08:14:46'),
(2, 'MAIN_FEATURES_LEVEL', 0, '0', 'chaine', 1, 'Level of features to show (0=stable only, 1=stable+experimental, 2=stable+experimental+development', '2013-07-24 15:52:18'),
(3, 'MAILING_LIMIT_SENDBYWEB', 0, '25', 'chaine', 1, 'Number of targets to defined packet size when sending mass email', '2013-07-24 15:52:18'),
(4, 'SYSLOG_HANDLERS', 0, '["mod_syslog_file"]', 'chaine', 0, 'Which logger to use', '2013-07-24 15:52:18'),
(5, 'SYSLOG_FILE', 0, 'DOL_DATA_ROOT/dolibarr.log', 'chaine', 0, 'Directory where to write log file', '2013-07-24 15:52:18'),
(6, 'SYSLOG_LEVEL', 0, '7', 'chaine', 0, 'Level of debug info to show', '2013-07-24 15:52:18'),
(7, 'MAIN_MAIL_SMTP_SERVER', 0, '', 'chaine', 0, 'Host or ip address for SMTP server', '2013-07-24 15:52:18'),
(8, 'MAIN_MAIL_SMTP_PORT', 0, '', 'chaine', 0, 'Port for SMTP server', '2013-07-24 15:52:18'),
(9, 'MAIN_UPLOAD_DOC', 0, '2048', 'chaine', 0, 'Max size for file upload (0 means no upload allowed)', '2013-07-24 15:52:18'),
(414, 'MAIN_MONNAIE', 1, 'EUR', 'chaine', 0, '', '2013-08-24 08:03:49'),
(11, 'MAIN_MAIL_EMAIL_FROM', 1, 'robot@domain.com', 'chaine', 0, 'EMail emetteur pour les emails automatiques Dolibarr', '2013-07-24 15:52:18'),
(12, 'MAIN_SIZE_LISTE_LIMIT', 0, '25', 'chaine', 0, 'Longueur maximum des listes', '2013-07-24 15:52:18'),
(13, 'MAIN_SHOW_WORKBOARD', 0, '1', 'yesno', 0, 'Affichage tableau de bord de travail Dolibarr', '2013-07-24 15:52:18'),
(404, 'MAIN_MENU_STANDARD', 1, 'eldy_menu.php', 'chaine', 0, '', '2013-08-24 07:56:19'),
(406, 'MAIN_MENUFRONT_STANDARD', 1, 'eldy_menu.php', 'chaine', 0, '', '2013-08-24 07:56:19'),
(405, 'MAIN_MENU_SMARTPHONE', 1, 'eldy_menu.php', 'chaine', 0, '', '2013-08-24 07:56:19'),
(407, 'MAIN_MENUFRONT_SMARTPHONE', 1, 'eldy_menu.php', 'chaine', 0, '', '2013-08-24 07:56:19'),
(417, 'MAIN_INFO_SOCIETE_MAIL', 1, 'conseil@akteos.fr', 'chaine', 0, '', '2013-08-24 08:03:49'),
(18, 'MAIN_DELAY_ACTIONS_TODO', 1, '7', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur actions planifiées non réalisées', '2013-07-24 15:52:18'),
(19, 'MAIN_DELAY_ORDERS_TO_PROCESS', 1, '2', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur commandes clients non traitées', '2013-07-24 15:52:18'),
(20, 'MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS', 1, '7', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur commandes fournisseurs non traitées', '2013-07-24 15:52:18'),
(21, 'MAIN_DELAY_PROPALS_TO_CLOSE', 1, '31', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur propales à cloturer', '2013-07-24 15:52:18'),
(22, 'MAIN_DELAY_PROPALS_TO_BILL', 1, '7', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur propales non facturées', '2013-07-24 15:52:18'),
(23, 'MAIN_DELAY_CUSTOMER_BILLS_UNPAYED', 1, '31', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur factures client impayées', '2013-07-24 15:52:18'),
(24, 'MAIN_DELAY_SUPPLIER_BILLS_TO_PAY', 1, '2', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur factures fournisseur impayées', '2013-07-24 15:52:18'),
(25, 'MAIN_DELAY_NOT_ACTIVATED_SERVICES', 1, '0', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur services à activer', '2013-07-24 15:52:18'),
(26, 'MAIN_DELAY_RUNNING_SERVICES', 1, '0', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur services expirés', '2013-07-24 15:52:18'),
(27, 'MAIN_DELAY_MEMBERS', 1, '31', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur cotisations adhérent en retard', '2013-07-24 15:52:18'),
(28, 'MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE', 1, '62', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur rapprochements bancaires à faire', '2013-07-24 15:52:18'),
(29, 'MAIN_FIX_FOR_BUGGED_MTA', 1, '1', 'chaine', 1, 'Set constant to fix email ending from PHP with some linux ike system', '2013-07-24 15:52:18'),
(275, 'MAILING_EMAIL_FROM', 1, 'dolibarr@domain.com', 'chaine', 0, '', '2013-08-23 18:37:49'),
(276, 'MAILING_EMAIL_UNSUBSCRIBE_KEY', 1, '9203b218961d2941eeb534522e47b539', 'chaine', 0, '', '2013-08-23 18:37:49'),
(31, 'MAIN_MODULE_USER', 0, '1', NULL, 0, NULL, '2013-07-24 15:52:57'),
(32, 'MAIN_VERSION_LAST_INSTALL', 0, '3.4.1', 'chaine', 0, 'Dolibarr version when install', '2013-07-24 15:52:57'),
(408, 'MAIN_INFO_SOCIETE_COUNTRY', 1, '1:FR:France', 'chaine', 0, '', '2013-08-24 08:03:49'),
(409, 'MAIN_INFO_SOCIETE_NOM', 1, 'Akteos', 'chaine', 0, '', '2013-08-24 08:03:49'),
(410, 'MAIN_INFO_SOCIETE_ADDRESS', 1, '6 Rue du 4 Septembre', 'chaine', 0, '', '2013-08-24 08:03:49'),
(411, 'MAIN_INFO_SOCIETE_TOWN', 1, 'Issy-les-Moulineaux', 'chaine', 0, '', '2013-08-24 08:03:49'),
(412, 'MAIN_INFO_SOCIETE_ZIP', 1, '92130', 'chaine', 0, '', '2013-08-24 08:03:49'),
(413, 'MAIN_INFO_SOCIETE_STATE', 1, '94', 'chaine', 0, '', '2013-08-24 08:03:49'),
(426, 'SOCIETE_FISCAL_MONTH_START', 1, '1', 'chaine', 0, '', '2013-08-24 08:03:49'),
(427, 'FACTURE_TVAOPTION', 1, 'reel', 'chaine', 0, '', '2013-08-24 08:03:49'),
(415, 'MAIN_INFO_SOCIETE_TEL', 1, '01 55 95 85 10', 'chaine', 0, '', '2013-08-24 08:03:49'),
(416, 'MAIN_INFO_SOCIETE_FAX', 1, '01 55 95 85 11', 'chaine', 0, '', '2013-08-24 08:03:49'),
(418, 'MAIN_INFO_SOCIETE_WEB', 1, 'http://www.akteos.fr', 'chaine', 0, '', '2013-08-24 08:03:49'),
(53, 'MAIN_INFO_SOCIETE_LOGO', 1, 'logo_bande_fr.png', 'chaine', 0, '', '2013-07-24 15:57:08'),
(54, 'MAIN_INFO_SOCIETE_LOGO_SMALL', 1, 'logo_bande_fr_small.png', 'chaine', 0, '', '2013-07-24 15:57:08'),
(55, 'MAIN_INFO_SOCIETE_LOGO_MINI', 1, 'logo_bande_fr_mini.png', 'chaine', 0, '', '2013-07-24 15:57:08'),
(419, 'MAIN_INFO_CAPITAL', 1, '37000', 'chaine', 0, '', '2013-08-24 08:03:49'),
(420, 'MAIN_INFO_SOCIETE_FORME_JURIDIQUE', 1, '57', 'chaine', 0, '', '2013-08-24 08:03:49'),
(422, 'MAIN_INFO_SIRET', 1, ' 41205615200033 ', 'chaine', 0, '', '2013-08-24 08:03:49'),
(423, 'MAIN_INFO_APE', 1, '6420Z', 'chaine', 0, '', '2013-08-24 08:03:49'),
(424, 'MAIN_INFO_RCS', 1, 'Nanterre B 412 056 152 ', 'chaine', 0, '', '2013-08-24 08:03:49'),
(461, 'MAIN_MODULE_SOCIETE', 1, '1', NULL, 0, NULL, '2013-08-27 23:11:18'),
(231, 'SOCIETE_CODECLIENT_ADDON', 1, 'mod_codeclient_elephant', 'chaine', 0, '', '2013-08-21 17:03:52'),
(165, 'SOCIETE_CODECOMPTA_ADDON', 1, 'mod_codecompta_aquarium', 'chaine', 0, '', '2013-07-25 23:03:30'),
(475, 'MAIN_SEARCHFORM_SOCIETE', 1, '1', 'chaine', 0, '', '2013-09-25 08:14:46'),
(474, 'MAIN_SEARCHFORM_CONTACT', 1, '1', 'chaine', 0, '', '2013-09-25 08:14:46'),
(68, 'COMPANY_ADDON_PDF_ODT_PATH', 1, 'DOL_DATA_ROOT/doctemplates/thirdparties', 'chaine', 0, NULL, '2013-07-24 15:57:18'),
(449, 'MAIN_MODULE_PROPALE', 1, '1', NULL, 0, NULL, '2013-08-27 23:11:17'),
(70, 'PROPALE_ADDON_PDF', 1, 'azur', 'chaine', 0, 'Nom du gestionnaire de generation des propales en PDF', '2013-07-24 15:57:20'),
(71, 'PROPALE_ADDON', 1, 'mod_propale_marbre', 'chaine', 0, 'Nom du gestionnaire de numerotation des propales', '2013-07-24 15:57:20'),
(72, 'PROPALE_VALIDITY_DURATION', 1, '15', 'chaine', 0, 'Duration of validity of business proposals', '2013-07-24 15:57:20'),
(73, 'PROPALE_ADDON_PDF_ODT_PATH', 1, 'DOL_DATA_ROOT/doctemplates/proposals', 'chaine', 0, NULL, '2013-07-24 15:57:20'),
(462, 'MAIN_MODULE_SERVICE', 1, '1', NULL, 0, NULL, '2013-08-27 23:11:18'),
(457, 'MAIN_MODULE_FACTURE', 1, '1', NULL, 0, NULL, '2013-08-27 23:11:18'),
(77, 'FACTURE_ADDON_PDF', 1, 'crabe', 'chaine', 0, 'Name of PDF model of invoice', '2013-07-24 15:57:33'),
(78, 'FACTURE_ADDON', 1, 'mod_facture_terre', 'chaine', 0, 'Name of numbering numerotation rules of invoice', '2013-07-24 15:57:33'),
(79, 'FACTURE_ADDON_PDF_ODT_PATH', 1, 'DOL_DATA_ROOT/doctemplates/invoices', 'chaine', 0, NULL, '2013-07-24 15:57:33'),
(453, 'MAIN_MODULE_COMPTABILITE', 1, '1', NULL, 0, NULL, '2013-08-27 23:11:17'),
(459, 'MAIN_MODULE_BANQUE', 1, '1', NULL, 0, NULL, '2013-08-27 23:11:18'),
(444, 'MAIN_MODULE_AGEFODD_TABS_2', 1, 'propal:+tabAgefodd:AgfMenuSess:agefodd@agefodd:/agefodd/session/list_fin.php?search_propalid=__ID__', 'chaine', 0, NULL, '2013-08-27 23:11:17'),
(89, 'AGF_USE_STAGIAIRE_TYPE', 1, '', 'yesno', 0, 'Use trainee type', '2013-07-25 23:02:41'),
(90, 'AGF_DEFAULT_STAGIAIRE_TYPE', 1, '2', 'chaine', 0, 'Type of  trainee funding', '2013-07-25 23:02:41'),
(91, 'AGF_UNIVERSAL_MASK', 1, '', 'chaine', 0, 'Mask of training number ref', '2013-07-25 23:02:41'),
(92, 'AGF_ADDON', 1, 'mod_agefodd_simple', 'chaine', 0, 'Use simple mask for training ref', '2013-07-25 23:02:41'),
(93, 'AGF_ORGANISME_PREF', 1, '', 'chaine', 0, 'Prefecture d''enregistrement', '2013-07-25 23:02:41'),
(94, 'AGF_ORGANISME_NUM', 1, '', 'chaine', 0, 'Numerot d''enregistrement a la prefecture', '2013-07-25 23:02:41'),
(95, 'AGF_ORGANISME_REPRESENTANT', 1, '', 'chaine', 0, 'Representant de la societé de formation', '2013-07-25 23:02:41'),
(157, 'AGF_TRAINING_USE_SEARCH_TO_SELECT', 1, '1', 'chaine', 0, '', '2013-07-25 23:03:00'),
(158, 'AGF_TRAINER_USE_SEARCH_TO_SELECT', 1, '1', 'chaine', 0, '', '2013-07-25 23:03:00'),
(159, 'AGF_TRAINEE_USE_SEARCH_TO_SELECT', 1, '1', 'chaine', 0, '', '2013-07-25 23:03:01'),
(160, 'AGF_SITE_USE_SEARCH_TO_SELECT', 1, '1', 'chaine', 0, '', '2013-07-25 23:03:01'),
(100, 'AGF_STAGTYPE_USE_SEARCH_TO_SELECT', 1, '', 'yesno', 0, 'Search stagiaire type with combobox', '2013-07-25 23:02:41'),
(166, 'AGF_CONTACT_USE_SEARCH_TO_SELECT', 1, '1', 'chaine', 0, '', '2013-07-25 23:05:13'),
(156, 'AGF_CONTACT_DOL_SESSION', 1, '1', 'chaine', 0, '', '2013-07-25 23:02:57'),
(441, 'MAIN_MODULE_AGEFODD', 1, '1', NULL, 0, NULL, '2013-08-27 23:11:17'),
(442, 'MAIN_MODULE_AGEFODD_TABS_0', 1, 'order:+tabAgefodd:AgfMenuSess:agefodd@agefodd:/agefodd/session/list_fin.php?search_orderid=__ID__', 'chaine', 0, NULL, '2013-08-27 23:11:17'),
(161, 'AGF_DOL_AGENDA', 1, '1', 'chaine', 0, '', '2013-07-25 23:03:02'),
(155, 'AGF_USE_FAC_WITHOUT_ORDER', 1, '1', 'chaine', 0, '', '2013-07-25 23:02:52'),
(106, 'AGF_LINK_OPCA_ADRR_TO_CONTACT', 1, '', 'yesno', 0, 'Display OPCA adress from OPCA contact rather than OPCA', '2013-07-25 23:02:41'),
(107, 'AGF_TEXT_COLOR', 1, '000000', 'chaine', 0, 'Text color of PDF in hexadecimal', '2013-07-25 23:02:41'),
(108, 'AGF_HEAD_COLOR', 1, 'CB4619', 'chaine', 0, 'Text color header in hexadecimal', '2013-07-25 23:02:41'),
(109, 'AGF_FOOT_COLOR', 1, 'BEBEBE', 'chaine', 0, 'Text color of PDF footer, in hexadccimal', '2013-07-25 23:02:41'),
(433, 'MAIN_MODULE_CATEGORYCONTACT', 1, '1', NULL, 0, NULL, '2013-08-26 13:49:42'),
(168, 'AGF_FCKEDITOR_ENABLE_TRAINING', 1, '1', 'chaine', 0, '', '2013-07-25 23:05:19'),
(112, 'AGF_MANAGE_OPCA', 1, '1', 'yesno', 0, 'Manage Opca', '2013-07-25 23:02:41'),
(113, 'AGF_CERTIF_ADDON', 1, 'mod_agefoddcertif_simple', 'chaine', 0, 'Use simple mask for certif ref', '2013-07-25 23:02:41'),
(114, 'AGF_CERTIF_UNIVERSAL_MASK', 1, '', 'chaine', 0, 'Mask of certificate code', '2013-07-25 23:02:41'),
(169, 'AGF_SESSION_TRAINEE_STATUS_AUTO', 1, '1', 'chaine', 0, '', '2013-07-25 23:05:24'),
(272, 'MAIN_MODULE_EXPORT', 1, '1', NULL, 0, NULL, '2013-08-23 18:13:17'),
(120, 'COMMANDE_ADDON_PDF', 1, 'einstein', 'chaine', 0, 'Name of PDF model of order', '2013-07-25 23:02:41'),
(121, 'COMMANDE_ADDON', 1, 'mod_commande_marbre', 'chaine', 0, 'Name of numbering numerotation rules of order', '2013-07-25 23:02:41'),
(122, 'COMMANDE_ADDON_PDF_ODT_PATH', 1, 'DOL_DATA_ROOT/doctemplates/orders', 'chaine', 0, NULL, '2013-07-25 23:02:41'),
(460, 'MAIN_MODULE_FOURNISSEUR', 1, '1', NULL, 0, NULL, '2013-08-27 23:11:18'),
(132, 'COMMANDE_SUPPLIER_ADDON_PDF', 1, 'muscadet', 'chaine', 0, 'Nom du gestionnaire de generation des bons de commande en PDF', '2013-07-25 23:02:41'),
(133, 'COMMANDE_SUPPLIER_ADDON_NUMBER', 1, 'mod_commande_fournisseur_muguet', 'chaine', 0, 'Nom du gestionnaire de numerotation des commandes fournisseur', '2013-07-25 23:02:41'),
(134, 'INVOICE_SUPPLIER_ADDON_PDF', 1, 'canelle', 'chaine', 0, 'Nom du gestionnaire de generation des factures fournisseur en PDF', '2013-07-25 23:02:41'),
(135, 'INVOICE_SUPPLIER_ADDON_NUMBER', 1, 'mod_facture_fournisseur_cactus', 'chaine', 0, 'Nom du gestionnaire de numerotation des factures fournisseur', '2013-07-25 23:02:41'),
(463, 'MAIN_MODULE_AGENDA', 1, '1', NULL, 0, NULL, '2013-08-27 23:11:18'),
(139, 'MAIN_AGENDA_ACTIONAUTO_COMPANY_CREATE', 1, '1', 'chaine', 0, NULL, '2013-07-25 23:02:41'),
(140, 'MAIN_AGENDA_ACTIONAUTO_CONTRACT_VALIDATE', 1, '1', 'chaine', 0, NULL, '2013-07-25 23:02:41'),
(141, 'MAIN_AGENDA_ACTIONAUTO_PROPAL_VALIDATE', 1, '1', 'chaine', 0, NULL, '2013-07-25 23:02:41'),
(142, 'MAIN_AGENDA_ACTIONAUTO_PROPAL_SENTBYMAIL', 1, '1', 'chaine', 0, NULL, '2013-07-25 23:02:41'),
(143, 'MAIN_AGENDA_ACTIONAUTO_ORDER_VALIDATE', 1, '1', 'chaine', 0, NULL, '2013-07-25 23:02:41'),
(144, 'MAIN_AGENDA_ACTIONAUTO_ORDER_SENTBYMAIL', 1, '1', 'chaine', 0, NULL, '2013-07-25 23:02:41'),
(145, 'MAIN_AGENDA_ACTIONAUTO_BILL_VALIDATE', 1, '1', 'chaine', 0, NULL, '2013-07-25 23:02:41'),
(146, 'MAIN_AGENDA_ACTIONAUTO_BILL_PAYED', 1, '1', 'chaine', 0, NULL, '2013-07-25 23:02:41'),
(147, 'MAIN_AGENDA_ACTIONAUTO_BILL_CANCEL', 1, '1', 'chaine', 0, NULL, '2013-07-25 23:02:41'),
(148, 'MAIN_AGENDA_ACTIONAUTO_BILL_SENTBYMAIL', 1, '1', 'chaine', 0, NULL, '2013-07-25 23:02:41'),
(149, 'MAIN_AGENDA_ACTIONAUTO_ORDER_SUPPLIER_VALIDATE', 1, '1', 'chaine', 0, NULL, '2013-07-25 23:02:41'),
(150, 'MAIN_AGENDA_ACTIONAUTO_BILL_SUPPLIER_VALIDATE', 1, '1', 'chaine', 0, NULL, '2013-07-25 23:02:41'),
(151, 'MAIN_AGENDA_ACTIONAUTO_SHIPPING_VALIDATE', 1, '1', 'chaine', 0, NULL, '2013-07-25 23:02:41'),
(152, 'MAIN_AGENDA_ACTIONAUTO_SHIPPING_SENTBYMAIL', 1, '1', 'chaine', 0, NULL, '2013-07-25 23:02:41'),
(153, 'MAIN_AGENDA_ACTIONAUTO_BILL_UNVALIDATE', 1, '1', 'chaine', 0, NULL, '2013-07-25 23:02:41'),
(154, 'AGF_USE_LOGO_CLIENT', 1, '1', 'chaine', 0, '', '2013-07-25 23:02:50'),
(162, 'COMPANY_USE_SEARCH_TO_SELECT', 1, '2', 'chaine', 0, '', '2013-07-25 23:03:24'),
(163, 'CONTACT_USE_SEARCH_TO_SELECT', 1, '2', 'chaine', 0, '', '2013-07-25 23:03:26'),
(167, 'MAIN_USE_COMPANY_NAME_OF_CONTACT', 1, '1', 'chaine', 0, '', '2013-07-25 23:05:15'),
(443, 'MAIN_MODULE_AGEFODD_TABS_1', 1, 'invoice:+tabAgefodd:AgfMenuSess:agefodd@agefodd:/agefodd/session/list_fin.php?search_invoiceid=__ID__', 'chaine', 0, NULL, '2013-08-27 23:11:17'),
(466, 'MAIN_MULTILANGS', 1, '0', 'chaine', 0, '', '2013-09-25 08:14:46'),
(467, 'MAIN_SIZE_LISTE_LIMIT', 1, '50', 'chaine', 0, '', '2013-09-25 08:14:46'),
(468, 'MAIN_DISABLE_JAVASCRIPT', 1, '0', 'chaine', 0, '', '2013-09-25 08:14:46'),
(469, 'MAIN_BUTTON_HIDE_UNAUTHORIZED', 1, '1', 'chaine', 0, '', '2013-09-25 08:14:46'),
(470, 'MAIN_START_WEEK', 1, '1', 'chaine', 0, '', '2013-09-25 08:14:46'),
(471, 'MAIN_SHOW_LOGO', 1, '0', 'chaine', 0, '', '2013-09-25 08:14:46'),
(472, 'MAIN_FIRSTNAME_NAME_POSITION', 1, '0', 'chaine', 0, '', '2013-09-25 08:14:46'),
(473, 'MAIN_THEME', 1, 'bureau2crea', 'chaine', 0, '', '2013-09-25 08:14:46'),
(476, 'MAIN_SEARCHFORM_PRODUITSERVICE', 1, '0', 'chaine', 0, '', '2013-09-25 08:14:46'),
(477, 'MAIN_SEARCHFORM_ADHERENT', 1, '0', 'chaine', 0, '', '2013-09-25 08:14:46'),
(478, 'MAIN_HELPCENTER_DISABLELINK', 0, '0', 'chaine', 0, '', '2013-09-25 08:14:46'),
(479, 'MAIN_HELP_DISABLELINK', 0, '0', 'chaine', 0, '', '2013-09-25 08:14:46'),
(480, 'MAIN_USE_PREVIEW_TABS', 1, '0', 'chaine', 0, '', '2013-09-25 08:14:46'),
(428, 'COMPANY_ELEPHANT_MASK_CUSTOMER', 1, 'ACC{00000}', 'chaine', 0, '', '2013-08-24 08:04:39'),
(429, 'COMPANY_ELEPHANT_MASK_SUPPLIER', 1, 'ACC{00000}', 'chaine', 0, '', '2013-08-24 08:04:39'),
(249, 'PROJECT_ADDON_PDF', 1, 'baleine', 'chaine', 0, 'Nom du gestionnaire de generation des projets en PDF', '2013-08-23 16:32:47'),
(250, 'PROJECT_ADDON', 1, 'mod_project_simple', 'chaine', 0, 'Nom du gestionnaire de numerotation des projets', '2013-08-23 16:32:47'),
(251, 'PROJECT_ADDON_PDF_ODT_PATH', 1, 'DOL_DATA_ROOT/doctemplates/projects', 'chaine', 0, NULL, '2013-08-23 16:32:47'),
(268, 'MAIN_MODULE_CATEGORIE', 1, '1', NULL, 0, NULL, '2013-08-23 16:49:22'),
(269, 'PRODUIT_MULTIPRICES', 1, '1', 'chaine', 0, '', '2013-08-23 17:43:31'),
(464, 'PRODUIT_MULTIPRICES_LIMIT', 1, '3', 'chaine', 0, '', '2013-08-31 22:54:58'),
(273, 'MAIN_MODULE_MAILING', 1, '1', NULL, 0, NULL, '2013-08-23 18:27:23'),
(274, 'MAILING_EMAIL_UNSUBSCRIBE', 1, '1', 'chaine', 0, '', '2013-08-23 18:37:46'),
(277, 'MAIN_MODULE_FCKEDITOR', 1, '1', NULL, 0, NULL, '2013-08-23 18:38:53'),
(278, 'FCKEDITOR_ENABLE_SOCIETE', 1, '1', 'yesno', 0, 'WYSIWIG for description and note (except products/services)', '2013-08-23 18:38:53'),
(279, 'FCKEDITOR_ENABLE_PRODUCTDESC', 1, '1', 'yesno', 0, 'WYSIWIG for products/services description and note', '2013-08-23 18:38:53'),
(280, 'FCKEDITOR_ENABLE_MAILING', 1, '1', 'yesno', 0, 'WYSIWIG for mass emailings', '2013-08-23 18:38:53'),
(421, 'MAIN_INFO_SIREN', 1, '412056152', 'chaine', 0, '', '2013-08-24 08:03:49'),
(282, 'FCKEDITOR_ENABLE_USERSIGN', 1, '1', 'yesno', 0, 'WYSIWIG for products details lines for all entities', '2013-08-23 18:38:53'),
(283, 'FCKEDITOR_ENABLE_MAIL', 1, '1', 'yesno', 0, 'WYSIWIG for products details lines for all entities', '2013-08-23 18:38:53'),
(425, 'MAIN_INFO_TVAINTRA', 1, 'FR80412056152', 'chaine', 0, '', '2013-08-24 08:03:49'),
(431, 'MAIN_MODULE_HOLIDAY', 1, '1', NULL, 0, NULL, '2013-08-25 18:20:39'),
(432, 'MAIN_MODULE_HOLIDAY_TABS_0', 1, 'user:+paidholidays:CPTitreMenu:holiday:$user->rights->holiday->write:/holiday/index.php?mainmenu=holiday&id=__ID__', 'chaine', 0, NULL, '2013-08-25 18:20:39'),
(434, 'MAIN_MODULE_CATEGORYCONTACT_TABS_0', 1, 'contact:+tabCategorie:Category:categories:/categorycontact/categorycontact/categorie.php?id=__ID__&type=4', 'chaine', 0, NULL, '2013-08-26 13:49:42'),
(435, 'MAIN_MODULE_CATEGORYCONTACT_MODELS', 1, '1', 'chaine', 0, NULL, '2013-08-26 13:49:42'),
(437, 'MAIN_MENU_HIDE_UNAUTHORIZED', 1, '1', 'chaine', 1, '', '2013-08-26 17:09:10'),
(438, 'AGENDA_USE_EVENT_TYPE', 1, '1', 'chaine', 0, '', '2013-08-26 17:24:44'),
(439, 'MAIN_USE_ADVANCED_PERMS', 1, '1', 'chaine', 1, '', '2013-08-27 13:31:08'),
(440, 'SOCIETE_ADD_REF_IN_LIST', 1, '1', 'yesno', 0, '', '2013-08-27 23:06:27'),
(445, 'MAIN_MODULE_AGEFODD_TRIGGERS', 1, '1', 'chaine', 0, NULL, '2013-08-27 23:11:17'),
(446, 'AGF_LAST_VERION_INSTALL', 1, '2.1.3', 'chaine', 0, 'Last version installed to know change table to execute', '2013-08-27 23:11:17'),
(447, 'AGF_MANAGE_CERTIF', 1, '', 'yesno', 0, 'Manage certification', '2013-08-27 23:11:17'),
(451, 'MAIN_MODULE_COMMANDE', 1, '1', NULL, 0, NULL, '2013-08-27 23:11:17');







UPDATE account SET modreg='CHQ' WHERE modreg='CH';
UPDATE account SET modreg='CHQ' WHERE modreg='CHG';
UPDATE account SET modreg='VIR' WHERE modreg='VRG';

UPDATE account SET tvaintracom= REPLACE(tvaintracom,'TVA Intracommunautaire : ','');
UPDATE account SET tvaintracom= REPLACE(tvaintracom,'TVA Intracommunautaire: ','');

UPDATE account SET pays=1 WHERE pays='FRANCE';
UPDATE account SET pays=5 WHERE pays='ALLEMAGNE';
UPDATE account SET pays=5 WHERE pays='GERMANY';
UPDATE account SET pays=184 WHERE pays='POLOGNE';
UPDATE account SET pays=3 WHERE pays='ITALIE';
UPDATE account SET pays=12 WHERE pays='MAROC';
UPDATE account SET pays=6 WHERE pays='SUISSE';
UPDATE account SET pays=7 WHERE pays='GRANDE BRETAGNE';
UPDATE account SET pays=7 WHERE pays='UNITED KINGDOM';
UPDATE account SET pays=115 WHERE pays='HONG KONG';
UPDATE account SET pays=85 WHERE pays='EGYPTE';
UPDATE account SET pays=201 WHERE pays='SLOVAQUIE';
UPDATE account SET pays=11 WHERE pays='USA';
UPDATE account SET pays=20 WHERE pays IN ('SWEDEN','SUEDE');
UPDATE account SET pays=17 WHERE pays='PAYS BAS';
UPDATE account SET pays=2 WHERE pays='BELGIQUE';
UPDATE account SET pays=123 WHERE pays='JAPAN';
UPDATE account SET pays=4 WHERE pays='ESPAGNE';
UPDATE account SET pays=9 WHERE pays='CHINE';
UPDATE account SET pays=1 WHERE pays='MARTINIQUE';
UPDATE account SET pays=14 WHERE pays='CANADA';
UPDATE account SET pays=11 WHERE pays='ETATS UNIS';
UPDATE account SET pays=227 WHERE pays='UNITED ARAB EMIRATES';
UPDATE account SET pays=188 WHERE pays='ROUMANIE';
UPDATE account SET pays=29 WHERE pays='SINGAPOUR';
UPDATE account SET pays=221 WHERE pays='TUIRQUIE';
UPDATE account SET pays=13 WHERE pays='ALGERIE';
UPDATE account SET pays=117 WHERE pays='INDE';
UPDATE account SET pays=17 WHERE pays='PAYS-BAS';
UPDATE account SET pays=7 WHERE pays='ENGLAND';
UPDATE account SET pays=7 WHERE pays='GRANDE-BRETAGNE';
UPDATE account SET pays=7 WHERE pays='ANGLETERRE';
UPDATE account SET pays=1 WHERE pays='FRA?CE';
UPDATE account SET pays=1 WHERE pays='FRABNCE';
UPDATE account SET pays=17 WHERE pays='PAYS - BAS';
UPDATE account SET pays=227 WHERE pays='EMIRATS ARABES UNIS';
UPDATE account SET pays=227 WHERE pays='EMIRATS ARABE UNIS';
UPDATE account SET pays=2 WHERE pays='BELGIUM';
UPDATE account SET pays=2 WHERE pays='BELGIQUE BELGIE';
UPDATE account SET pays=11 WHERE pays='ETATS UNIS USA';
UPDATE account SET pays=17 WHERE pays='HOLLAND';
UPDATE account SET pays=7 WHERE pays IN ('ROYAUME UNI','ROYAUME-UNI');
UPDATE account SET pays=6 WHERE pays='SWITZERLAND';
UPDATE account SET pays=80 WHERE pays='DANEMARK';
UPDATE account SET pays=140 WHERE pays='LUXEMBOURG';
UPDATE account SET pays=35 WHERE pays='ANGOLA';
UPDATE account SET pays=81 WHERE pays='DJIBOUTI';
UPDATE account SET pays=27 WHERE pays='MONACO';
UPDATE account SET pays=28 WHERE pays='AUSTRALIE';
UPDATE account SET pays=56 WHERE pays='BRESIL';
UPDATE account SET pays=143 WHERE pays='MADAGASCAR';
UPDATE account SET pays=25 WHERE pays='PORTUGAL';
UPDATE account SET pays=22 WHERE pays='SENEGAL';
UPDATE account SET pays=10 WHERE pays='TUNISIE';
UPDATE account SET pays=79 WHERE pays IN ('CZECH REPUBLIC','CSECH REPUBLIC','REPUBLIQUE TCHEQUE');
UPDATE account SET pays=221 WHERE pays='TURQUIE';
UPDATE account SET pays=41 WHERE pays='AUTRICHE';
UPDATE account SET pays=21 WHERE pays='Côte d''Ivoire';
UPDATE account SET pays=24 WHERE pays='CAMEROUN';
UPDATE account SET pays=17 WHERE pays='THE NETHERLANDS';
UPDATE account SET pays=1 WHERE pays='ANGERS CEDEX 01';
UPDATE account SET pays=29 WHERE pays='SINGAPORE';
UPDATE account SET pays=117 WHERE pays='INDIA';
UPDATE account SET pays=3 WHERE pays IN ('ITALY','ITALIA');
UPDATE account SET pays=94 WHERE pays='FINLANDE';
UPDATE account SET pays=80 WHERE pays='DENMARK (DANEMARK)';
UPDATE account SET pays=1 WHERE pays='FRANCE Cedex 01';
UPDATE account SET pays=102 WHERE pays='GREECE';
UPDATE account SET pays=102 WHERE pays='GRECE';
UPDATE account SET pays=19 WHERE pays IN ('RUSSIA','RUSSIE');
UPDATE account SET pays=216 WHERE pays='THAILANDE';
UPDATE account SET pays=124 WHERE pays='JORDANIE';
UPDATE account SET pays=134 WHERE pays='LIBAN';
UPDATE account SET pays=173 WHERE pays='NORWAY';
UPDATE account SET pays=129 WHERE pays='COREE DU SUD';
UPDATE account SET pays=44 WHERE pays='BAHREIN';
UPDATE account SET pays=18 WHERE pays='HONGRIE';
UPDATE account SET pays=78 WHERE pays='CYPRUS';
UPDATE account SET pays=165 WHERE pays='NOUVELLE CALEDONIE';
UPDATE account SET pays=NULL WHERE pays='';

UPDATE interv SET pays=1 WHERE pays='FRANCE';
UPDATE interv SET pays=5 WHERE pays='ALLEMAGNE';
UPDATE interv SET pays=5 WHERE pays='GERMANY';
UPDATE interv SET pays=184 WHERE pays='POLOGNE';
UPDATE interv SET pays=3 WHERE pays='ITALIE';
UPDATE interv SET pays=12 WHERE pays='MAROC';
UPDATE interv SET pays=6 WHERE pays='SUISSE';
UPDATE interv SET pays=7 WHERE pays='GRANDE BRETAGNE';
UPDATE interv SET pays=7 WHERE pays='UNITED KINGDOM';
UPDATE interv SET pays=115 WHERE pays IN ('HONG KONG','HONG-KONG');
UPDATE interv SET pays=85 WHERE pays='EGYPTE';
UPDATE interv SET pays=201 WHERE pays='SLOVAQUIE';
UPDATE interv SET pays=11 WHERE pays='USA';
UPDATE interv SET pays=20 WHERE pays IN ('SWEDEN','SUEDE');
UPDATE interv SET pays=17 WHERE pays='PAYS BAS';
UPDATE interv SET pays=2 WHERE pays='BELGIQUE';
UPDATE interv SET pays=123 WHERE pays='JAPAN';
UPDATE interv SET pays=4 WHERE pays='ESPAGNE';
UPDATE interv SET pays=9 WHERE pays='CHINE';
UPDATE interv SET pays=1 WHERE pays='MARTINIQUE';
UPDATE interv SET pays=14 WHERE pays='CANADA';
UPDATE interv SET pays=11 WHERE pays='ETATS UNIS';
UPDATE interv SET pays=227 WHERE pays='UNITED ARAB EMIRATES';
UPDATE interv SET pays=188 WHERE pays='ROUMANIE';
UPDATE interv SET pays=29 WHERE pays='SINGAPOUR';
UPDATE interv SET pays=221 WHERE pays='TUIRQUIE';
UPDATE interv SET pays=13 WHERE pays='ALGERIE';
UPDATE interv SET pays=117 WHERE pays='INDE';
UPDATE interv SET pays=17 WHERE pays='PAYS-BAS';
UPDATE interv SET pays=7 WHERE pays='ENGLAND';
UPDATE interv SET pays=7 WHERE pays='GRANDE-BRETAGNE';
UPDATE interv SET pays=7 WHERE pays='ANGLETERRE';
UPDATE interv SET pays=1 WHERE pays='FRA?CE';
UPDATE interv SET pays=1 WHERE pays='FRABNCE';
UPDATE interv SET pays=17 WHERE pays='PAYS - BAS';
UPDATE interv SET pays=227 WHERE pays='EMIRATS ARABES UNIS';
UPDATE interv SET pays=227 WHERE pays='EMIRATS ARABE UNIS';
UPDATE interv SET pays=2 WHERE pays='BELGIUM';
UPDATE interv SET pays=2 WHERE pays='BELGIQUE BELGIE';
UPDATE interv SET pays=11 WHERE pays='ETATS UNIS USA';
UPDATE interv SET pays=17 WHERE pays='HOLLAND';
UPDATE interv SET pays=7 WHERE pays IN ('ROYAUME UNI','ROYAUME-UNI','United  Kingdom','UK');
UPDATE interv SET pays=6 WHERE pays='SWITZERLAND';
UPDATE interv SET pays=80 WHERE pays='DANEMARK';
UPDATE interv SET pays=140 WHERE pays='LUXEMBOURG';
UPDATE interv SET pays=35 WHERE pays='ANGOLA';
UPDATE interv SET pays=81 WHERE pays='DJIBOUTI';
UPDATE interv SET pays=27 WHERE pays='MONACO';
UPDATE interv SET pays=28 WHERE pays='AUSTRALIE';
UPDATE interv SET pays=56 WHERE pays='BRESIL';
UPDATE interv SET pays=143 WHERE pays='MADAGASCAR';
UPDATE interv SET pays=25 WHERE pays='PORTUGAL';
UPDATE interv SET pays=22 WHERE pays='SENEGAL';
UPDATE interv SET pays=10 WHERE pays='TUNISIE';
UPDATE interv SET pays=79 WHERE pays IN ('CZECH REPUBLIC','CSECH REPUBLIC','REPUBLIQUE TCHEQUE');
UPDATE interv SET pays=221 WHERE pays='TURQUIE';
UPDATE interv SET pays=41 WHERE pays='AUTRICHE';
UPDATE interv SET pays=21 WHERE pays='Côte d''Ivoire';
UPDATE interv SET pays=24 WHERE pays='CAMEROUN';
UPDATE interv SET pays=17 WHERE pays='THE NETHERLANDS';
UPDATE interv SET pays=1 WHERE pays='ANGERS CEDEX 01';
UPDATE interv SET pays=29 WHERE pays='SINGAPORE';
UPDATE interv SET pays=117 WHERE pays='INDIA';
UPDATE interv SET pays=3 WHERE pays IN ('ITALY','ITALIA');
UPDATE interv SET pays=94 WHERE pays='FINLANDE';
UPDATE interv SET pays=80 WHERE pays='DENMARK (DANEMARK)';
UPDATE interv SET pays=1 WHERE pays='FRANCE Cedex 01';
UPDATE interv SET pays=102 WHERE pays='GREECE';
UPDATE interv SET pays=102 WHERE pays='GRECE';
UPDATE interv SET pays=19 WHERE pays IN ('RUSSIA','RUSSIE');
UPDATE interv SET pays=216 WHERE pays='THAILANDE';
UPDATE interv SET pays=124 WHERE pays='JORDANIE';
UPDATE interv SET pays=134 WHERE pays='LIBAN';
UPDATE interv SET pays=173 WHERE pays IN ('NORWAY','NORVEGE');
UPDATE interv SET pays=129 WHERE pays='COREE DU SUD';
UPDATE interv SET pays=44 WHERE pays='BAHREIN';
UPDATE interv SET pays=18 WHERE pays='HONGRIE';
UPDATE interv SET pays=78 WHERE pays='CYPRUS';
UPDATE interv SET pays=165 WHERE pays='NOUVELLE CALEDONIE';
UPDATE interv SET pays=NULL WHERE pays='';
UPDATE interv SET pays=121 WHERE pays='ISRAEL';
UPDATE interv SET pays=123 WHERE pays='JAPON';
UPDATE interv SET pays=213 WHERE pays='TAIWAN';
UPDATE interv SET pays=118 WHERE pays='INDONESIE';
UPDATE interv SET pays=154 WHERE pays='MEXIQUE';
UPDATE interv SET pays=166 WHERE pays='NOUVELLE ZELANDE';


UPDATE contact SET titre='MR' WHERE titre='M.';
UPDATE contact SET titre='MME' WHERE titre='Mme';
UPDATE contact SET titre='MLE' WHERE titre='Mlle';

UPDATE interv SET civilite='MR' WHERE civilite='M.';
UPDATE interv SET civilite='MME' WHERE civilite='Mme';
UPDATE interv SET civilite='MLE' WHERE civilite='Mlle';

UPDATE eleves SET civilite='MR' WHERE civilite='M.';
UPDATE eleves SET civilite='MME' WHERE civilite='Mme';
UPDATE eleves SET civilite='MLE' WHERE civilite='Mlle';


--Affect Charle de rostand old user to new user
UPDATE account set com_id='dfbfc33c-039f-102c-b0fb-001aa0790251' WHERE com_id='dfbfc922-039f-102c-b0fb-001aa0790251';


INSERT INTO `llx_user` (`rowid`, `entity`, `ref_ext`, `ref_int`, `datec`, `tms`, `login`, `pass`, `pass_crypted`, `pass_temp`, `civilite`, `lastname`, `firstname`, `address`, `zip`, `town`, `fk_state`, `fk_country`, `job`, `office_phone`, `office_fax`, `user_mobile`, `email`, `signature`, `admin`, `module_comm`, `module_compta`, `fk_societe`, `fk_socpeople`, `fk_member`, `fk_user`, `note`, `datelastlogin`, `datepreviouslogin`, `egroupware_id`, `ldap_sid`, `openid`, `statut`, `photo`, `lang`, `color`) VALUES
(2, 1, NULL, NULL, '2011-05-01 09:34:56', '2013-08-27 15:31:40', 'cmigeot', 'test', NULL, NULL, NULL, 'MIGEOT', 'Caroline', '', '', '', NULL, NULL, 'Assistante', '01 55 95 85 17', '01 55 95 85 11', '', 'cmigeot@akteos.fr', '', 0, 0, 0, NULL, NULL, NULL, 11, '', '2013-09-25 08:24:31', '2013-09-02 07:49:50', NULL, NULL, NULL, 1, NULL, NULL, NULL),
(3, 1, NULL, NULL, '2009-08-07 13:25:20', '2013-08-27 15:59:03', 'jandrian', 'test', NULL, NULL, NULL, 'ANDRIAN', 'Janieva', '', '', '', NULL, NULL, 'Attaché commercial', '01 55 95 84 69', '01 55 95 85 11', '', 'jandrian@akteos.fr', '', 0, 0, 0, NULL, NULL, NULL, 8, '', '2013-09-21 10:29:04', '2013-08-24 21:23:35', NULL, NULL, NULL, 1, NULL, NULL, NULL),
(4, 1, NULL, NULL, '2010-05-06 17:57:52', '2013-08-27 15:56:06', 'ldarrieux', 'test', NULL, NULL, NULL, 'DARRIEUX', 'Laurence', '', '', '', NULL, NULL, 'Chef de projet', '01 55 95 84 66', '01 55 95 85 11', '', 'ldarrieux@akteos.fr', '', 0, 0, 0, NULL, NULL, NULL, 8, '', '2013-09-02 07:43:18', NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL),
(5, 1, NULL, NULL, '2013-02-07 17:32:57', '2013-08-27 15:56:43', 'mclement', 'test', NULL, NULL, NULL, 'CLEMENT', 'Mehdi', '', '', '', NULL, NULL, 'Attaché commercial', '01 55 95 84 65', '', '', 'mclement@akteos.fr', '', 0, 0, 0, NULL, NULL, NULL, 8, '', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL),
(6, 1, NULL, NULL, '2012-03-01 11:55:18', '2013-08-27 15:58:16', 'dnguyen', 'test', NULL, NULL, NULL, 'NGUYEN', 'David', '', '', '', NULL, NULL, 'Attaché commercial', '01 55 95 84 69', '01 55 95 85 11', '', 'dnguyen@akteos.fr', '', 0, 0, 0, NULL, NULL, NULL, 8, '', '2013-08-21 17:02:14', NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL),
(8, 1, NULL, NULL, NULL, '2013-08-27 15:32:54', 'crostand', 'test', NULL, NULL, NULL, 'ROSTAND', 'Charles', '', '', '', NULL, NULL, 'Directeur Général', '01 55 95 85 10', '01 55 95 85 11', '06 80 26 18 81', 'crostand@akteos.fr', 'Bien cordialement<br />\r\n<br />\r\nCharles Rostand<br />\r\nDirecteur G&eacute;n&eacute;ral<br />\r\n<br />', 1, 0, 0, NULL, NULL, NULL, NULL, '', '2013-08-29 08:17:11', NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL),
(10, 1, NULL, NULL, NULL, '2013-08-27 15:56:25', 'lrostand', 'test', NULL, NULL, NULL, 'ROSTAND', 'Laure', '', '', '', NULL, NULL, 'Présidente', '01 55 95 85 15', '01 55 95 85 16', '06 60 34 31 10', 'lrostand@akteos.fr', 'Bien cordialement<br />\r\n<br />\r\nLaure Rostand<br />\r\nPr&eacute;sidente<br />\r\n<br />\r\n01 55 95 85 15<br />\r\n06 60 34 31 10', 1, 0, 0, NULL, NULL, NULL, NULL, '', '2013-09-27 17:49:40', '2013-09-27 16:01:38', NULL, NULL, NULL, 1, NULL, NULL, NULL),
(11, 1, NULL, NULL, NULL, '2013-08-27 15:57:03', 'phabourdin', 'test', NULL, NULL, NULL, 'HABOURDIN', 'Pascale', '', '', '', NULL, NULL, 'Chef de Projet', '01 55 95 85 12', '01 55 95 85 11', '', 'phabourdin@akteos.fr', '', 0, 0, 0, NULL, NULL, NULL, 8, '', '2013-09-25 08:23:42', '2013-09-25 08:11:29', NULL, NULL, NULL, 1, NULL, NULL, NULL),
(13, 1, NULL, NULL, '2013-08-24 08:50:48', '2013-08-27 15:55:17', 'hlefebvre', 'test', '098f6bcd4621d373cade4e832627b4f6', NULL, NULL, 'LEFEBVRE', 'Hélène', '', '', '', NULL, NULL, 'Assitante', '', '', '', '', '', 0, 1, 1, NULL, NULL, NULL, 4, '', NULL, NULL, NULL, '', NULL, 1, NULL, NULL, NULL),
(14, 1, NULL, NULL, '2013-08-24 08:52:11', '2013-08-27 15:57:34', 'cmontaud', 'test', '098f6bcd4621d373cade4e832627b4f6', NULL, NULL, 'MONTAUD', 'Claire', '', '', '', NULL, NULL, 'Assistante', '', '', '', '', '', 0, 1, 1, NULL, NULL, NULL, 8, '', '2013-09-02 08:11:24', '2013-09-02 07:45:33', NULL, '', NULL, 1, NULL, NULL, NULL),
(15, 1, NULL, NULL, '2013-08-24 08:53:33', '2013-08-27 15:57:56', 'ctesson', 'test', '098f6bcd4621d373cade4e832627b4f6', NULL, NULL, 'TESSON', 'Cécile', '', '', '', NULL, NULL, 'Chargée de communication', '', '', '', '', '', 0, 1, 1, NULL, NULL, NULL, 10, '', '2013-09-02 07:42:19', '2013-09-01 23:00:32', NULL, '', NULL, 1, NULL, NULL, NULL);

UPDATE llx_user as doluser, sf_user as stuser SET  doluser.import_key=stuser.id where doluser.email=stuser.email_address;

--Insert inactive user
INSERT INTO llx_user (
entity,
ref_ext,
ref_int,
datec,
tms,
login,
pass,
pass_crypted,
pass_temp,
civilite,
lastname,
firstname,
address,
zip,
town,
fk_state,
fk_country,
job,
office_phone,
office_fax,
user_mobile,
email,
signature,
admin,
module_comm,
module_compta,
fk_societe,
fk_socpeople,
fk_member,
fk_user,
note,
datelastlogin,
datepreviouslogin,
egroupware_id,
ldap_sid,
openid,
statut,
photo,
lang,
color,
import_key) 
SELECT DISTINCT
1,--entity,
sf_user.external_ref, --ref_ext,
NULL, --ref_int,
sf_user.created, --datec,
sf_user.modified, --tms,
sf_user.login, --login,
'test', --pass,
NULL,  --pass_crypted,
NULL, --pass_temp,
NULL, --civilite,
sf_user.lastname, --lastname,
sf_user.firstname,--firstname,
NULL, --address,
NULL, --zip,
NULL, --town,
NULL, --fk_state,
NULL, --fk_country,
NULL, --job,
NULL, --office_phone,
NULL, --office_fax,
NULL, --user_mobile,
TRIM(sf_user.email_address), --email,
NULL, --signature,
0, --admin,
0, --module_comm,
0, --module_compta,
NULL, --fk_societe,
NULL, --fk_socpeople,
NULL, --fk_member,
NULL, --fk_user,
NULL, --note,
NULL, --datelastlogin,
NULL, --datepreviouslogin,
NULL, --egroupware_id,
NULL, --ldap_sid,
NULL, --openid,
0, --statut,
NULL, --photo,
NULL, --lang,
NULL, --color,
sf_user.id -- import_key
FROM sf_user 
WHERE sf_user.state='disabled';



--Insert customer typed account into thridparty
SET foreign_key_checks = 0;
TRUNCATE TABLE  llx_societe;
INSERT INTO llx_societe(nom, 
entity, 
ref_ext, 
ref_int, 
statut, 
parent, 
tms, 
datec, 
datea, 
status, 
code_client, 
code_fournisseur, 
code_compta, 
code_compta_fournisseur, 
address, 
zip, 
town, 
fk_departement, 
fk_pays, 
phone, 
fax, 
url, 
email, 
fk_effectif, 
fk_typent, 
fk_forme_juridique, 
fk_currency, 
siren, 
siret, 
ape, 
idprof4, 
idprof5, 
idprof6, 
tva_intra, 
capital, 
fk_stcomm, 
note_private, 
note_public, 
prefix_comm, 
client, 
fournisseur, 
supplier_account, 
fk_prospectlevel, 
customer_bad, 
customer_rate, 
supplier_rate,
 fk_user_creat, 
fk_user_modif, 
remise_client, 
mode_reglement, 
cond_reglement, 
tva_assuj, 
localtax1_assuj, 
localtax2_assuj, 
barcode, 
fk_barcode_type, 
price_level, 
default_lang, 
logo, 
canvas, 
import_key)
SELECT DISTINCT act.nom,
	1, 
	act.accountcode,
	act.leadcode, 
	0,
	NULL,
	act.modified,
	act.created,
	act.created,
	act.disabled,
	act.accountcode,
	NULL,
	LEFT(leg.piece,3),
NULL,
CONCAT_WS(' ',act.adresse, act.adresse2),
act.codepostal,
act.ville,
NULL,
act.pays,
act.tel,
act.fax,
act.siteweb,
TRIM(act.email),
NULL,
0,
NULL,
0,
NULL,
act.siret,
ref_sect.secteur,
NULL,
NULL, 
NULL,
LEFT(act.tvaintracom,20),
NULL,
0,
act.remarque,
NULL,
NULL,
act.client,
0,
NULL,
NULL,
0,
0, 
0,
IFNULL(usercrea.rowid,1), 
IFNULL(usermod.rowid,1), 
0,
modpay.id,
payterm.rowid,
act.tva,
NULL,
NULL,
NULL,
0,
NULL,
NULL,
NULL,
NULL,
act.id
FROM  thirdparty as so 
INNER JOIN account as act ON so.account_id=act.id AND so.type='account'
LEFT OUTER JOIN legacy_mvt as leg ON so.id=leg.thirdparty_id
LEFT OUTER JOIN ref_sect ON ref_sect.code=act.secteur
LEFT OUTER JOIN llx_c_paiement as modpay ON modpay.code = act.modreg
LEFT OUTER JOIN llx_c_payment_term as payterm ON payterm.nbjour = act.jourreg AND payterm.active=1
LEFT OUTER JOIN llx_user as usercrea ON act.created_by_sf_user_id=usercrea.import_key
LEFT OUTER JOIN llx_user as usermod ON act.modified_by_sf_user_id=usermod.import_key
WHERE act.type NOT IN ('Z99','IND','JOU','REL','FOU');

--Import supplier
INSERT INTO llx_societe(nom, 
entity, 
ref_ext, 
ref_int, 
statut, 
parent, 
tms, 
datec, 
datea, 
status, 
code_client, 
code_fournisseur, 
code_compta, 
code_compta_fournisseur, 
address, 
zip, 
town, 
fk_departement, 
fk_pays, 
phone, 
fax, 
url, 
email, 
fk_effectif, 
fk_typent, 
fk_forme_juridique, 
fk_currency, 
siren, 
siret, 
ape, 
idprof4, 
idprof5, 
idprof6, 
tva_intra, 
capital, 
fk_stcomm, 
note_private, 
note_public, 
prefix_comm, 
client, 
fournisseur, 
supplier_account, 
fk_prospectlevel, 
customer_bad, 
customer_rate, 
supplier_rate,
 fk_user_creat, 
fk_user_modif, 
remise_client, 
mode_reglement, 
cond_reglement, 
tva_assuj, 
localtax1_assuj, 
localtax2_assuj, 
barcode, 
fk_barcode_type, 
price_level, 
default_lang, 
logo, 
canvas, 
import_key)
SELECT DISTINCT act.nom,
	1, 
	act.accountcode,
	act.leadcode, 
	0,
	NULL,
	act.modified,
	act.created,
	act.created,
	act.disabled,
	act.accountcode,
	NULL,
	LEFT(leg.piece,3),
NULL,
CONCAT_WS(' ',act.adresse, act.adresse2),
act.codepostal,
act.ville,
NULL,
act.pays,
act.tel,
act.fax,
act.siteweb,
TRIM(act.email),
NULL,
0,
NULL,
0,
NULL,
act.siret,
ref_sect.secteur,
NULL,
NULL, 
NULL,
LEFT(act.tvaintracom,20),
NULL,
0,
act.remarque,
NULL,
NULL,
0,
1,
NULL,
NULL,
0,
0, 
0,
IFNULL(usercrea.rowid,1), 
IFNULL(usermod.rowid,1), 
0,
modpay.id,
payterm.rowid,
act.tva,
NULL,
NULL,
NULL,
0,
NULL,
NULL,
NULL,
NULL,
act.id
FROM  thirdparty as so 
INNER JOIN account as act ON so.account_id=act.id AND so.type='account'
LEFT OUTER JOIN legacy_mvt as leg ON so.id=leg.thirdparty_id
LEFT OUTER JOIN ref_sect ON ref_sect.code=act.secteur
LEFT OUTER JOIN llx_c_paiement as modpay ON modpay.code = act.modreg
LEFT OUTER JOIN llx_c_payment_term as payterm ON payterm.nbjour = act.jourreg AND payterm.active=1
LEFT OUTER JOIN llx_user as usercrea ON act.created_by_sf_user_id=usercrea.import_key
LEFT OUTER JOIN llx_user as usermod ON act.modified_by_sf_user_id=usermod.import_key
WHERE act.type='FOU';

--Insert prospect
INSERT INTO llx_societe(nom, 
entity, 
ref_ext, 
ref_int, 
statut, 
parent, 
tms, 
datec, 
datea, 
status, 
code_client, 
code_fournisseur, 
code_compta, 
code_compta_fournisseur, 
address, 
zip, 
town, 
fk_departement, 
fk_pays, 
phone, 
fax, 
url, 
email, 
fk_effectif, 
fk_typent, 
fk_forme_juridique, 
fk_currency, 
siren, 
siret, 
ape, 
idprof4, 
idprof5, 
idprof6, 
tva_intra, 
capital, 
fk_stcomm, 
note_private, 
note_public, 
prefix_comm, 
client, 
fournisseur, 
supplier_account, 
fk_prospectlevel, 
customer_bad, 
customer_rate, 
supplier_rate,
 fk_user_creat, 
fk_user_modif, 
remise_client, 
mode_reglement, 
cond_reglement, 
tva_assuj, 
localtax1_assuj, 
localtax2_assuj, 
barcode, 
fk_barcode_type, 
price_level, 
default_lang, 
logo, 
canvas, 
import_key)
SELECT DISTINCT act.nom,
	1, 
	act.accountcode,
	act.leadcode, 
	0,
	NULL,
	act.modified,
	act.created,
	act.created,
	act.disabled,
	act.accountcode,
	NULL,
	NULL,
NULL,
CONCAT_WS(' ',act.adresse, act.adresse2),
act.codepostal,
act.ville,
NULL,
act.pays,
act.tel,
act.fax,
act.siteweb,
TRIM(act.email),
NULL,
0,
NULL,
0,
NULL,
act.siret,
ref_sect.secteur,
NULL,
NULL, 
NULL,
LEFT(act.tvaintracom,20),
NULL,
0,
act.remarque,
NULL,
NULL,
2,
0,
NULL,
NULL,
0,
0, 
0,
IFNULL(usercrea.rowid,1), 
IFNULL(usermod.rowid,1), 
0,
modpay.id,
payterm.rowid,
act.tva,
NULL,
NULL,
NULL,
0,
NULL,
NULL,
NULL,
NULL,
act.id
FROM  account as act 
LEFT OUTER JOIN ref_sect ON ref_sect.code=act.secteur
LEFT OUTER JOIN llx_c_paiement as modpay ON modpay.code = act.modreg
LEFT OUTER JOIN llx_c_payment_term as payterm ON payterm.nbjour = act.jourreg AND payterm.active=1
LEFT OUTER JOIN llx_user as usercrea ON act.created_by_sf_user_id=usercrea.import_key
LEFT OUTER JOIN llx_user as usermod ON act.modified_by_sf_user_id=usermod.import_key
WHERE act.id NOT IN (SELECT account_id from thirdparty WHERE account_id IS NOT NULL )
AND act.type NOT IN ('Z99','IND','JOU','REL','FOU');


UPDATE llx_societe SET status=2,tms=tms WHERE status=0;
UPDATE llx_societe SET status=0,tms=tms WHERE status=1;
UPDATE llx_societe SET status=1,tms=tms WHERE status=2; 

TRUNCATE TABLE llx_socpeople;

--Insert Contact of thirdparty
INSERT INTO llx_socpeople (datec,
tms,
fk_soc,
entity,
ref_ext,
civilite,
lastname,
firstname,
address,
zip,
town,
fk_departement,
fk_pays,
birthday,
poste,
phone,
phone_perso,
phone_mobile,
fax,
email,
jabberid,
no_email,
priv,
fk_user_creat,
fk_user_modif,
note_private,
note_public,
default_lang,
canvas,
import_key) 
SELECT 
contact.created, 
contact.modified, 
soc.rowid, 
1,
contact.external_ref,
civ.code,
contact.nom, 
contact.prenom,
CONCAT_WS(' ',contact.adresse, contact.adresse2),
contact.codepostal,
contact.ville,
NULL,
soc.fk_pays,
NULL,
fonc.libelle,
contact.tel,
NULL,
contact.portable,
contact.fax,
TRIM(contact.email),
NULL,
0,
0,
IFNULL(usercrea.rowid,1), 
IFNULL(usermod.rowid,1), 
contact.remarque,
NULL,
NULL,
NULL,
contact.id  
FROM contact
INNER JOIN account ON contact.account_id = account.id
INNER JOIN llx_societe as soc ON soc.import_key=account.id
LEFT OUTER JOIN llx_c_civilite as civ ON civ.code=contact.titre
LEFT OUTER JOIN ref_fonc as fonc ON fonc.fonction=contact.fonction
LEFT OUTER JOIN llx_user as usercrea ON contact.created_by_sf_user_id=usercrea.import_key
LEFT OUTER JOIN llx_user as usermod ON contact.modified_by_sf_user_id=usermod.import_key;


--update commercial on customer
TRUNCATE TABLE  llx_societe_commerciaux;

INSERT INTO llx_societe_commerciaux (fk_soc,fk_user)
SELECT soc.rowid,usr.rowid
FROM llx_societe as soc 
INNER JOIN account ON soc.import_key=account.id
INNER JOIN com ON com.id=account.com_id
INNER JOIN llx_user as usr ON com.email=usr.email;

--Update Mother company
UPDATE llx_societe as soc,account as child, account as parent,llx_societe as socparent 
SET soc.parent=socparent.rowid,
soc.tms=soc.tms
WHERE soc.import_key=child.id
AND child.groupe_account_id=parent.id
AND socparent.import_key=parent.id
AND child.groupe_account_id<>child.id;

--Create asktoes as thirdparty to import place/room
INSERT INTO llx_societe (nom,entity,tms,datec,datea,status,address,zip,town,fk_pays,client,fournisseur,import_key)
VALUES ('Akteos',1,NOW(),NOW(),NOW(),1,'6 rue du quatre septembre','92130','ISSY LES MOULINEAUX',1,0,0,'akteos');

--import place into agefodd
TRUNCATE TABLE llx_agefodd_place;
INSERT INTO llx_agefodd_place (
ref_interne,
adresse,
cp,
ville,
fk_pays,
tel,
fk_societe,
notes,
acces_site,
note1,
archive,
fk_reg_interieur,
fk_user_author,
datec,
fk_user_mod,
tms,
entity,
import_key) 
SELECT 
CONCAT_WS('-',room.code, room.adr1), --ref_interne,
room.adr2, --adresse,
room.cp, --cp
room.ville,
1, --fk_pays,
NULL, --tel,
soc.rowid, --fk_societe,
NULL, --notes,
room.adr3, --acces_site,
NULL, --note1,
0, --archive,
NULL, --fk_reg_interieur,
IFNULL(usercrea.rowid,1), --fk_user_author
NOW(), --datec,
IFNULL(usermod.rowid,1),  --fk_user_mod,
room.modified,--tms,
1, --entity
room.id -- importkey
FROM (room
, llx_societe as soc)
LEFT OUTER JOIN llx_user as usercrea ON room.created_by_sf_user_id=usercrea.import_key
LEFT OUTER JOIN llx_user as usermod ON room.modified_by_sf_user_id=usermod.import_key
WHERE soc.import_key='akteos'
AND room.code NOT LIKE 'Z9999%';

---Vérification manuel si +sieur matires par stage
/*
SELECT stage_id from trainingprogramdiscipline 
GROUP BY stage_id HAVING count(stage_id)>1

DELETE fROM trainingprogramdiscipline WHERE stage_id='5d9ef8bb-a906-45d6-a39e-7cbc0c546a07' AND matiere='02MIC'
*/

--import analytics training category
INSERT INTO llx_agefodd_formation_catalogue_type (code,intitule,sort,active,tms)
SELECT DISTINCT analyt.code,
analyt.intitule,
0,
CASE WHEN (analyt.intitule='NE PAS UTILISER') THEN 0 ELSE 1 END,
NOW()
FROM stage 
INNER JOIN analyt ON stage.analyt=analyt.code;

--import training catalogue
TRUNCATE TABLE llx_agefodd_formation_catalogue;
INSERT INTO llx_agefodd_formation_catalogue (
ref,
ref_interne,
entity,
intitule,
duree,
public,
methode,
prerequis,
but,
programme,
note1,
note2,
archive,
fk_user_author,
datec,
fk_user_mod,
note_private,
note_public,
fk_product,
nb_subscribe_min,
tms,
import_key,
fk_c_category) 
SELECT
stage.numstage,--ref
stage.numstage, --ref_interne
1, --entity
CASE WHEN (TRIM(stage.intlong)='') THEN stage.intitule ELSE TRIM(REPLACE(stage.intlong,'Formation :','')) END ,--intitule
IFNULL(stage.nbhr,0), --duree
typcours.intitule, --public
NULL, --methode
NULL, --prerequis
matiere.intitule, --but
NULL, --programme,
matiere.memo, --note1,
NULL, --note2,
0, --archive,
IFNULL(usercrea.rowid,1), --fk_user_author
IFNULL(stage.created,NOW()), --datec,
IFNULL(usermod.rowid,1),  --fk_user_mod,
NULL, --note_private,
NULL, --note_public,
NULL, --fk_product,
NULL, --nb_subscribe_min,
stage.modified, --tms
stage.id,
cattype.rowid
FROM stage 
INNER JOIN typcours ON typcours.code=stage.typcours
LEFT OUTER JOIN trainingprogramdiscipline as but ON but.stage_id=stage.id
LEFT OUTER JOIN matiere ON matiere.matiere=but.matiere
LEFT OUTER JOIN llx_user as usercrea ON stage.created_by_sf_user_id=usercrea.import_key
LEFT OUTER JOIN llx_user as usermod ON stage.modified_by_sf_user_id=usermod.import_key
LEFT OUTER JOIN llx_agefodd_formation_catalogue_type as cattype ON cattype.code=stage.analyt
WHERE typcours.intitule <> 'NE PAS UTILISER';

UPDATE llx_agefodd_formation_catalogue SET ref=CONCAT_WS('','FOR_', date_format(datec,'%y%m'),'-', LPAD(rowid,4,'0'));

INSERT INTO llx_agefodd_training_admlevel(fk_agefodd_training_admlevel,fk_training,level_rank,fk_parent_level,indice,intitule,delais_alerte,fk_user_author,datec,fk_user_mod) 
SELECT DISTINCT seesadm.rowid,training.rowid, seesadm.level_rank, seesadm.fk_parent_level,seesadm.indice, seesadm.intitule,seesadm.delais_alerte,seesadm.fk_user_author,seesadm.datec,seesadm.fk_user_mod 
FROM llx_agefodd_session_admlevel as seesadm, llx_agefodd_formation_catalogue as training;

UPDATE llx_agefodd_training_admlevel as ori, llx_agefodd_training_admlevel as upd SET upd.fk_parent_level=ori.rowid WHERE upd.fk_parent_level=ori.fk_agefodd_training_admlevel AND upd.level_rank<>0 AND upd.fk_training=ori.fk_training;

--Insert domaine extrafield
INSERT INTO llx_agefodd_formation_catalogue_extrafields(fk_object, cd_domaine)
SELECT llx_agefodd_formation_catalogue.rowid, 
stage.domaine
FROM llx_agefodd_formation_catalogue INNER JOIN stage ON llx_agefodd_formation_catalogue.import_key=stage.id;



--import bank
TRUNCATE TABLE llx_bank_account;
INSERT INTO llx_bank_account (rowid,datec,tms,ref,label,entity,bank,code_banque,code_guichet,number,cle_rib,bic,iban_prefix,country_iban,cle_iban,domiciliation,state_id,fk_pays,proprio,owner_address,courant,clos,rappro,url,account_number,currency_code,min_allowed,min_desired,comment) VALUES (1,{ts '2013-08-13 21:46:03.'},{ts '2013-08-13 21:46:39.'},'CIC','CIC',1,'CIC','30066','10021','00010327101','','','',null,null,'',null,1,'','',1,0,1,null,'','EUR',0,0,'');

/*
--import product
TRUNCATE TABLE llx_product;
INSERT INTO llx_product (
ref,
entity,
ref_ext,
datec,
tms,
virtual,
fk_parent,
label,
description,
note,
customcode,
fk_country,
price,
price_ttc,
price_min,
price_min_ttc,
price_base_type,
tva_tx,
recuperableonly,
localtax1_tx,
localtax2_tx,
fk_user_author,
tosell,
tobuy,
fk_product_type,
duration,
seuil_stock_alerte,
barcode,
fk_barcode_type,
accountancy_code_sell,
accountancy_code_buy,
partnumber,
weight,
weight_units,
length,
length_units,
surface,
surface_units,
volume,
volume_units,
stock,
pmp,
canvas,
finished,
hidden,
import_key) 
SELECT 
produit.codprod, --ref,
1, --entity,
NULL, --ref_ext,
NOW(),
NOW(),
0, --virtual,
NULL, --fk_parent,
produit.intitule, --label,
NULL, --description,
NULL, --note,
NULL, --customcode,
NULL, --fk_country,
0, --price,
0, --price_ttc,
0, --price_min,
0, --price_min_ttc,
'TTC', --price_base_type,
19.6, --tva_tx,
0, --recuperableonly,
0, --localtax1_tx,
0, --localtax2_tx,
1, --fk_user_author,
1, --tosell,
0, --tobuy,
1, --fk_product_type,
NULL, --duration,
NULL, --seuil_stock_alerte,
NULL, --barcode,
NULL, --fk_barcode_type,
produit.compte, --accountancy_code_sell,
NULL, --accountancy_code_buy,
NULL, --partnumber,
NULL, --weight,
NULL, --weight_units,
NULL, --length,
NULL, --length_units,
NULL, --surface,
NULL, --surface_units,
NULL, --volume,
NULL, --volume_units,
NULL, --stock,
0, --pmp,
NULL, --canvas,
0, --finished,
0, --hidden,
NULL --import_key
FROM produit;

--Update product id into training
UPDATE llx_agefodd_formation_catalogue as cat, llx_product as prod , stage SET cat.fk_product=prod.rowid
WHERE stage.specific_product=prod.ref AND stage.numstage=cat.ref_interne;
*/

INSERT INTO `llx_product` (`rowid`, `ref`, `entity`, `ref_ext`, `datec`, `tms`, `virtual`, `fk_parent`, `label`, `description`, `note`, `customcode`, `fk_country`, `price`, `price_ttc`, `price_min`, `price_min_ttc`, `price_base_type`, `tva_tx`, `recuperableonly`, `localtax1_tx`, `localtax2_tx`, `fk_user_author`, `tosell`, `tobuy`, `fk_product_type`, `duration`, `seuil_stock_alerte`, `barcode`, `fk_barcode_type`, `accountancy_code_sell`, `accountancy_code_buy`, `partnumber`, `weight`, `weight_units`, `length`, `length_units`, `surface`, `surface_units`, `volume`, `volume_units`, `stock`, `pmp`, `canvas`, `finished`, `hidden`, `import_key`) VALUES
(1, 'A04', 1, NULL, '2013-08-14 17:12:40', '2013-09-01 19:20:43', 0, NULL, 'Formation interentreprises', '', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 1, 1, 1, 1, '2d', NULL, NULL, NULL, '706100', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL),
(2, 'A01', 1, NULL, '2013-08-14 17:12:40', '2013-09-27 16:02:15', 0, NULL, 'Formation intra-entreprise', '', '', '', NULL, 5000.00000000, 5980.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 1, 1, 1, 1, '2d', NULL, NULL, NULL, '706100', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL),
(3, 'A02', 1, NULL, '2013-08-14 17:12:40', '2013-09-01 12:58:56', 0, NULL, 'Atelier', '', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 1, 1, 1, 1, '3h', NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL),
(4, 'A03', 1, NULL, '2013-08-14 17:12:40', '2013-09-01 12:59:24', 0, NULL, 'Team Building', '', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 1, 1, 1, 1, '1d', NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL),
(5, 'F02', 1, NULL, '2013-08-14 17:12:40', '2013-09-01 19:09:16', 0, NULL, 'Coaching', '', '', '', NULL, 500.00000000, 598.00000000, 400.00000000, 478.40000000, 'HT', 19.600, 0, 0.000, 0.000, 1, 1, 1, 1, '2h', NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL),
(6, 'D01', 1, NULL, '2013-08-14 17:12:40', '2013-09-01 19:06:49', 0, NULL, 'Préparation', '', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 1, 1, 1, 1, '', NULL, NULL, NULL, '706100', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL),
(7, 'F01', 1, NULL, '2013-08-14 17:12:40', '2013-09-01 19:08:51', 0, NULL, 'Conférence', '', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 1, 1, 1, 1, '2h', NULL, NULL, NULL, '706200', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL),
(9, 'E01', 1, NULL, '2013-08-14 17:12:40', '2013-09-01 19:07:26', 0, NULL, 'Nomad''Online', '', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 1, 1, 1, 1, '', NULL, NULL, NULL, '707500', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL),
(10, 'F03', 1, NULL, '2013-08-14 17:12:40', '2013-09-01 19:09:35', 0, NULL, 'Majoration', '', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 1, 1, 1, 1, '', NULL, NULL, NULL, '706100', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL),
(12, 'E02', 1, NULL, '2013-08-14 17:12:40', '2013-09-01 19:07:44', 0, NULL, 'The International Profiler', '', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 1, 1, 1, 1, '', NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL),
(13, 'E05', 1, NULL, '2013-08-14 17:12:40', '2013-09-01 19:08:31', 0, NULL, 'Rich Media', '', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 1, 1, 1, 1, '', NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL),
(20, 'C02', 1, NULL, '2013-08-14 17:12:40', '2013-09-01 19:18:50', 0, NULL, 'Frais techniques', '', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 1, 1, 1, 1, '', NULL, NULL, NULL, '708700', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL),
(24, 'C01', 1, NULL, '2013-08-14 17:12:40', '2013-09-01 19:19:18', 0, NULL, 'Frais de mission', '', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 1, 1, 1, 1, '', NULL, NULL, NULL, '708701', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL),
(31, 'B01', 1, NULL, '2013-08-14 17:12:40', '2013-09-01 18:51:09', 0, NULL, 'Profil Nomad''', '', '', '', NULL, 50.00000000, 59.80000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 1, 1, 0, 1, '', NULL, NULL, NULL, '707500', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL),
(33, 'E03', 1, NULL, '2013-08-14 17:12:40', '2013-09-01 19:08:00', 0, NULL, 'LCP', '', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 1, 1, 1, 1, '', NULL, NULL, NULL, '707500', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL),
(34, 'D02', 1, NULL, '2013-08-14 17:12:40', '2013-09-01 19:31:17', 0, NULL, 'Développement spécifique', '', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 1, 1, 1, 1, '', NULL, NULL, NULL, '707500', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL),
(38, 'HT_A01', 1, NULL, '2013-08-14 17:12:40', '2013-09-20 21:50:29', 0, NULL, 'Formation', '', '', '', NULL, 5000.00000000, 5000.00000000, 0.00000000, 0.00000000, 'HT', 0.000, 0, 0.000, 0.000, 1, 1, 1, 1, '2d', NULL, NULL, NULL, '706110', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL),
(41, 'HT_C01', 1, NULL, '2013-08-14 17:12:40', '2013-09-20 21:56:34', 0, NULL, 'Frais de mission', '', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 0.000, 0, 0.000, 0.000, 1, 1, 1, 1, '', NULL, NULL, NULL, '707610', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL),
(46, 'E04', 1, NULL, '2013-08-14 17:12:40', '2013-09-01 19:08:15', 0, NULL, 'Webinar', '', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 1, 1, 1, 1, '', NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL),
(47, 'HT_F01', 1, NULL, '2013-08-14 17:12:40', '2013-09-20 21:56:58', 0, NULL, 'Conférence', '', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 0.000, 0, 0.000, 0.000, 1, 1, 1, 1, '2h', NULL, NULL, NULL, '706110', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL),
(49, 'HT_B01', 1, NULL, '2013-08-14 17:12:40', '2013-09-20 21:52:30', 0, NULL, 'Profil Nomad', '', '', '', NULL, 50.00000000, 50.00000000, 0.00000000, 0.00000000, 'TTC', 0.000, 0, 0.000, 0.000, 1, 1, 0, 1, '', NULL, NULL, NULL, '707500', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL),
(53, 'HT_F03', 1, NULL, '2013-08-14 17:12:40', '2013-09-20 21:57:30', 0, NULL, 'Majoration', '', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 0.000, 0, 0.000, 0.000, 1, 1, 1, 1, '', NULL, NULL, NULL, '708710', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, NULL, NULL, 0, NULL);

INSERT INTO `llx_product_fournisseur_price` (`rowid`, `entity`, `datec`, `tms`, `fk_product`, `fk_soc`, `ref_fourn`, `fk_availability`, `price`, `quantity`, `remise_percent`, `remise`, `unitprice`, `charges`, `unitcharges`, `tva_tx`, `info_bits`, `fk_user`, `import_key`) VALUES
(1, 1, '2013-08-31 23:17:09', '2013-08-31 23:17:09', 2, 5578, 'ABOU', 0, 1000.00000000, 1, 0, 0, 1000.00000000, 0.00000000, 0.00000000, 0.000, 0, 10, NULL);

INSERT INTO `llx_product_price` (`rowid`, `entity`, `tms`, `fk_product`, `date_price`, `price_level`, `price`, `price_ttc`, `price_min`, `price_min_ttc`, `price_base_type`, `tva_tx`, `recuperableonly`, `localtax1_tx`, `localtax2_tx`, `fk_user_author`, `tosell`, `price_by_qty`, `import_key`) VALUES
(1, 1, '2013-08-23 17:32:56', 2, '2013-08-23 17:32:56', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 1, 1, 0, NULL),
(2, 1, '2013-08-23 17:33:05', 2, '2013-08-23 17:33:05', 1, 1254.18060000, 1500.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 1, 1, 0, NULL),
(3, 1, '2013-08-23 18:23:33', 2, '2013-08-23 18:23:33', 1, 1500.00000000, 1500.00000000, 0.00000000, 0.00000000, 'TTC', 0.000, 0, 0.000, 0.000, 1, 1, 0, NULL),
(4, 1, '2013-08-25 00:27:52', 31, '2013-08-25 00:27:52', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(5, 1, '2013-08-25 00:28:35', 31, '2013-08-25 00:28:35', 1, 41.80602000, 50.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(6, 1, '2013-08-25 00:29:20', 31, '2013-08-25 00:29:20', 1, 50.00000000, 59.80000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(7, 1, '2013-08-25 22:27:00', 2, '2013-08-25 22:27:00', 1, 2500.00000000, 2990.00000000, 2000.00000000, 2392.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(8, 1, '2013-08-25 22:31:33', 2, '2013-08-25 22:31:33', 2, 2300.00000000, 2750.80000000, 1800.00000000, 2152.80000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(9, 1, '2013-08-25 22:32:24', 2, '2013-08-25 22:32:24', 1, 2500.00000000, 2990.00000000, 2200.00000000, 2631.20000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(10, 1, '2013-08-25 22:32:46', 2, '2013-08-25 22:32:46', 2, 2300.00000000, 2750.80000000, 1600.00000000, 1913.60000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(11, 1, '2013-08-25 22:42:25', 1, '2013-08-25 22:42:25', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(12, 1, '2013-08-25 22:45:33', 1, '2013-08-25 22:45:33', 1, 1490.00000000, 1782.04000000, 1450.00000000, 1734.20000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(13, 1, '2013-08-25 22:53:42', 3, '2013-08-25 22:53:42', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(14, 1, '2013-08-25 22:55:49', 5, '2013-08-25 22:55:49', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(15, 1, '2013-08-25 22:56:18', 5, '2013-08-25 22:56:18', 1, 500.00000000, 598.00000000, 400.00000000, 478.40000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(16, 1, '2013-08-25 23:07:03', 1, '2013-08-25 23:07:03', 2, 2300.00000000, 2750.80000000, 1600.00000000, 1913.60000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(17, 1, '2013-08-26 08:55:00', 1, '2013-08-26 08:55:00', 1, 1490.00000000, 1782.04000000, 1450.00000000, 1734.20000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(18, 1, '2013-08-26 08:55:20', 1, '2013-08-26 08:55:20', 2, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(19, 1, '2013-08-26 14:14:13', 12, '2013-08-26 14:14:13', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 1, 1, 0, NULL),
(20, 1, '2013-08-29 23:43:18', 7, '2013-08-29 23:43:18', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(30, 1, '2013-09-20 21:50:29', 38, '2013-09-20 21:50:29', 1, 5000.00000000, 5000.00000000, 0.00000000, 0.00000000, 'HT', 0.000, 0, 0.000, 0.000, 10, 1, 0, NULL),
(29, 1, '2013-09-20 21:49:40', 38, '2013-09-20 21:49:40', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(24, 1, '2013-08-31 08:11:40', 20, '2013-08-31 08:11:40', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(25, 1, '2013-08-31 17:53:40', 2, '2013-08-31 17:53:40', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(26, 1, '2013-08-31 17:53:55', 2, '2013-08-31 17:53:55', 2, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(27, 1, '2013-08-31 17:54:15', 2, '2013-08-31 17:54:15', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(28, 1, '2013-08-31 22:50:28', 2, '2013-08-31 22:50:28', 1, 5000.00000000, 5980.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(31, 1, '2013-09-20 21:52:17', 49, '2013-09-20 21:52:17', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(32, 1, '2013-09-20 21:52:30', 49, '2013-09-20 21:52:30', 1, 50.00000000, 50.00000000, 0.00000000, 0.00000000, 'TTC', 0.000, 0, 0.000, 0.000, 10, 1, 0, NULL),
(33, 1, '2013-09-20 21:56:25', 41, '2013-09-20 21:56:25', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(34, 1, '2013-09-20 21:56:34', 41, '2013-09-20 21:56:34', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 0.000, 0, 0.000, 0.000, 10, 1, 0, NULL),
(35, 1, '2013-09-20 21:56:49', 47, '2013-09-20 21:56:49', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(36, 1, '2013-09-20 21:56:58', 47, '2013-09-20 21:56:58', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 0.000, 0, 0.000, 0.000, 10, 1, 0, NULL),
(37, 1, '2013-09-20 21:57:22', 53, '2013-09-20 21:57:22', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(38, 1, '2013-09-20 21:57:30', 53, '2013-09-20 21:57:30', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 0.000, 0, 0.000, 0.000, 10, 1, 0, NULL),
(39, 1, '2013-10-20 11:33:25', 34, '2013-10-20 11:33:25', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 'TTC', 19.600, 0, 0.000, 0.000, 1, 1, 0, NULL);

--import session link with convention
TRUNCATE TABLE llx_agefodd_session;
INSERT INTO llx_agefodd_session (
entity,
fk_soc,
fk_formation_catalogue,
fk_session_place,
type_session,
nb_place,
nb_stagiaire,
force_nb_stagiaire,
nb_subscribe_min,
dated,
datef,
notes,
color,
cost_trainer,
cost_site,
cost_trip,
sell_price,
is_date_res_site,
date_res_site,
is_date_res_trainer,
date_res_trainer,
date_ask_OPCA,
is_date_ask_OPCA,
is_OPCA,
fk_soc_OPCA,
fk_socpeople_OPCA,
num_OPCA_soc,
num_OPCA_file,
fk_user_author,
datec,
fk_user_mod,
tms,
archive,
status,
import_key) 
SELECT 
1, --entity,
CASE WHEN (sess.inter=0) THEN soc.rowid ELSE NULL END, --fk_soc,
cat.rowid, --fk_formation_catalogue,
IFNULL(place.rowid,(SELECT rowid from llx_agefodd_place WHERE ref_interne='ENT-Dans l''entreprise')), --fk_session_place,
sess.inter, --type_session,
0, --nb_place,
0, --nb_stagiaire,
0, --force_nb_stagiaire,
sess.nbrmin, --nb_subscribe_min,
IFNULL(MIN(convct.datdeb),sess.datdeb), --dated,
IFNULL(MAX(convct.datfin),sess.datfin), --datef,
'', --notes,
NULL, --color,
SUM(coutconsultant.montant),  --cost_trainer,
SUM(coutsalle.montant), --cost_site,
SUM(couttrip.montant), --cost_trip,
sess.prxft, --sell_price,
0, --is_date_res_site,
NULL, --date_res_site,
0, --is_date_res_trainer,
NULL, --date_res_trainer,
NULL, --date_ask_OPCA,
0,--is_date_ask_OPCA,
0,--is_OPCA,
NULL, --fk_soc_OPCA,
NULL, --fk_socpeople_OPCA,
NULL, --num_OPCA_soc,
NULL, --num_OPCA_file,
IFNULL(usercrea.rowid,1), --fk_user_author
IFNULL(sess.created,NOW()), --datec,
IFNULL(usermod.rowid,1), --fk_user_author
sess.modified, --tms,
CASE WHEN IFNULL(MAX(convct.datfin),sess.datfin)<NOW() THEN 1 ELSE 0 END, --archive
4,--status CONVOQUE
sess.id --import_key
FROM session as sess
INNER JOIN stage ON stage.id=sess.stage_id
INNER JOIN llx_agefodd_formation_catalogue as cat ON cat.ref_interne=stage.numstage
LEFT OUTER JOIN llx_agefodd_place as place ON place.import_key=sess.room_id
LEFT OUTER JOIN sesfr as coutconsultant ON coutconsultant.session_id=sess.id AND coutconsultant.typfr='CON'
LEFT OUTER JOIN sesfr as coutsalle ON coutsalle.session_id=sess.id AND coutsalle.typfr='SAL'
LEFT OUTER JOIN sesfr as couttrip ON couttrip.session_id=sess.id AND couttrip.typfr='DEP'
INNER JOIN convct ON sess.id=convct.session_id
LEFT OUTER JOIN proct ON sess.id=proct.session_id 
LEFT OUTER JOIN account ON account.id=proct.account_id
LEFT OUTER JOIN llx_societe as soc ON soc.import_key=account.id
LEFT OUTER JOIN llx_user as usercrea ON sess.created_by_sf_user_id=usercrea.import_key
LEFT OUTER JOIN llx_user as usermod ON sess.modified_by_sf_user_id=usermod.import_key
GROUP BY sess.id;


--import session without convention
INSERT INTO llx_agefodd_session (
entity,
fk_soc,
fk_formation_catalogue,
fk_session_place,
type_session,
nb_place,
nb_stagiaire,
force_nb_stagiaire,
nb_subscribe_min,
dated,
datef,
notes,
color,
cost_trainer,
cost_site,
cost_trip,
sell_price,
is_date_res_site,
date_res_site,
is_date_res_trainer,
date_res_trainer,
date_ask_OPCA,
is_date_ask_OPCA,
is_OPCA,
fk_soc_OPCA,
fk_socpeople_OPCA,
num_OPCA_soc,
num_OPCA_file,
fk_user_author,
datec,
fk_user_mod,
tms,
archive,
import_key) 
SELECT 
1, --entity,
CASE WHEN (sess.inter=0) THEN soc.rowid ELSE NULL END, --fk_soc,
cat.rowid, --fk_formation_catalogue,
IFNULL(place.rowid,(SELECT rowid from llx_agefodd_place WHERE ref_interne='ENT-Dans l''entreprise')), --fk_session_place,
sess.inter, --type_session,
0, --nb_place,
0, --nb_stagiaire,
0, --force_nb_stagiaire,
sess.nbrmin, --nb_subscribe_min,
IFNULL(MIN(convct.datdeb),sess.datdeb), --dated,
IFNULL(MAX(convct.datfin),sess.datfin), --datef,
'', --notes,
NULL, --color,
SUM(coutconsultant.montant),  --cost_trainer,
SUM(coutsalle.montant), --cost_site,
SUM(couttrip.montant), --cost_trip,
sess.prxft, --sell_price,
0, --is_date_res_site,
NULL, --date_res_site,
0, --is_date_res_trainer,
NULL, --date_res_trainer,
NULL, --date_ask_OPCA,
0,--is_date_ask_OPCA,
0,--is_OPCA,
NULL, --fk_soc_OPCA,
NULL, --fk_socpeople_OPCA,
NULL, --num_OPCA_soc,
NULL, --num_OPCA_file,
IFNULL(usercrea.rowid,1), --fk_user_author
IFNULL(sess.created,NOW()), --datec,
IFNULL(usermod.rowid,1), --fk_user_author
sess.modified, --tms,
CASE WHEN IFNULL(MAX(convct.datfin),sess.datfin)<NOW() THEN 1 ELSE 0 END, --archive
sess.id --import_key
FROM session as sess
INNER JOIN stage ON stage.id=sess.stage_id
INNER JOIN llx_agefodd_formation_catalogue as cat ON cat.ref_interne=stage.numstage
LEFT OUTER JOIN llx_agefodd_place as place ON place.import_key=sess.room_id
LEFT OUTER JOIN sesfr as coutconsultant ON coutconsultant.session_id=sess.id AND coutconsultant.typfr='CON'
LEFT OUTER JOIN sesfr as coutsalle ON coutsalle.session_id=sess.id AND coutsalle.typfr='SAL'
LEFT OUTER JOIN sesfr as couttrip ON couttrip.session_id=sess.id AND couttrip.typfr='DEP'
LEFT OUTER JOIN convct ON sess.id=convct.session_id
LEFT OUTER JOIN proct ON sess.id=proct.session_id 
LEFT OUTER JOIN account ON account.id=proct.account_id
LEFT OUTER JOIN llx_societe as soc ON soc.import_key=account.id
LEFT OUTER JOIN llx_user as usercrea ON sess.created_by_sf_user_id=usercrea.import_key
LEFT OUTER JOIN llx_user as usermod ON sess.modified_by_sf_user_id=usermod.import_key
WHERE convct.id IS NULL AND YEAR(sess.datdeb)>2000
GROUP BY sess.id;

--Import trainer as supplier and contact
INSERT INTO llx_societe(nom, 
entity, 
ref_ext, 
ref_int, 
statut, 
parent, 
tms, 
datec, 
datea, 
status, 
code_client, 
code_fournisseur, 
code_compta, 
code_compta_fournisseur, 
address, 
zip, 
town, 
fk_departement, 
fk_pays, 
phone, 
fax, 
url, 
email, 
fk_effectif, 
fk_typent, 
fk_forme_juridique, 
fk_currency, 
siren, 
siret, 
ape, 
idprof4, 
idprof5, 
idprof6, 
tva_intra, 
capital, 
fk_stcomm, 
note_private, 
note_public, 
prefix_comm, 
client, 
fournisseur, 
supplier_account, 
fk_prospectlevel, 
customer_bad, 
customer_rate, 
supplier_rate,
 fk_user_creat, 
fk_user_modif, 
remise_client, 
mode_reglement, 
cond_reglement, 
tva_assuj, 
localtax1_assuj, 
localtax2_assuj, 
barcode, 
fk_barcode_type, 
price_level, 
default_lang, 
logo, 
canvas, 
import_key)
SELECT DISTINCT CONCAT_WS(' ',interv.nom, interv.prenom),
	1, 
	NULL,
	NULL, 
	0,
	NULL,
	interv.modified,
	interv.created,
	interv.created,
	interv.actif,
	NULL,
	NULL,
	NULL,
NULL,
CONCAT_WS(' ',interv.adr1, interv.commune),
interv.cp,
interv.bureau,
NULL,
interv.pays,
interv.telephone,
interv.fax,
NULL,
TRIM(interv.email),
NULL,
8,
NULL,
0,
NULL,
interv.siret,
interv.urssaf,
NULL,
NULL, 
NULL,
NULL,
NULL,
0,
interv.memo,
NULL,
NULL,
0,
1,
NULL,
NULL,
0,
0, 
0,
IFNULL(usercrea.rowid,1), --fk_user_author
IFNULL(usermod.rowid,1), --fk_user_mod
0,
NULL,
NULL,
NULL,
NULL,
NULL,
NULL,
0,
NULL,
NULL,
NULL,
NULL,
interv.id
FROM  interv
LEFT OUTER JOIN llx_user as usercrea ON interv.created_by_sf_user_id=usercrea.import_key
LEFT OUTER JOIN llx_user as usermod ON interv.modified_by_sf_user_id=usermod.import_key;

UPDATE llx_societe SET code_fournisseur=CONCAT_WS('','ACC', LPAD(rowid,5,'0')), tms=tms WHERE fournisseur=1;


INSERT INTO llx_socpeople (datec,
tms,
fk_soc,
entity,
ref_ext,
civilite,
lastname,
firstname,
address,
zip,
town,
fk_departement,
fk_pays,
birthday,
poste,
phone,
phone_perso,
phone_mobile,
fax,
email,
jabberid,
no_email,
priv,
fk_user_creat,
fk_user_modif,
note_private,
note_public,
default_lang,
canvas,
import_key) 
SELECT 
interv.created, 
interv.modified, 
soc.rowid, 
1,
interv.external_ref,
civ.code,
interv.nom, 
interv.prenom,
CONCAT_WS(' ',interv.adr1, interv.commune),
interv.cp,
interv.bureau,
NULL,
soc.fk_pays,
NULL,
NULL,
interv.telephone,
NULL,
interv.portable,
interv.fax,
TRIM(interv.email),
NULL,
0,
0,
IFNULL(usercrea.rowid,1), --fk_user_author
IFNULL(usermod.rowid,1), --fk_user_mod
CONCAT_WS(' ',interv.memo, interv.contrat),
NULL,
NULL,
NULL,
interv.id  
FROM interv
INNER JOIN llx_societe as soc ON soc.import_key=interv.id
LEFT OUTER JOIN llx_c_civilite as civ ON civ.code=interv.civilite
LEFT OUTER JOIN llx_user as usercrea ON interv.created_by_sf_user_id=usercrea.import_key
LEFT OUTER JOIN llx_user as usermod ON interv.modified_by_sf_user_id=usermod.import_key;

--Import trainer from dolibarr contact
TRUNCATE TABLE llx_agefodd_formateur;
INSERT INTO llx_agefodd_formateur (
entity,
fk_socpeople,
fk_user,
type_trainer,
archive,
fk_user_author,
datec,
fk_user_mod,
tms)
SELECT 
1, --entity,
llx_socpeople.rowid, --fk_socpeople,
NULL, --fk_user,
'socpeople', --type_trainer, 
interv.disabled, --archive,
IFNULL(usercrea.rowid,1), --fk_user_author
IFNULL(interv.created,NOW()), --datec,
IFNULL(usermod.rowid,1), --fk_user_mod
IFNULL(interv.modified,NOW()) --tms
FROM llx_socpeople 
INNER JOIN interv ON interv.id=llx_socpeople.import_key
LEFT OUTER JOIN llx_user as usercrea ON interv.created_by_sf_user_id=usercrea.import_key
LEFT OUTER JOIN llx_user as usermod ON interv.modified_by_sf_user_id=usermod.import_key;


--Import trainer into session
TRUNCATE TABLE llx_agefodd_session_formateur;
INSERT INTO llx_agefodd_session_formateur (
fk_session,
fk_agefodd_formateur,
fk_user_author,
datec,
fk_user_mod,
tms) 
SELECT DISTINCT
llx_agefodd_session.rowid,
llx_agefodd_formateur.rowid,
IFNULL(usercrea.rowid,1), --fk_user_author
IFNULL(pls.created,NOW()),
IFNULL(usermod.rowid,1), --fk_user_mod
IFNULL(pls.modified,NOW())
FROM pls 
INNER JOIN llx_agefodd_session ON llx_agefodd_session.import_key=pls.session_id
INNER JOIN llx_socpeople ON llx_socpeople.import_key=pls.interv_id
INNER JOIN llx_agefodd_formateur ON llx_agefodd_formateur.fk_socpeople=llx_socpeople.rowid
LEFT OUTER JOIN llx_user as usercrea ON pls.created_by_sf_user_id=usercrea.import_key
LEFT OUTER JOIN llx_user as usermod ON pls.modified_by_sf_user_id=usermod.import_key
WHERE pls.status='confirmed';


--import trainer times and cost into session
TRUNCATE TABLE llx_agefodd_session_formateur_calendrier;
INSERT INTO llx_agefodd_session_formateur_calendrier(
entity,
  fk_agefodd_session_formateur,
  date_session,
  heured,
  heuref,
  trainer_cost,
  fk_actioncomm,
  fk_user_author,
  datec,
  fk_user_mod,
  tms)
 SELECT 
 1,--entity,
 llx_agefodd_session_formateur.rowid, --fk_agefodd_session_formateur,
 pls.datec,--date_session,
 DATE_ADD(DATE(pls.datec),INTERVAL DATE_FORMAT(pls.hrdeb,'%H:%i') HOUR_MINUTE),--heured,
 DATE_ADD(DATE(pls.datec),INTERVAL DATE_FORMAT(pls.hrfin,'%H:%i') HOUR_MINUTE), --heuref,
 coutconsultant.montant/pls.nbhr,--trainer_cost,
 NULL,--fk_actioncomm,
 IFNULL(usercrea.rowid,1), --fk_user_author
 IFNULL(pls.created,NOW()),-- datec,
 IFNULL(usermod.rowid,1), --fk_user_mod
 IFNULL(pls.modified,NOW())--tms
 FROM pls 
INNER JOIN llx_agefodd_session ON llx_agefodd_session.import_key=pls.session_id
INNER JOIN llx_socpeople ON llx_socpeople.import_key=pls.interv_id
INNER JOIN llx_agefodd_formateur ON llx_agefodd_formateur.fk_socpeople=llx_socpeople.rowid
INNER JOIN llx_agefodd_session_formateur ON llx_agefodd_session.rowid=llx_agefodd_session_formateur.fk_session AND llx_agefodd_formateur.rowid=llx_agefodd_session_formateur.fk_agefodd_formateur
LEFT OUTER JOIN sesfr as coutconsultant ON coutconsultant.session_id=pls.session_id AND coutconsultant.typfr='CON' AND coutconsultant.interv_id=pls.interv_id
LEFT OUTER JOIN llx_user as usercrea ON pls.created_by_sf_user_id=usercrea.import_key
LEFT OUTER JOIN llx_user as usermod ON pls.modified_by_sf_user_id=usermod.import_key
WHERE pls.status='confirmed';

--import trainee
TRUNCATE TABLE llx_agefodd_stagiaire;
INSERT INTO llx_agefodd_stagiaire (
entity,
nom,
prenom,
civilite,
fk_user_author,
fk_user_mod,
datec,
tms,
fk_soc,
fk_socpeople,
fonction,
tel1,
tel2,
mail,
date_birth,
place_birth,
note,
import_key) 
SELECT DISTINCT
1, --entity,
TRIM(eleves.nom),
IFNULL(TRIM(eleves.prenom),''),
eleves.civilite,
 IFNULL(usercrea.rowid,1), --fk_user_author
 IFNULL(usermod.rowid,1), --fk_user_mod
IFNULL(eleves.created,NOW()), --datec,
eleves.modified, --tms,
IFNULL(soc.rowid,(SELECT rowid from llx_societe where nom='Akteos')), --fk_soc,
NULL, --fk_socpeople,
NULL, --fonction,
eleves.telephone, --tel1,
NULL,
eleves.email, --mail,
eleves.datnais, --date_birth,
NULL, --place_birth,
eleves.texte, --note,
eleves.id --import_key
FROM eleves 
INNER JOIN point ON eleves.id=point.eleves_id
INNER JOIN session as sess ON sess.id=point.session_id
LEFT OUTER JOIN llx_societe as soc ON soc.import_key=eleves.account_id
LEFT OUTER JOIN llx_user as usercrea ON eleves.created_by_sf_user_id=usercrea.import_key
LEFT OUTER JOIN llx_user as usermod ON eleves.modified_by_sf_user_id=usermod.import_key;

--Add trainee to session
TRUNCATE TABLE llx_agefodd_session_stagiaire;
INSERT INTO llx_agefodd_session_stagiaire (
fk_session_agefodd,
fk_stagiaire,
fk_agefodd_stagiaire_type,
status_in_session,
fk_user_author,
datec,
fk_user_mod,
tms,
import_key)
SELECT 
llx_agefodd_session.rowid,
llx_agefodd_stagiaire.rowid,
1,
3,
 IFNULL(usercrea.rowid,1), --fk_user_author
NOW(),
 IFNULL(usermod.rowid,1), --fk_user_mod
NOW(),
NULL
FROM eleves 
INNER JOIN point ON eleves.id=point.eleves_id
INNER JOIN session as sess ON sess.id=point.session_id
INNER JOIN llx_agefodd_session ON sess.id=llx_agefodd_session.import_key
INNER JOIN llx_agefodd_stagiaire ON llx_agefodd_stagiaire.import_key=point.eleves_id
LEFT OUTER JOIN llx_user as usercrea ON eleves.created_by_sf_user_id=usercrea.import_key
LEFT OUTER JOIN llx_user as usermod ON eleves.modified_by_sf_user_id=usermod.import_key;

--Update number of trainee per session
UPDATE llx_agefodd_session SET nb_stagiaire=(SELECT count(rowid) FROM llx_agefodd_session_stagiaire WHERE fk_session_agefodd = llx_agefodd_session.rowid), tms=tms WHERE (llx_agefodd_session.force_nb_stagiaire=0 OR llx_agefodd_session.force_nb_stagiaire IS NULL);

--Insert Session calendar
TRUNCATE TABLE  llx_agefodd_session_calendrier;
INSERT INTO llx_agefodd_session_calendrier (
fk_agefodd_session,
date_session,
heured,
heuref,
fk_actioncomm,
fk_user_author,
datec,
fk_user_mod,
tms)
SELECT 
agesess.rowid,
DATE(pls.datec),
DATE_ADD(DATE(pls.datec),INTERVAL DATE_FORMAT(pls.hrdeb,'%H:%i') HOUR_MINUTE),
DATE_ADD(DATE(pls.datec),INTERVAL DATE_FORMAT(pls.hrfin,'%H:%i') HOUR_MINUTE),
NULL,
1,
IFNULL(pls.created,NOW()),
1,
IFNULL(pls.modified,NOW())
FROM llx_agefodd_session as agesess
INNER JOIN pls ON agesess.import_key=pls.session_id;

--Insert Propal
TRUNCATE TABLE llx_propaldet;
TRUNCATE TABLE llx_propal;

INSERT INTO llx_propal (
ref,
entity,
ref_ext,
ref_int,
ref_client,
fk_soc,
fk_projet,
tms,
datec,
datep,
fin_validite,
date_valid,
date_cloture,
fk_user_author,
fk_user_valid,
fk_user_cloture,
fk_statut,
price,
remise_percent,
remise_absolue,
remise,
total_ht,
tva,
localtax1,
localtax2,
total,
fk_account,
fk_currency,
fk_cond_reglement,
fk_mode_reglement,
note_private,
note_public,
model_pdf,
date_livraison,
fk_availability,
fk_input_reason,
import_key,
extraparams,
fk_delivery_address) 
SELECT
proct.numct,  --ref,
1,  --entity,
NULL, --ref_ext,
NULL, --ref_int,
NULL, --ref_client,
soc.rowid,  --fk_soc,
NULL,  --fk_projet,
IFNULL(proct.modified,NOW()),  --tms,
IFNULL(proct.created,NOW()), --datec,
IFNULL(proct.created,NOW()),  --datep,
proct.dateps,  --fin_validite,
proct.dates,  --date_valid,
proct.dates,  --date_cloture,
 IFNULL(usercrea.rowid,1), --fk_user_author
1,    --fk_user_valid,
1,  --fk_user_cloture,
CASE WHEN proct.signe THEN 2 ELSE 3 END, --fk_statut,
0,  --price,
NULL,  --remise_percent,
NULL,  --remise_absolue,
0,  --remise,
0, --total_ht,
0,  --tva,
0,  --localtax1,
0,  --localtax2,
0,  --total,
NULL,  --fk_account,
NULL,  --fk_currency,
NULL,  --fk_cond_reglement,
NULL,  --fk_mode_reglement,
NULL,  --note_private,
NULL,  --note_public,
'azur',  --model_pdf,
NULL, --date_livraison,
0, --fk_availability,
0, --fk_input_reason,
proct.id, --import_key,
NULL, --extraparams,
NULL  --fk_delivery_address
FROM proct 
INNER JOIN llx_societe as soc ON soc.import_key=proct.account_id
INNER JOIN prolig ON prolig.proct_id=proct.id
LEFT OUTER JOIN llx_user as usercrea ON proct.created_by_sf_user_id=usercrea.import_key
GROUP BY proct.id;


--Insert Propal det
INSERT INTO llx_propaldet (
fk_propal,
fk_parent_line,
fk_product,
label,
description,
fk_remise_except,
tva_tx,
localtax1_tx,
localtax1_type,
localtax2_tx,
localtax2_type,
qty,
remise_percent,
remise,
price,
subprice,
total_ht,
total_tva,
total_localtax1,
total_localtax2,
total_ttc,
product_type,
date_start,
date_end,
info_bits,
buy_price_ht,
fk_product_fournisseur_price,
special_code,
rang) 
SELECT 
prop.rowid, --fk_propal,
NULL,  --fk_parent_line,
NULL,  --fk_product,
NULL,  --label,
prolig.intitule,  --description,
NULL,  --fk_remise_except,
CASE WHEN prolig.ctva=1 THEN 19.600 ELSE 0 END,  --tva_tx,
0,  --localtax1_tx,
0,  --localtax1_type,
0,  --localtax2_tx,
0,  --localtax2_type,
prolig.nbjr, --qty,
0,  --remise_percent,
0,  --remise,
prolig.taux,  --price,
prolig.taux,  --subprice,
CASE WHEN prolig.ctva=1 THEN prolig.mont-(prolig.mont*0.196) ELSE prolig.mont END,  --total_ht,
CASE WHEN prolig.ctva=1 THEN prolig.mont*0.196 ELSE 0 END,  --total_tva,
0,  --total_localtax1,
0,  --total_localtax2,
prolig.mont,  --total_ttc,
1,  --product_type,
NULL,  --date_start,
NULL,  --date_end,
0,  --info_bits,
0,  --buy_price_ht,
NULL,  --fk_product_fournisseur_price,
0,  --special_code,
prolig.numlig  --rang
FROM prolig INNER JOIN llx_propal as prop ON prop.import_key=prolig.proct_id;


--Add propal contact
TRUNCATE TABLE llx_element_contact;
INSERT INTO llx_element_contact (
datecreate,
statut,
element_id,
fk_c_type_contact,
fk_socpeople)
SELECT DISTINCT
proct.created,
4,
llx_propal.rowid,
40,
llx_socpeople.rowid
FROM proct
INNER JOIN llx_propal ON llx_propal.import_key=proct.id
INNER JOIN contact ON proct.contact_id=contact.id
INNER JOIN llx_socpeople ON  llx_socpeople.import_key=contact.id;


--Update propal total amount
UPDATE llx_propal 
SET llx_propal.total_ht=(SELECT SUM(total_ht) FROM llx_propaldet WHERE llx_propaldet.fk_propal=llx_propal.rowid GROUP BY llx_propaldet.fk_propal),
llx_propal.tva=(SELECT SUM(total_tva) FROM llx_propaldet WHERE llx_propaldet.fk_propal=llx_propal.rowid  GROUP BY llx_propaldet.fk_propal),
llx_propal.total=(SELECT SUM(total_ttc) FROM llx_propaldet WHERE llx_propaldet.fk_propal=llx_propal.rowid  GROUP BY llx_propaldet.fk_propal),
llx_propal.tms=llx_propal.tms;

--Lier propal Session/client
INSERT INTO llx_agefodd_facture(
fk_commande,
fk_facture,
fk_propal,
fk_session,
fk_societe,
fk_user_author,
datec,
fk_user_mod,
tms)
SELECT 
NULL,
NULL,
llx_propal.rowid,
llx_agefodd_session.rowid,
llx_societe.rowid,
1,
NOW(),
1,
NOW()
FROM llx_propal
INNER JOIN proct ON proct.id=llx_propal.import_key
INNER JOIN session as sess ON sess.id=proct.session_id
INNER JOIN llx_agefodd_session ON llx_agefodd_session.import_key=sess.id
INNER JOIN account ON account.id=proct.account_id
INNER JOIN llx_societe ON llx_societe.import_key=account.id;



--------------------------------------------------------
--------------------------------------------------------
--------------------------------------------------------
--------------------------------------------------------

--Import Invoice
TRUNCATE TABLE llx_facture;
TRUNCATE TABLE llx_facturedet;
INSERT INTO llx_facture (
facnumber,
entity,
ref_ext,
ref_int,
ref_client,
type,
increment,
fk_soc,datec,
datef,
date_valid,
tms,
paye,
amount,
remise_percent,
remise_absolue,
remise,
close_code,
close_note,
tva,
localtax1,
localtax2,
revenuestamp,
total,
total_ttc,
fk_statut,
fk_user_author,
fk_user_valid,
fk_facture_source,
fk_projet,
fk_account,
fk_currency,
fk_cond_reglement,
fk_mode_reglement,
date_lim_reglement,
note_private,
note_public,
model_pdf,
import_key,
extraparams)
SELECT 
lettrage.nopfact,  --facnumber
1,  --entity,
NULL,  --ref_ext,
NULL,  --ref_int,
NULL,  --ref_client,
0,  --type,
NULL,  --increment,
llx_societe.rowid,  --fk_soc,
IFNULL(lettrage.datefact,NOW()),  --datec,
IFNULL(lettrage.datefact,NOW()),  --datef,
IFNULL(lettrage.datefact,NOW()),  --date_valid,
NOW(),  --tms,
0,  --paye,
0,  --amount,
NULL,  --remise_percent,
NULL,  --remise_absolue,
0,  --remise,
NULL,  --close_code,
NULL,  --close_note,
0,  --tva,
0,  --localtax1,
0,  --localtax2,
0,  --revenuestamp,
0,  --total,
0,  --total_ttc,
2,  --fk_statut,
1,  --fk_user_author,
1,  --fk_user_valid,
NULL,  --fk_facture_source,
NULL,  --fk_projet,
NULL,  --fk_account,
NULL,  --fk_currency,
llx_societe.cond_reglement,  --fk_cond_reglement,
llx_societe.mode_reglement,  --fk_mode_reglement,
MAX(tempfact.dateche),  --date_lim_reglement,
NULL,  --note_private,
NULL,  --note_public,
'crabe',  --model_pdf,
lettrage.id,  --import_key,
NULL  --extraparams
FROM lettrage
INNER JOIN tempfact ON tempfact.numfact=lettrage.nopfact
INNER JOIN thirdparty ON thirdparty.id=lettrage.thirdparty_id
INNER JOIN account ON account.id=thirdparty.account_id
INNER JOIN llx_societe ON llx_societe.import_key=account.id
INNER JOIN convct ON convct.id=lettrage.convct_id
WHERE convct.supprime=0
GROUP BY lettrage.nopfact;

--Import Invoice line
INSERT INTO llx_facturedet (
fk_facture,
fk_parent_line,
fk_product,
label,
description,
tva_tx,
localtax1_tx,
localtax1_type,
localtax2_tx,
localtax2_type,
qty,
remise_percent,
remise,
fk_remise_except,
subprice,
price,
total_ht,
total_tva,
total_localtax1,
total_localtax2,
total_ttc,
product_type,
date_start,
date_end,
info_bits,
buy_price_ht,
fk_product_fournisseur_price,
fk_code_ventilation,
special_code,
rang,
import_key) 
SELECT DISTINCT
llx_facture.rowid,  --fk_facture,
NULL,  --fk_parent_line,
NULL,  --fk_product,
NULL,  --label,
tempfact.intitule,  --description,
tempfact.tauxtva,  --tva_tx,
0,  --localtax1_tx,
0,  --localtax1_type,
0,  --localtax2_tx,
0,  --localtax2_type,
tempfact.nbjr,  --qty,
0,  --remise_percent,
0,  --remise,
NULL,  --fk_remise_except,
tempfact.taux,  --subprice,
NULL,  --price,
tempfact.montant,  --total_ht,
tempfact.mttva,  --total_tva,
0,  --total_localtax1,
0,  --total_localtax2,
tempfact.mtttc,  --total_ttc,
1,  --product_type,
NULL,  --date_start,
NULL,  --date_end,
0,  --info_bits,
0,  --buy_price_ht,
NULL,  --fk_product_fournisseur_price,
0,  --fk_code_ventilation,
0,  --special_code,
tempfact.numlig,  --rang,
NULL  --import_key
FROM llx_facture
INNER JOIN tempfact ON tempfact.numfact=llx_facture.facnumber
INNER JOIN thirdparty ON thirdparty.id=tempfact.thirdparty_id
INNER JOIN account ON account.id=thirdparty.account_id
INNER JOIN llx_societe ON llx_societe.import_key=account.id
INNER JOIN convct ON convct.id=tempfact.convct_id;


--Update invoice header amount
UPDATE llx_facture
SET llx_facture.total_ttc=(SELECT SUM(total_ttc) FROM llx_facturedet WHERE llx_facturedet.fk_facture=llx_facture.rowid GROUP BY llx_facturedet.fk_facture),
llx_facture.tva=(SELECT SUM(total_tva) FROM llx_facturedet WHERE llx_facturedet.fk_facture=llx_facture.rowid  GROUP BY llx_facturedet.fk_facture),
llx_facture.total=(SELECT SUM(total_ttc) FROM llx_facturedet WHERE llx_facturedet.fk_facture=llx_facture.rowid  GROUP BY llx_facturedet.fk_facture),
llx_facture.tms=llx_facture.tms;

--Lier facture Session/client
INSERT INTO llx_agefodd_facture(
fk_commande,
fk_facture,
fk_propal,
fk_session,
fk_societe,
fk_user_author,
datec,
fk_user_mod,
tms)
SELECT 
NULL,
llx_facture.rowid,
NULL,
llx_agefodd_session.rowid,
llx_societe.rowid,
1,
NOW(),
1,
NOW()
FROM llx_facture
INNER JOIN tempfact ON tempfact.numfact=llx_facture.facnumber
INNER JOIN convct ON convct.id=tempfact.convct_id
INNER JOIN session as sess ON sess.id=convct.session_id
INNER JOIN llx_agefodd_session ON llx_agefodd_session.import_key=sess.id
INNER JOIN account ON account.id=convct.ent_account_id
INNER JOIN llx_societe ON llx_societe.import_key=account.id
WHERE CONCAT(llx_agefodd_session.rowid,'&', llx_societe.rowid) NOT IN (SELECT CONCAT(fk_session, '&',fk_societe) FROM llx_agefodd_facture);


--Remove temporarie data
ALTER TABLE llx_societe DROP INDEX idx_llx_societe_import_key;
ALTER TABLE llx_agefodd_session DROP INDEX idx_llx_agefodd_session_import_key;
ALTER TABLE llx_propal DROP INDEX idx_llx_propal_import_key;
ALTER TABLE llx_user DROP INDEX idx_llx_user_import_key;
ALTER TABLE llx_socpeople DROP INDEX idx_llx_socpeople_import_key;
SET foreign_key_checks = 1;
