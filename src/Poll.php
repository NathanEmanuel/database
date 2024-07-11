<?php

namespace Compucie\DatabaseManagers;

use Compucie\DatabaseManagers\DatabaseManager;
use DateTime;

/**
 * Dataclass
 */
class Poll extends DatabaseManager
{
    public function __construct(
        private int $id,
        private string $question,
        private DateTime $published,
        private DateTime $expiry,
        private Answers $answers,
        private int $voteCount,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function getPublished(): DateTime
    {
        return $this->published;
    }

    public function getExpiry(): DateTime
    {
        return $this->expiry;
    }

    public function getAnswers(): Answers
    {
        return $this->answers;
    }

    public function getVoteCount(): int
    {
        return $this->voteCount;
    }
}
