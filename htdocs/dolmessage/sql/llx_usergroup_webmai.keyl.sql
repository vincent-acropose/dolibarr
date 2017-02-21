



--
-- Constraints for table llx_userdolmessagewebmail
--
ALTER TABLE llx_usergroupwebmail
  ADD CONSTRAINT fk_usergroupdolmessagewebmail_fk_user FOREIGN KEY (fk_usergroup) REFERENCES llx_usergroup (rowid);
 
