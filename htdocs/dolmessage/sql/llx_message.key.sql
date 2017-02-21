--
-- Indexes for table `llx_message`
--
ALTER TABLE `llx_message`
 ADD PRIMARY KEY (`row_id`), ADD UNIQUE KEY `user_id` (`user_id`,`usergroup_id`,`number`,`entity`,`message_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `llx_message`
--
ALTER TABLE `llx_message`
MODIFY `row_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;