-- ============================================================================
-- Copyright (C) 2014      Juanjo Menent <jmenent@2byte.es>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===========================================================================
  
ALTER TABLE llx_webmail_mail ADD INDEX `idx_webmail_mail_nospam` (`fk_user`,`state_spam`);
ALTER TABLE llx_webmail_mail ADD INDEX `idx_webmail_mail_count` (`fk_user`,`state_new`);
ALTER TABLE llx_webmail_mail ADD INDEX `idx_webmail_mail_sendmail` (`is_outbox`,`state_sent`);
ALTER TABLE llx_webmail_mail ADD INDEX `idx_webmail_mail_fk_user` (`fk_user`);
