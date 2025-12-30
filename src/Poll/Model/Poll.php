<?php

namespace Compucie\Database\Poll\Model;

use DateTime;

/**
 * Dataclass
 */
readonly class Poll
{
    /**
     * @param int $id
     * @param string $question
     * @param DateTime $publishedAt
     * @param DateTime $expiresAt
     * @param array<Option> $options
     * @param int $voteCount
     */
    public function __construct(
        private int      $id,
        private string   $question,
        private DateTime $publishedAt,
        private DateTime $expiresAt,
        private array  $options,
        private int      $voteCount,
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

    /**
     * @return array<Option>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getVoteCount(): int
    {
        return $this->voteCount;
    }
}
