<?php

namespace Compucie\Database\Poll;

use Compucie\Database\CouldNotInsertException;
use Compucie\Database\DatabaseManager;
use Compucie\Database\Poll\Model\Answers;
use Compucie\Database\Poll\Model\Poll;
use DateTime;

class PollDatabaseManager extends DatabaseManager
{
    const SQL_DATETIME_FORMAT = "Y-m-d H:i:s";

    public function __construct(string $configpath)
    {
        parent::__construct($configpath);
    }

    /**
     * @return  int[]
     */
    public function getActivePollIds(): array
    {
        $statement = $this->getClient()->prepare("SELECT `poll_id` FROM `polls` WHERE `expires_at` > UTC_TIMESTAMP() OR `expires_at` IS NULL");
        $statement->execute();
        $statement->bind_result($activePollId);

        $activePollIds = array();
        while ($statement->fetch()) {
            $activePollIds[] = $activePollId;
        }

        return $activePollIds;
    }

    /**
     * @return  Poll[]
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
     * @param   int     $max
     * @param   Poll[]
     */
    public function getLatestPolls(int $max): array
    {
        $statement = $this->getClient()->prepare("SELECT `poll_id` FROM `polls` ORDER BY `poll_id` DESC LIMIT ?");
        $statement->bind_param("i", $max);
        $statement->bind_result($poll_id);
        $statement->execute();

        $latestPollIds = array();
        while ($statement->fetch()) {
            $latestPollIds[] = $poll_id;
        }
        
        $statement->close();

        $latestPolls = array();
        foreach ($latestPollIds as $id) {
            $latestPolls[] = $this->getPoll($id);
        }
        return $latestPolls;
    }

    public function hasUserVoted(int $pollId, int $userId): bool
    {
        $statement = $this->getClient()->prepare("SELECT COUNT(*) FROM `votes` WHERE `poll_id` = ? AND `user_id` = ?");
        $statement->bind_param("ii", $pollId, $userId);
        $statement->execute();
        $statement->bind_result($voteCount);
        $statement->fetch();

        return $voteCount > 0;
    }

    /**
     * @param   int     $userId
     * @return  int[]
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

    public function addPoll(string $question, Datetime $expiry) {
        $statement = $this->getClient()->prepare("INSERT INTO `polls` (`question`, `expires_at`) VALUES (?, ?)");
        $expiryString = $expiry->format(self::SQL_DATETIME_FORMAT);
        $statement->bind_param("ss", $question, $expiryString);
        $statement->execute();
        $statement->close();
    }

    public function addAnswer(int $pollId, string $text): void
    {
        $statement = $this->getClient()->prepare("INSERT INTO `answers` (`poll_id`, `text`) VALUES (?, ?)");
        $statement->bind_param("is", $pollId, $text);
        $isSuccesful = $statement->execute();
        $statement->close();

        if (!$isSuccesful) {
            throw new CouldNotInsertException("Answer could not be inserted.");
        }
    }

    /**
     * INSERT INTO `votes` (poll_id, user_id, answer_id) VALUES ($pollId, $userId, $answerId);
     * throw exception on fail
     */
    public function addVote(int $pollId, int $userId, int $answerId): void
    {
        $statement = $this->getClient()->prepare("INSERT INTO `votes` (`poll_id`, `user_id`, `answer_id`) VALUES (?, ?, ?)");
        $statement->bind_param("iii", $pollId, $userId, $answerId);

        $isSuccesful = $statement->execute();
        if (!$isSuccesful) {
            throw new CouldNotInsertException;
        }
    }

    /**
     * SELECT (question) FROM `polls` WHERE `id` = $pollId;
     * SELECT (id, answer) FROM `answers` WHERE `poll_id` = $pollId;
     * for each id:
     *     SELECT COUNT(*) FROM `votes` WHERE `poll_id` = $pollId AND `answer_id` = id;
     * return question, answers, and votes per answer in Poll object+
     */
    public function getPoll(int $pollId): Poll
    {
        $statement = $this->getClient()->prepare("SELECT `question`, `published_at`, `expires_at` FROM `polls` WHERE `poll_id` = ?");
        $statement->bind_param("i", $pollId);
        $statement->bind_result($question, $published, $expiry);
        $statement->execute();
        $statement->fetch();
        $statement->close();
    
        $answers = $this->getAnswers($pollId);
        $answers = $this->getVoteCounts($pollId, $answers);
        $pollVoteCount = $this->getPollVoteCount($pollId);
        return new Poll($pollId, $question, new DateTime($published), new DateTime($expiry), $answers, $pollVoteCount);
    }

    /**
     * Return an array with the answers. The key is the answer ID and the value is the answer text.
     * @param   int     $pollId ID of the poll to get the answers of.
     * @return  Answers         Answers object with the answers.
     */
    private function getAnswers(int $pollId): Answers
    {
        $statement = $this->getClient()->prepare("SELECT `answer_id`, `text` FROM `answers` WHERE `poll_id` = ?");
        $statement->bind_param("i", $pollId);
        $statement->bind_result($id, $answer);
        $statement->execute();

        $answers = new Answers();

        while ($statement->fetch()) {
            $answers->setText($id, $answer);
        }

        $statement->close();

        return $answers;
    }

    /**
     * Return an array with the answers and the votes for each answer. The key is the answer ID.
     * The value is another array that contains the answer text and the vote count.
     * @param   int     $pollId     ID of the poll to get the answers for.
     * @param   Answers $answers    Array of the answers to get the vote counts for.
     * @return  Answers             Array with the answers and their vote counts.
     */
    private function getVoteCounts(int $pollId, Answers $answers): Answers
    {
        $statement = $this->getClient()->prepare("SELECT COUNT(*) FROM `votes` WHERE `poll_id` = ? AND `answer_id` = ?");
        $statement->bind_param("ii", $pollId, $answerId);
        $statement->bind_result($voteCount);

        foreach ($answers->getIds() as $answerId) {
            $statement->execute();
            $statement->fetch();
            $answers->setVoteCount($answerId, $voteCount);
        }

        $statement->close();

        return $answers;
    }

    private function getPollVoteCount(int $pollId): int
    {
        $statement = $this->getClient()->prepare("SELECT COUNT(*) FROM `votes` WHERE `poll_id` = ?");
        $statement->bind_param("i", $pollId);
        $statement->bind_result($voteCount);
        $statement->execute();
        $statement->fetch();
        $statement->close();
        return $voteCount;
    }
}
