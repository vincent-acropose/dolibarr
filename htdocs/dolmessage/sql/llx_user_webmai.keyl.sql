



--
-- Constraints for table llx_userdolmessagewebmail
--
ALTER TABLE llx_userwebmail
  ADD CONSTRAINT fk_userdolmessagewebmail_fk_user FOREIGN KEY (fk_user) REFERENCES llx_user (rowid);
 
