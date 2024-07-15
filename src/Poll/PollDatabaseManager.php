<?php

namespace Compucie\Database\Poll;

use Compucie\Database\CouldNotInsertException;
use Compucie\Database\DatabaseManager;
use Compucie\Database\Poll\Model\Options;
use Compucie\Database\Poll\Model\Poll;
use Compucie\Database\SqlExecutionFailedException;
use DateTime;

class PollDatabaseManager extends DatabaseManager
{
    const SQL_DATETIME_FORMAT = "Y-m-d H:i:s";

    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    /**
     * Create all tables used by this database manager.
     */
    public function createTables(): void
    {
        $statement = $this->getClient()->prepare(
            "CREATE TABLE `polls` (
                `poll_id` INT NOT NULL AUTO_INCREMENT,
                `question` VARCHAR(255) NOT NULL,
                `published_at` DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
                `expires_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (poll_id)
            );"
        );
        $isSuccesful = $statement->execute();
        $statement->close();
        if (!$isSuccesful) {
            throw new SqlExecutionFailedException;
        }

        $statement->$this->getClient()->prepare(
            "CREATE TABLE `options` (
                `option_id` INT NOT NULL AUTO_INCREMENT,
                `poll_id` INT NOT NULL,
                `text` VARCHAR(4095) NOT NULL,
                PRIMARY KEY (option_id),
                FOREIGN KEY (poll_id) REFERENCES polls(poll_id)
            );"
        );
        $isSuccesful = $statement->execute();
        $statement->close();
        if (!$isSuccesful) {
            throw new SqlExecutionFailedException;
        }

        $statement->$this->getClient()->prepare(
            "CREATE TABLE `votes` (
                `vote_id` INT NOT NULL AUTO_INCREMENT,
                `poll_id` INT NOT NULL,
                `option_id` INT NOT NULL,
                `user_id` INT NOT NULL,
                `published_at` DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
                PRIMARY KEY (vote_id),
                FOREIGN KEY (poll_id) REFERENCES polls(poll_id),
                FOREIGN KEY (option_id) REFERENCES options(option_id)
            );"
        );
        $isSuccesful = $statement->execute();
        $statement->close();
        if (!$isSuccesful) {
            throw new SqlExecutionFailedException;
        }
    }

    /**
     * Return the IDs of the currently active polls.
     * @return  int[]   The IDs of the currently active polls.
     * @throws  SqlExecutionFailedException
     */
    public function getActivePollIds(): array
    {
        $statement = $this->getClient()->prepare("SELECT `poll_id` FROM `polls` WHERE `expires_at` > UTC_TIMESTAMP() OR `expires_at` IS NULL");
        $statement->bind_result($activePollId);
        $isSuccesful = $statement->execute();

        if (!$isSuccesful) {
            throw new SqlExecutionFailedException;
        }

        $activePollIds = array();
        while ($statement->fetch()) {
            $activePollIds[] = $activePollId;
        }
        $statement->close();

        return $activePollIds;
    }

    /**
     * Return the currently active polls.
     * @return  Poll[]  The currently active polls.
     * @throws  SqlExecutionFailedException
     */
    public function getActivePolls(): array
    {
        $activePolls = array();
        foreach ($this->getActivePollIds() as $id) {
            $activePolls[] = $this->getPoll($id);
        }
        return $activePolls;
    }

    /**
     * Return the latest polls. The amount is limited by the given value.
     * @param   int     $max    The maximum amount of polls to get.
     * @return  Poll[]          Array containing the retrieved polls.
     * @throws  SqlExecutionFailedException
     */
    public function getLatestPolls(int $max): array
    {
        $statement = $this->getClient()->prepare("SELECT `poll_id` FROM `polls` ORDER BY `poll_id` DESC LIMIT ?");
        $statement->bind_param("i", $max);
        $statement->bind_result($pollId);
        $isSuccesful = $statement->execute();

        if (!$isSuccesful) {
            $statement->close();
            throw new SqlExecutionFailedException;
        }

        $latestPollIds = array();
        while ($statement->fetch()) {
            $latestPollIds[] = $pollId;
        }
        $statement->close();

        $latestPolls = array();
        foreach ($latestPollIds as $id) {
            $latestPolls[] = $this->getPoll($id);
        }

        return $latestPolls;
    }

    /**
     * Return whether the given user has voted on the given poll.
     * @param   int     $pollId     ID of the poll to check for.
     * @param   int     $userId     ID of the user to check for.
     * @return  bool                Whether the user is allowed to vote on the poll.
     * @throws  SqlExecutionFailedException
     */
    public function hasUserVoted(int $pollId, int $userId): bool
    {
        $statement = $this->getClient()->prepare("SELECT COUNT(*) FROM `votes` WHERE `poll_id` = ? AND `user_id` = ?");
        $statement->bind_param("ii", $pollId, $userId);
        $statement->bind_result($voteCount);
        $isSuccesful = $statement->execute();
        $statement->fetch();
        $statement->close();

        if (!$isSuccesful) {
            throw new SqlExecutionFailedException;
        }

        return $voteCount > 0;
    }

    /**
     * Return the IDs of the polls that the user may vote on.
     * @param   int     $userId     The ID of the user.
     * @return  int[]               Array of IDs of polls on which the user can vote.
     * @throws  SqlExecutionFailedException
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
     * Add a poll. This does not incude the poll's options.
     * @param   string      $question   The poll's question.
     * @param   DateTime    $expiresAt  The moment at which the poll expires.
     * @throws  SqlExecutionFailedException
     */
    public function addPoll(string $question, DateTime $expiresAt): void
    {
        $statement = $this->getClient()->prepare("INSERT INTO `polls` (`question`, `expires_at`) VALUES (?, ?)");
        $expiresAtString = $expiresAt->format(self::SQL_DATETIME_FORMAT);
        $statement->bind_param("ss", $question, $expiresAtString);
        $isSuccesful = $statement->execute();
        $statement->close();

        if (!$isSuccesful) {
            throw new SqlExecutionFailedException;
        }
    }

    /**
     * Add an option to a poll.
     * @param   int     $pollId     ID of the poll to which the option must be added.
     * @param   string  $text       The textual representation of the option.
     * @throws  SqlExecutionFailedException
     */
    public function addOption(int $pollId, string $text): void
    {
        $statement = $this->getClient()->prepare("INSERT INTO `options` (`poll_id`, `text`) VALUES (?, ?)");
        $statement->bind_param("is", $pollId, $text);
        $isSuccesful = $statement->execute();
        $statement->close();

        if (!$isSuccesful) {
            throw new SqlExecutionFailedException;
        }
    }

    /**
     * Add a vote.
     * @param   int     $pollId     ID of the poll on which was voted.
     * @param   int     $usedId     ID of the user who voted.
     * @param   int     $optionId   ID of the option that was chosen.
     * @throws  SqlExecutionFailedException
     */
    public function addVote(int $pollId, int $userId, int $optionId): void
    {
        $statement = $this->getClient()->prepare("INSERT INTO `votes` (`poll_id`, `user_id`, `option_id`) VALUES (?, ?, ?)");
        $statement->bind_param("iii", $pollId, $userId, $optionId);
        $isSuccesful = $statement->execute();
        $statement->close();

        if (!$isSuccesful) {
            throw new SqlExecutionFailedException;
        }
    }

    /**
     * Return the poll with the given ID. This includes the poll's options and the vote counts per option,
     * as well as the total vote count.
     * @param   int     $pollId     ID of the poll to get.
     * @return  Poll                The poll with the given ID.
     * @throws  SqlExecutionFailedException
     */
    public function getPoll(int $pollId): Poll
    {
        $statement = $this->getClient()->prepare("SELECT `question`, `published_at`, `expires_at` FROM `polls` WHERE `poll_id` = ?");
        $statement->bind_param("i", $pollId);
        $statement->bind_result($question, $publishedAt, $expiresAt);
        $isSuccesful = $statement->execute();
        $statement->fetch();
        $statement->close();

        if (!$isSuccesful) {
            throw new SqlExecutionFailedException;
        }

        $options = $this->getOptions($pollId);
        $options = $this->getVoteCounts($pollId, $options);
        $pollVoteCount = $this->getPollVoteCount($pollId);
        return new Poll($pollId, $question, new DateTime($publishedAt), new DateTime($expiresAt), $options, $pollVoteCount);
    }

    /**
     * Return the options for the given poll.
     * @param   int     $pollId     ID of the poll for which to get the options.
     * @return  Options             Object with the options.
     * @throws  SqlExecutionFailedException
     */
    private function getOptions(int $pollId): Options
    {
        $statement = $this->getClient()->prepare("SELECT `option_id`, `text` FROM `options` WHERE `poll_id` = ?");
        $statement->bind_param("i", $pollId);
        $statement->bind_result($id, $answer);
        $isSuccesful = $statement->execute();

        if (!$isSuccesful) {
            $statement->close();
            throw new SqlExecutionFailedException;
        }

        $options = new Options();
        while ($statement->fetch()) {
            $options->setText($id, $answer);
        }
        $statement->close();

        return $options;
    }

    /**
     * Return the options and the vote count for each option.
     * The value is another array that contains the option text and the vote count.
     * @param   int         $pollId     ID of the poll for which to get the vote counts.
     * @param   Options     $options    Object with the options.
     * @return  Options                 Object with the options and their vote counts.
     * @throws  SqlExecutionFailedException
     */
    private function getVoteCounts(int $pollId, Options $options): Options
    {
        $statement = $this->getClient()->prepare("SELECT COUNT(*) FROM `votes` WHERE `poll_id` = ? AND `option_id` = ?");
        $statement->bind_param("ii", $pollId, $optionId);
        $statement->bind_result($voteCount);

        foreach ($options->getIds() as $optionId) {
            $isSuccesful = $statement->execute();

            if (!$isSuccesful) {
                $statement->close();
                throw new SqlExecutionFailedException;
            }

            $statement->fetch();
            $options->setVoteCount($optionId, $voteCount);
        }
        $statement->close();

        return $options;
    }

    /**
     * Return total vote count of the given poll.
     * @param   int     $pollId     ID of the poll for which to get the vote counts.
     * @return  int                 The total vote count of the poll.
     */
    private function getPollVoteCount(int $pollId): int
    {
        $statement = $this->getClient()->prepare("SELECT COUNT(*) FROM `votes` WHERE `poll_id` = ?");
        $statement->bind_param("i", $pollId);
        $statement->bind_result($voteCount);
        $isSuccesful = $statement->execute();
        $statement->fetch();
        $statement->close();

        if (!$isSuccesful) {
            throw new SqlExecutionFailedException;
        }

        return $voteCount;
    }
}
