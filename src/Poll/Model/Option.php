<?php

namespace Compucie\Database\Poll\Model;

/**
 * Dataclass
 */
readonly class Option
{
    private int    $voteCount;

    public function __construct(
        private int    $id,
        private int    $pollId,
        private string $text,
        private array   $votes,
    )
    {
        $this->voteCount = count($this->votes);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getPollId(): int
    {
        return $this->pollId;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @return array<Vote>
     */
    public function getVotes(): array
    {
        return $this->votes;
    }

    /**
     * @return int
     */
    public function getVoteCount(): int
    {
        return $this->voteCount;
    }
}