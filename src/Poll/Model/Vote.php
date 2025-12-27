<?php

namespace Compucie\Database\Poll\Model;

use DateTime;

/**
 * Dataclass
 */
readonly class Vote
{
    public function __construct(
        private int      $id,
        private int      $pollId,
        private int      $optionId,
        private int      $userId,
        private DateTime $published_at
    ) {
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
     * @return int
     */
    public function getOptionId(): int
    {
        return $this->optionId;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return DateTime
     */
    public function getPublishedAt(): DateTime
    {
        return $this->published_at;
    }
}