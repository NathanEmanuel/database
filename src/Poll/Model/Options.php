<?php

namespace Compucie\Database\Poll\Model;

class Options
{
    /**
     * @var array<int> $ids
     */
    private array $ids;
    /**
     * @var array<string> $texts
     */
    private array $texts;
    /**
     * @var array<int> $voteCounts
     */
    private array $voteCounts;

    /**
     * @param array<int> $ids
     * @param array<string> $texts
     * @param array<int> $voteCounts
     */
    public function __construct(
        array $ids = array(),
        array $texts = array(),
        array $voteCounts = array(),
    ) {
        $this->ids = array();
        $this->texts = array();
        $this->voteCounts = array();

        foreach ($ids as $id) {
            $this->registerId($id);
        }

        foreach ($texts as $id => $text) {
            $this->setText((int)$id, (string)$text);
        }

        foreach ($voteCounts as $id => $count) {
            $this->setVoteCount((int)$id, (int)$count);
        }
    }

    public function getText(int $id): string
    {
        return $this->texts[$id] ?? '';
    }

    public function getVoteCount(int $id): int
    {
        return $this->voteCounts[$id] ?? 0;
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

    /**
     * @return array<int>
     */
    public function getIds(): array
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
