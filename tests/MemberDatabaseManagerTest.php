<?php

namespace Compucie\DatabaseTest;

use Compucie\Database\Member\Exceptions\CardNotRegisteredException;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

class MemberDatabaseManagerTest extends TestCase
{
    private TestableMemberDatabaseManager $dbm;
    protected DbTestHelper $dbh;

    protected function setUp(): void
    {
        $env = parse_ini_file(".env", true);
        if ($env) {
            $this->dbm = new TestableMemberDatabaseManager($env['member']);
            $this->dbm->createTables();
            $this->dbh = new DbTestHelper($this->dbm->client());

            $this->dbh->truncateTables(['screen_birthdays', 'rfid']);
        }
    }

    protected function tearDown(): void
    {
        $this->dbh->truncateTables(['screen_birthdays','rfid']);
    }

    public function testGetMemberIdsWithBirthdayToday(): void
    {
        //implement insert
        assertSame(0, $this->dbh->rowCount('screen_birthdays')); //change to 1
    }

    public function testGetCongressusMemberIdFromCardIdNotFound(): void
    {
        $this->expectException(CardNotRegisteredException::class);
        $this->expectExceptionMessage("Card is not registered.");
        $this->dbm->getCongressusMemberIdFromCardId("deadbeaf");
        assertSame(0, $this->dbh->rowCount('rfid'));
    }

    /**
     * @throws CardNotRegisteredException
     */
    public function testGetCongressusMemberIdFromCardId(): void
    {
        $this->dbm->insertRfid("deadbeaf", 123);
        $congressusMemberId = $this->dbm->getCongressusMemberIdFromCardId("deadbeaf");
        assertSame(1, $this->dbh->rowCount('rfid'));
        assertSame(123, $congressusMemberId);
    }

    public function testIsRfidCardRegistered(): void
    {
        $this->dbm->insertRfid("deadbeaf", 123, true);
        assertSame(1, $this->dbh->rowCount('rfid'));
        $registered = $this->dbm->isRfidCardRegistered("deadbeaf");

        assertTrue($registered);
    }

    public function testIsRfidCardRegisteredNot(): void
    {
        assertSame(0, $this->dbh->rowCount('rfid'));
        $registered = $this->dbm->isRfidCardRegistered("deadbeaf");

        assertFalse($registered);
    }

    public function testInsertRfid(): void
    {
        $this->dbm->insertRfid("deadbeaf", 123);

        assertSame(1, $this->dbh->rowCount('rfid'));
        assertSame(1, $this->dbh->rowCount(
            'rfid',
            "card_id = 'deadbeaf' AND congressus_member_id = 123 AND is_email_confirmed = 0"
        ));
    }

    public function testInsertRfidEmailConfirmed(): void
    {
        $this->dbm->insertRfid("deadbeaf", 123, true);

        assertSame(1, $this->dbh->rowCount('rfid'));
        assertSame(1, $this->dbh->rowCount(
            'rfid',
            "card_id = 'deadbeaf' AND congressus_member_id = 123 AND is_email_confirmed = 1"
        ));
    }

    public function testDeleteMembersRfidRegistrations(): void
    {
        $this->dbm->insertRfid("deadbeaf", 123, true);

        assertSame(1, $this->dbh->rowCount('rfid'));

        $this->dbm->deleteMembersRfidRegistrations(123);

        assertSame(0, $this->dbh->rowCount('rfid'));
    }
}
