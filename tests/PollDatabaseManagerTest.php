<?php

namespace Compucie\DatabaseTest;

use DateInterval;
use DateTime;
use Exception;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

class PollDatabaseManagerTest extends TestCase
{
    private TestablePollDatabaseManager $dbm;
    protected DbTestHelper $dbh;

    protected function setUp(): void
    {
        $env = parse_ini_file(".env", true);
        if ($env) {
            $this->dbm = new TestablePollDatabaseManager($env['poll']);
            $this->dbm->createTables();
            $this->dbh = new DbTestHelper($this->dbm->client());

            $this->dbh->truncateTables(['options', 'polls', 'votes']);
        }
    }

    protected function tearDown(): void
    {
        $this->dbh->truncateTables(['options', 'polls', 'votes']);
    }

    function testGetActivePollIds(): void
    {
        $question1 = "Is this a poll?";
        $date1 = new DateTime();
        $this->dbm->addPoll($question1, $date1);

        $question2 = "Is this another poll?";
        $date2 = (new DateTime())->add(new DateInterval("PT1H"));
        $this->dbm->addPoll($question2, $date2);

        $question3 = "Is this even another poll?";
        $date3 = (new DateTime())->add(new DateInterval("PT2S"));
        $this->dbm->addPoll($question3, $date3);

        sleep(2);

        $ids = $this->dbm->getActivePollIds();

        assertSame(3, $this->dbh->rowCount('polls'));
        assertSame(0, $this->dbh->rowCount('options'));
        assertSame(0, $this->dbh->rowCount('votes'));
        assertSame(1, count($ids));
    }

    function testGetActivePolls(): void
    {
        $question1 = "Is this a poll?";
        $date1 = new DateTime();
        $this->dbm->addPoll($question1, $date1);

        $question2 = "Is this another poll?";
        $date2 = (new DateTime())->add(new DateInterval("PT1H"));
        $this->dbm->addPoll($question2, $date2);

        $question3 = "Is this even another poll?";
        $date3 = (new DateTime())->add(new DateInterval("PT2S"));
        $this->dbm->addPoll($question3, $date3);

        sleep(2);

        $polls = $this->dbm->getActivePolls();

        assertSame(3, $this->dbh->rowCount('polls'));
        assertSame(0, $this->dbh->rowCount('options'));
        assertSame(0, $this->dbh->rowCount('votes'));
        assertSame(1, count($polls));
        assertSame($question2, $polls[0]->getQuestion());

    }

    /**
     * @throws Exception
     */
    function testGetMostRecentlyExpiredPoll(): void
    {
        $question1 = "Is this a poll?";
        $date1 = new DateTime();
        $this->dbm->addPoll($question1, $date1);

        $question2 = "Is this another poll?";
        $date2 = (new DateTime())->add(new DateInterval("PT1H"));
        $this->dbm->addPoll($question2, $date2);

        $question3 = "Is this even another poll?";
        $date3 = (new DateTime())->add(new DateInterval("PT2S"));
        $this->dbm->addPoll($question3, $date3);

        sleep(5);

        $poll = $this->dbm->getMostRecentlyExpiredPoll();
        assertNotNull($poll);
        assertSame(3, $poll->getId());
    }

    function testGetLatestPolls(): void
    {
        $question1 = "Is this a poll?";
        $date1 = new DateTime();
        $this->dbm->addPoll($question1, $date1);

        $question2 = "Is this another poll?";
        $date2 = (new DateTime())->add(new DateInterval("PT1H"));
        $this->dbm->addPoll($question2, $date2);

        $question3 = "Is this even another poll?";
        $date3 = (new DateTime())->add(new DateInterval("PT2S"));
        $this->dbm->addPoll($question3, $date3);

        $polls = $this->dbm->getLatestPolls(2);
        assertSame(2, count($polls));
    }

    function testHasUserVoted(): void
    {
        $this->dbm->addPoll("Is this a poll?", new DateTime());
        $this->dbm->addOption(1, "Yes");
        $this->dbm->addOption(1, "No");

        $this->dbm->addVote(1, 1,1);
        $this->dbm->addVote(1, 2,2);
        $this->dbm->addVote(1, 3,1);

        $voted = $this->dbm->hasUserVoted(1, 2);
        $notVoted = $this->dbm->hasUserVoted(1, 4);

        assertSame(1, $this->dbh->rowCount('polls'));
        assertSame(2, $this->dbh->rowCount('options'));
        assertSame(3, $this->dbh->rowCount('votes'));

        assertTrue($voted);
        assertFalse($notVoted);
    }

    function testGetVotablePollIds(): void
    {
        $this->dbm->addPoll("Is this a poll?", (new DateTime())->add(new DateInterval("PT1H")));
        $this->dbm->addOption(1, "Yes");
        $this->dbm->addOption(1, "No");
        $this->dbm->addPoll("Is this another poll?", (new DateTime())->add(new DateInterval("PT1H")));
        $this->dbm->addOption(2, "Yes");
        $this->dbm->addOption(2, "No");

        $this->dbm->addVote(1, 1,1);
        $this->dbm->addVote(1, 2,2);
        $this->dbm->addVote(1, 3,1);

        $ids = $this->dbm->getVotablePollIds(2);

        assertSame(2, $this->dbh->rowCount('polls'));
        assertSame(4, $this->dbh->rowCount('options'));
        assertSame(3, $this->dbh->rowCount('votes'));

        assertSame(1, count($ids));
    }

