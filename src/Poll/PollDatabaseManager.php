<?php

namespace Compucie\Database\Poll;

use Compucie\Database\DatabaseManager;
use Compucie\Database\Poll\Model\Option;
use Compucie\Database\Poll\Model\Poll;
use Compucie\Database\Poll\Model\Vote;
use DateTime;
use Exception;
use mysqli_sql_exception;
use function Compucie\Database\makeErrorLogging;
use function Compucie\Database\safeDateTime;

class PollDatabaseManager extends DatabaseManager
{
    /**
     * @throws  mysqli_sql_exception
     */
    public function createTables(): void
    {
        $statement = $this->getClient()->prepare(
            "CREATE TABLE IF NOT EXISTS `polls` (
                `poll_id` INT NOT NULL AUTO_INCREMENT,
                `question` VARCHAR(255) NOT NULL,
                `published_at` DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
                `expires_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (poll_id)
            );"
        );
        if ($statement){
            $statement->execute();
            $statement->close();
        }

        $statement = $this->getClient()->prepare(
            "CREATE TABLE IF NOT EXISTS `options` (
                `option_id` INT NOT NULL AUTO_INCREMENT,
                `poll_id` INT NOT NULL,
                `text` VARCHAR(4095) NOT NULL,
                PRIMARY KEY (option_id),
                KEY `idx_options_poll` (`poll_id`),
                CONSTRAINT `fk_options_poll` FOREIGN KEY (poll_id) REFERENCES polls(poll_id) ON DELETE CASCADE
            );"
        );
        if ($statement){
            $statement->execute();
            $statement->close();
        }

        $statement = $this->getClient()->prepare(
            "CREATE TABLE IF NOT EXISTS `votes` (
                `vote_id` INT NOT NULL AUTO_INCREMENT,
                `poll_id` INT NOT NULL,
                `option_id` INT NOT NULL,
                `user_id` INT NOT NULL,
                `published_at` DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
                PRIMARY KEY (vote_id),
                UNIQUE KEY `uniq_vote` (`poll_id`, `user_id`),
                KEY `idx_votes_poll` (`poll_id`),
                KEY `idx_votes_option` (`option_id`),
                CONSTRAINT `fk_votes_poll` FOREIGN KEY (poll_id) REFERENCES polls(poll_id) ON DELETE CASCADE,
                CONSTRAINT `fk_votes_option` FOREIGN KEY (option_id) REFERENCES options(option_id) ON DELETE CASCADE
            );"
        );
        if ($statement){
            $statement->execute();
            $statement->close();
        }
    }

    /* ------------- POLLS ------------- */

    /**
     * Add a poll. This does not include the poll's options.
     * @param   string      $question   The poll's question.
     * @param   DateTime    $expiresAt  The moment at which the poll expires.
     * @throws  mysqli_sql_exception
     */
    public function addPoll(string $question, DateTime $expiresAt): int
    {
        return $this->executeCreate(
            "polls",
            ["`question`", "`expires_at`"],
            [
                $question,
                $expiresAt->format(self::SQL_DATETIME_FORMAT),
            ],
            "ss"
        );
    }

    /**
     * Return the poll with the given ID. This includes the poll's options and the vote counts per option,
     * as well as the total vote count.
     * @param int $pollId ID of the poll to get.
     * @return  ?Poll                The poll with the given ID.
     * @throws  mysqli_sql_exception
     * @throws Exception
     */
    public function getPoll(int $pollId): ?Poll
    {
        if ($pollId <= 0){
            return null;
        }

        $row = $this->executeReadOne(
            "SELECT `question`, `published_at`, `expires_at` 
            FROM `polls` 
            WHERE `poll_id` = ?",
            [$pollId],
            "i"
        );

        if ($row === null) {
            throw new Exception("Poll $pollId not found");
        }

        $options            = $this->getOptions($pollId);
        $pollVoteCount      = $this->getPollVoteCount($pollId);

        return new Poll(
            $pollId,
            (string)$row['question'],
            safeDateTime((string)$row['published_at']),
            safeDateTime((string)$row['expires_at']),
            $options,
            $pollVoteCount
        );
    }

    /**
     * Update a poll and possibly clear the expires at field.
     * @param int $pollId
     * @param string|null $question
     * @param DateTime|null $expiresAt
     * @param bool $clearExpiresAt
     * @return bool
     * @throws mysqli_sql_exception
     */
    public function updatePoll(
        int $pollId,
        ?string $question = null,
        ?DateTime $expiresAt = null,
        bool $clearExpiresAt = false
    ): bool {
        $fields = [];
        $params = [];
        $types  = '';

        if ($clearExpiresAt) {
            $fields[] = 'expires_at = NULL';
        } elseif ($expiresAt !== null) {
            $fields[] = 'expires_at = ?';
            $params[] = $expiresAt->format(self::SQL_DATETIME_FORMAT);
            $types   .= 's';
        }

        if ($question !== null) {
            $fields[] = 'question = ?';
            $params[] = $question;
            $types   .= 's';
        }

        return $this->executeUpdate('polls', 'poll_id', $pollId, $fields, $params, $types);
    }

    /**
     * Delete the poll
     * @param int $pollId
     * @return bool
     */
    public function deletePoll(int $pollId): bool
    {
        return $this->executeDelete("polls", "poll_id", $pollId);
    }

    /**
     * Return the IDs of the currently active polls.
     * @return  int[]   The IDs of the currently active polls.
     * @throws  mysqli_sql_exception
     */
    public function getActivePollIds(): array
    {
        $rows = $this->executeReadAll(
            "SELECT `poll_id` 
            FROM `polls` 
            WHERE `expires_at` > NOW() 
               OR `expires_at` IS NULL"
        );

        return array_map(
            static fn(array $row): int => (int)$row['poll_id'],
            $rows
        );
    }

    /**
     * Return the currently active polls.
     * @return  Poll[]  The currently active polls.
     * @throws  mysqli_sql_exception
     */
    public function getActivePolls(): array
    {
        $activePolls = array();
        foreach ($this->getActivePollIds() as $id) {
            try {
                $poll = $this->getPoll($id);
                if ($poll === null) {
                    continue;
                }
                $activePolls[] = $poll;
            } catch (Exception $e) {
                makeErrorLogging("getActivePolls", $e);
                continue;
            }
        }
        return $activePolls;
    }

    /**
     * @throws  mysqli_sql_exception
     */
    public function getMostRecentlyExpiredPoll(): ?Poll
    {
        $row = $this->executeReadOne(
            "SELECT `poll_id`
            FROM `polls`
            WHERE `expires_at` IS NOT NULL
                AND NOW() > `expires_at`
            ORDER BY `expires_at` DESC
            LIMIT 1"
        );

        if ($row === null) {
            return null;
        }

        try {
            return $this->getPoll((int)$row['poll_id']);
        } catch (Exception $e) {
            makeErrorLogging("getMostRecentlyExpiredPoll", $e);
            return null;
        }
    }

    /**
     * Return the latest polls. The amount is limited by the given value.
     * @param int $max The maximum amount of polls to get.
     * @return  Poll[]          Array containing the retrieved polls.
     * @throws  mysqli_sql_exception
     */
    public function getLatestPolls(int $max): array
    {
        if ($max <= 0) {
            return [];
        }

        $rows = $this->executeReadAll(
            "SELECT `poll_id` FROM `polls` ORDER BY `poll_id` DESC LIMIT ?",
            [$max],
            "i"
        );

        $latestPolls = [];

        foreach ($rows as $row) {
            $id = (int)$row['poll_id'];
            try {
                $poll = $this->getPoll($id);
                if ($poll === null) {
                    continue;
                }
                $latestPolls[] = $poll;
            } catch (Exception $e) {
                makeErrorLogging("getLatestPolls", $e);
                continue;
            }
        }

        return $latestPolls;
    }

    /**
     * Return the IDs of the polls that the user may vote on.
     * @param   int     $userId     The ID of the user.
     * @return  int[]               Array of IDs of polls on which the user can vote.
     * @throws  mysqli_sql_exception
     */
    public function getVotablePollIds(int $userId): array
    {
        $pollIds = $this->getActivePollIds();
        $votablePollIds = array();
        foreach ($pollIds as $pollId) {
            if (!$this->hasUserVoted($pollId, $userId)) {
                $votablePollIds[] = $pollId;
            }
        }
        return $votablePollIds;
    }

    /**
     * Expire a poll immediately.
     * @param   int     $pollId     The ID of the poll to expire.
     * @throws  mysqli_sql_exception
     */
    public function expirePoll(int $pollId): bool
    {
        return $this->executeUpdate(
            "polls",
            "poll_id",
            $pollId,
            ["`expires_at` = NOW()"]
        );
    }

    /**
     * Return total vote count of the given poll.
     * @param   int     $pollId     ID of the poll for which to get the vote counts.
     * @return  int                 The total vote count of the poll.
     * @throws  mysqli_sql_exception
     */
    private function getPollVoteCount(int $pollId): int
    {
        if ($pollId <= 0) {
            return 0;
        }

        $row = $this->executeReadOne(
            "SELECT COUNT(*) AS cnt FROM `votes` WHERE `poll_id` = ?",
            [$pollId],
            "i"
        );

        return $row === null ? 0 : (int)$row['cnt'];
    }

    /* ------------- OPTIONS ------------- */

    /**
     * Add an option to a poll.
     * @param   int     $pollId     ID of the poll to which the option must be added.
     * @param   string  $text       The textual representation of the option.
     * @throws  mysqli_sql_exception
     */
    public function addOption(int $pollId, string $text): int
    {
        if ($pollId <= 0) {
            return -1;
        }

        return $this->executeCreate(
            'options',
            ['`poll_id`', '`text`'],
            [$pollId, $text],
            'is'
        );
    }

    /**
     * @throws Exception
     */
    public function getOption(int $optionId): ?Option
    {
        if ($optionId <= 0) {
            return null;
        }

        $row = $this->executeReadOne(
            "SELECT `option_id`, `poll_id`, `text`
         FROM `options`
         WHERE `option_id` = ?",
            [$optionId],
            "i"
        );

        if ($row === null) {
            throw new Exception("Option $optionId not found");
        }

        $id     = (int)$row['option_id'];
        $pollId = (int)$row['poll_id'];
        $text   = (string)$row['text'];

        return new Option($id, $pollId, $text, $this->getVotes($id));
    }

    /**
     * Return the options for the given poll.
     * @param   int     $pollId     ID of the poll for which to get the options.
     * @return  array<Option>       Object with the options.
     * @throws  mysqli_sql_exception
     */
    public function getOptions(int $pollId): array
    {
        if ($pollId <= 0) {
            return array();
        }

        $rows = $this->executeReadAll(
            "SELECT `option_id`, `text`
         FROM `options`
         WHERE `poll_id` = ?",
            [$pollId],
            "i"
        );

        $options = array();

        foreach ($rows as $row) {
            $option = new Option(
                $row['option_id'],
                $pollId,
                $row['text'],
                $this->getVotes($row['option_id'])
            );
            $options[] = $option;
        }

        return $options;
    }

    /**
     * Update option text in database.
     * @param int $optionId
     * @param string $optionText
     * @return bool
     * @throws mysqli_sql_exception
     */
    public function updateOption(int $optionId, string $optionText): bool
    {
        return $this->executeUpdate(
            'options',
            'option_id',
            $optionId,
            ['`text` = ?'],
            [$optionText],
            's'
        );
    }

    /**
     * Delete an option in database.
     * @param   int     $optionId   The ID of the option.
     * @throws  mysqli_sql_exception
     */
    public function deleteOption(int $optionId): bool
    {
        return $this->executeDelete('options', 'option_id', $optionId);
    }

    /* ------------- VOTES ------------- */

    /**
     * Add a vote.
     * @param   int     $pollId     ID of the poll on which was voted.
     * @param   int     $userId     ID of the user who voted.
     * @param   int     $optionId   ID of the option that was chosen.
     * @throws  mysqli_sql_exception
     */
    public function addVote(int $pollId, int $userId, int $optionId): int
    {
        return $this->executeCreate(
            table: 'votes',
            fields: ['`poll_id`', '`user_id`', '`option_id`'],
            params: [$pollId, $userId, $optionId],
            types: 'iii'
        );
    }

    /**
     * @param int $optionId
     * @return array<Vote>
     * @throws mysqli_sql_exception
     */
    public function getVotes(int $optionId): array
    {
        $rows = $this->executeReadAll(
            "SELECT *
         FROM `votes`
         WHERE `option_id` = ?",
            [$optionId],
            "i"
        );

        $votes = [];

        foreach ($rows as $row) {
            $votes[] = new Vote(
                (int)$row['vote_id'],
                (int)$row['poll_id'],
                (int)$row['option_id'],
                (int)$row['user_id'],
                safeDateTime((string)$row['published_at'])
            );
        }

        return $votes;
    }

    public function deleteVote(int $voteId): bool
    {
        return $this->executeDelete('votes', 'vote_id', $voteId);
    }

    /**
     * Return whether the given user has voted on the given poll.
     * @param   int     $pollId     ID of the poll to check for.
     * @param   int     $userId     ID of the user to check for.
     * @return  bool                Whether the user is allowed to vote on the poll.
     * @throws  mysqli_sql_exception
     */
    public function hasUserVoted(int $pollId, int $userId): bool
    {
        $row = $this->executeReadOne(
            "SELECT COUNT(*) AS cnt
         FROM `votes`
         WHERE `poll_id` = ?
           AND `user_id` = ?",
            [$pollId, $userId],
            "ii"
        );

        return $row !== null && (int)$row['cnt'] > 0;
    }
}
