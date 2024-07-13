<?php

namespace Compucie\Database\Poll\Model;

class Answers
{

    public function __construct(
        private ?array $ids = null,
        private ?array $texts = null,
        private ?array $voteCounts = null,
    ) {
        $this->ids = array();
    }

    public function getText(int $id): string
    {
        return $this->texts[$id];
    }

    public function getVoteCount(int $id): int
    {
        return $this->voteCounts[$id];
    }

    public function setText(int $id, string $text): void
    {
        $this->texts[$id] = $text;
        $this->registerId($id);
    }

    public function setVoteCount(int $id, int $voteCount): void
    {
        $this->voteCounts[$id] = $voteCount;
        $this->registerId($id);
    }

    public function getIds()
    {
        return $this->ids;
    }

    private function registerId(int $id): void
    {
        if (!in_array($id, $this->ids)) {
            $this->ids[] = $id;
        }
    }
}