    function testAddPoll(): void
    {
        //published_at has UTC timestamp
        $question1 = "Is this a poll?";
        $date1 = new DateTime();
        $this->dbm->addPoll($question1, $date1);

        assertSame(1, $this->dbh->rowCount('polls'));
        assertSame(0, $this->dbh->rowCount('options'));
        assertSame(0, $this->dbh->rowCount('votes'));
        assertSame(1, $this->dbh->rowCount(
            'polls', 'poll_id = 1 AND question = ? AND published_at IS NOT NULL AND expires_at = ?',[$question1, $date1->format('Y-m-d H:i:s')])
        );
    }

    function testAddOption(): void
    {
        $this->dbm->addPoll("Is this a poll?", new DateTime());

        $option1 = "Yes";
        $option2 = "No";
        $this->dbm->addOption(1, $option1);
        $this->dbm->addOption(1, $option2);

        assertSame(1, $this->dbh->rowCount('polls'));
        assertSame(2, $this->dbh->rowCount('options'));
        assertSame(0, $this->dbh->rowCount('votes'));
        assertSame(1, $this->dbh->rowCount(
            'options', 'option_id = 1 AND poll_id = 1 AND text = ?',[$option1])
        );
        assertSame(1, $this->dbh->rowCount(
            'options', 'option_id = 2 AND poll_id = 1 AND text = ?',[$option2])
        );
    }

    function testDeleteOption(): void
    {
        $this->dbm->addPoll("Is this a poll?", new DateTime());

        $option1 = "Yes";
        $option2 = "No";
        $this->dbm->addOption(1, $option1);
        $this->dbm->addOption(1, $option2);

        assertSame(1, $this->dbh->rowCount('polls'));
        assertSame(2, $this->dbh->rowCount('options'));
        assertSame(0, $this->dbh->rowCount('votes'));
        assertSame(1, $this->dbh->rowCount(
            'options', 'option_id = 1 AND poll_id = 1 AND text = ?',[$option1])
        );
        assertSame(1, $this->dbh->rowCount(
            'options', 'option_id = 2 AND poll_id = 1 AND text = ?',[$option2])
        );

        $this->dbm->deleteOption(1);

        assertSame(1, $this->dbh->rowCount('polls'));
        assertSame(1, $this->dbh->rowCount('options'));
        assertSame(0, $this->dbh->rowCount('votes'));
        assertSame(0, $this->dbh->rowCount(
            'options', 'option_id = 1 AND poll_id = 1 AND text = ?',[$option1])
        );
        assertSame(1, $this->dbh->rowCount(
            'options', 'option_id = 2 AND poll_id = 1 AND text = ?',[$option2])
        );
    }

    function testAddVote(): void
    {
        $this->dbm->addPoll("Is this a poll?", new DateTime());
        $this->dbm->addOption(1, "Yes");
        $this->dbm->addOption(1, "No");

        $this->dbm->addVote(1, 1,1);
        $this->dbm->addVote(1, 2,2);
        $this->dbm->addVote(1, 3,1);

        assertSame(1, $this->dbh->rowCount('polls'));
        assertSame(2, $this->dbh->rowCount('options'));
        assertSame(3, $this->dbh->rowCount('votes'));
        assertSame(1, $this->dbh->rowCount(
            'votes', 'vote_id = 1 AND poll_id = 1 AND user_id = 1 AND option_id = 1')
        );
        assertSame(1, $this->dbh->rowCount(
            'votes', 'vote_id = 2 AND poll_id = 1 AND user_id = 2 AND option_id = 2')
        );
        assertSame(1, $this->dbh->rowCount(
            'votes', 'vote_id = 3 AND poll_id = 1 AND user_id = 3 AND option_id = 1')
        );
    }

    /**
     * @throws Exception
     */
    function testGetPoll(): void
    {
        $question1 = "Is this a poll?";
        $date1 = new DateTime();
        $this->dbm->addPoll($question1, $date1);

        $option1 = "Yes";
        $option2 = "No";
        $this->dbm->addOption(1, $option1);
        $this->dbm->addOption(1, $option2);

        $this->dbm->addVote(1, 1,1);
        $this->dbm->addVote(1, 2,2);
        $this->dbm->addVote(1, 3,1);

        $poll = $this->dbm->getPoll(1);

        assertSame(1, $poll->getId());
        assertSame($question1, $poll->getQuestion());
//        echo $poll->getPublishedAt()->format('Y-m-d H:i:s');
//        echo $poll->getExpiresAt()->format('Y-m-d H:i:s');
        assertSame($date1->format('Y-m-d H:i:s'), $poll->getExpiresAt()->format('Y-m-d H:i:s'));
        $options = $poll->getOptions();
        assertSame(2, count($options->getIds()));
        assertSame($option1, $options->getText(1));
        assertSame(2, $options->getVoteCount(1));
        assertSame($option2, $options->getText(2));
        assertSame(1, $options->getVoteCount(2));
        assertSame(3, $poll->getVoteCount());
    }

    function testGetPollNotFound(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Poll 1 not found');
        $this->dbm->getPoll(1);
    }

    function testExpirePoll(): void
    {
        $question1 = "Is this a poll?";
        $date1 = new DateTime();
        $this->dbm->addPoll($question1, $date1);
        assertSame(1, $this->dbh->rowCount(
            'polls', 'poll_id = 1 AND question = ? AND published_at IS NOT NULL AND expires_at = ?',[$question1, $date1->format('Y-m-d H:i:s')])
        );
        sleep(2);
        $this->dbm->expirePoll(1);
        assertSame(1, $this->dbh->rowCount(
            'polls', 'poll_id = 1 AND question = ? AND published_at IS NOT NULL AND expires_at > ?',[$question1, $date1->format('Y-m-d H:i:s')])
        );
    }
}
