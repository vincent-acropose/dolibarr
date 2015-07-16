-- ============================================================================
-- Copyright (C) 2014   	Philippe Grand		<philippe.grand@atoo-net.com>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.

INSERT INTO llx_c_ultimatepdf_line(rowid,code,label,description,active) VALUES (1,'TEXTE1','Garantie 2 ans pièces et main d''œuvre, retour en atelier (Hors filtre et pièce d''usure)','texte de garantie',1);

INSERT INTO llx_extrafields (rowid, name, entity, elementtype, tms, label, type, size, pos, param, fieldunique, fieldrequired) VALUES
(3001001, 'newline', 1, 'propal', '2014-03-31 12:30:27', 'New line', 'sellist', '', 0, 'a:1:{s:7:"options";a:1:{s:29:"c_ultimatepdf_line:label:code";N;}}', 0, 0) ;
INSERT INTO llx_extrafields (rowid, name, entity, elementtype, tms, label, type, size, pos, param, fieldunique, fieldrequired) VALUES
(3001002, 'newline', 1, 'commande', '2014-03-31 12:30:27', 'New line', 'sellist', '', 0, 'a:1:{s:7:"options";a:1:{s:29:"c_ultimatepdf_line:label:code";N;}}', 0, 0) ;
INSERT INTO llx_extrafields (rowid, name, entity, elementtype, tms, label, type, size, pos, param, fieldunique, fieldrequired) VALUES
(3001003, 'newline', 1, 'facture', '2014-03-31 12:30:27', 'New line', 'sellist', '', 0, 'a:1:{s:7:"options";a:1:{s:29:"c_ultimatepdf_line:label:code";N;}}', 0, 0);



ALTER TABLE llx_propal_extrafields ADD COLUMN newline text NULL;
ALTER TABLE llx_commande_extrafields ADD COLUMN newline text NULL;
ALTER TABLE llx_facture_extrafields ADD COLUMN newline text NULL;




