ALTER TABLE votes
    ADD UNIQUE KEY uniq_vote (poll_id, user_id),
    ADD KEY `idx_votes_poll` (`poll_id`),
    ADD KEY `idx_votes_option` (`option_id`),
    ADD CONSTRAINT `fk_votes_poll` FOREIGN KEY (poll_id) REFERENCES polls(poll_id) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_votes_option` FOREIGN KEY (option_id) REFERENCES options(option_id) ON DELETE CASCADE;

ALTER TABLE options
    ADD KEY `idx_options_poll` (`poll_id`),
    ADD CONSTRAINT `fk_options_poll` FOREIGN KEY (poll_id) REFERENCES polls(poll_id) ON DELETE CASCADE;

ALTER TABLE purchase_items
    ADD KEY `idx_purchase_items_purchases` (`purchase_id`),
    ADD CONSTRAINT `fk_purchase_items_purchases` FOREIGN KEY (`purchase_id`) REFERENCES purchases(`purchase_id`);