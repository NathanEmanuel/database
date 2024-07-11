<?php

namespace Compucie\DatabaseManager\Tests;

use Compucie\DatabaseManagers\PollDatabaseManager;
use PHPUnit\Framework\TestCase;
#require_once('../vendor/autoload.php');
final class PollDatabaseManagerTest extends TestCase
{

    private PollDatabaseManager $dbm;

    protected function setUp(): void
    {
        $this->dbm = new PollDatabaseManager("config/test.ini");
    }

    public function testGetActivePolls(): void
    {
        
        $polls = $this->dbm->getActivePolls();
        $this->assertSame(1, $polls[0]->getId());
    }
    public function testAnswers(): void
    {
        $answers = $this->dbm->getPoll(1)->getAnswers();

        $this->assertSame([1, 2], $answers->getIds());

        $this->assertSame("Yes", $answers->getText(1));
        $this->assertSame(1, $answers->getVoteCount(1));

        $this->assertSame("No", $answers->getText(2));
        $this->assertSame(0, $answers->getVoteCount(2));
    }

    public function testVoteCount(): void
    {
        $this->assertSame(1, $this->dbm->getPoll(1)->getVoteCount());
    }
}
