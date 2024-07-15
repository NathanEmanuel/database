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
        private DateTime $publishedAt,
        private DateTime $expiresAt,
        private Options $options,
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

    public function getPublishedAt(): DateTime
    {
        return $this->publishedAt;
    }

    public function getExpiresAt(): DateTime
    {
        return $this->expiresAt;
    }

    public function getOptions(): Options
    {
        return $this->options;
    }

    public function getVoteCount(): int
    {
        return $this->voteCount;
    }
}
