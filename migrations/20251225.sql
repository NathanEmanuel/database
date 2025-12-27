ALTER TABLE `options`
ADD CONSTRAINT `fk_options_poll`
FOREIGN KEY (`poll_id`) REFERENCES `polls`(`poll_id`)
ON DELETE CASCADE;

ALTER TABLE `votes`
ADD CONSTRAINT `fk_votes_poll`
FOREIGN KEY (`poll_id`) REFERENCES `polls`(`poll_id`)
ON DELETE CASCADE,
ADD CONSTRAINT `fk_votes_options`
FOREIGN KEY (`option_id`) REFERENCES `options`(`option_id`)
ON DELETE CASCADE;