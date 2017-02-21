ALTER TABLE  `llx_equipementevt`	ADD  `fk_project`	Integer 		NULL 		DEFAULT NULL;
ALTER TABLE  `llx_equipement`		ADD  `import_key` 	VARCHAR( 14 ) 	NULL 		DEFAULT NULL;
ALTER TABLE  `llx_equipementevt`	ADD  `import_key`	VARCHAR( 14 ) 	NULL 		DEFAULT NULL;
ALTER TABLE  `llx_equipement`		ADD  `price`		double(24,8)	NULL		DEFAULT  '0';
ALTER TABLE  `llx_equipement`		ADD  `pmp`			double(24,8)	NULL		DEFAULT  '0';
ALTER TABLE  `llx_equipement`		ADD  `unitweight`	double(24,8)	NULL		DEFAULT  '0';		-- NEW : poid de l'équipement
ALTER TABLE  `llx_equipement`		ADD  `quantity`		INT 			NOT NULL	DEFAULT  '1';

