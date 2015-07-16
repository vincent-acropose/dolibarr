ALTER TABLE llx_societe_extrafields ADD COLUMN salesman text;


DELETE FROM llx_extrafields WHERE rowid=1030451;
INSERT INTO llx_extrafields (rowid, name, entity, elementtype, tms, label, type, size, pos, param, fieldunique, fieldrequired) VALUES
(1030451, 'salesman', 1, 'societe', '2013-07-18 15:06:57', 'Apporteur d''affaire', 'sellist', '', 0, 'a:1:{s:7:"options";a:1:{s:21:"c_salesman:libelle:rowid";N;}}', 0, 0);
