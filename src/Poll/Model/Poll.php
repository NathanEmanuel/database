<?php

namespace Compucie\Database\Poll\Model;

use DateTime;

/**
 * Dataclass
 */
class Poll
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
