<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional\Subscription;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\LockWaitTimeoutException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaDiff;
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaFactory;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\Subscription\ProjectionSubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\SubscriptionError;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;

final class ProjectionWithDeadlockTest extends AbstractSubscriptionEngineTestCase
{
    private const TABLE = 'cr_t_subscription_debug_projection_deadlock';

    static Connection $secondConnection;

    /** @before */
    public function createSecondConnection(): void
    {
        if (!isset(self::$secondConnection)) {
            $entityManager = $this->getObject(EntityManagerInterface::class);
            self::$secondConnection = DriverManager::getConnection(
                $entityManager->getConnection()->getParams(),
                $entityManager->getConfiguration(),
                $entityManager->getEventManager()
            );
        }
    }

    /** @before */
    public function fakeTableSetup()
    {
        $dbal = $this->getObject(Connection::class);

        $table = new Table(self::TABLE, [
            (new Column('test_key', Type::getType(Types::STRING))),
            (new Column('test_value', Type::getType(Types::STRING)))
        ]);
        $table->setPrimaryKey(['test_key']);

        $schema = DbalSchemaFactory::createSchemaWithTables($dbal, [$table]);
        foreach (DbalSchemaDiff::determineRequiredSqlStatements($dbal, $schema) as $statement) {
            $dbal->executeStatement($statement);
        }
        $dbal->executeStatement('TRUNCATE ' . self::TABLE);
    }

    // /** @test */
    public function playgroundForDeadlocks()
    {
        // max_statement_time can also crash nicely!!

        $dbal1 = $this->getObject(Connection::class);
        $dbal2 = self::$secondConnection;

        // $dbal1->executeStatement('SET SESSION tx_isolation = \'read-committed\';');
        // $dbal2->executeStatement('SET SESSION tx_isolation = \'read-committed\';');
        // $dbal2->executeStatement('set transaction-isolation=READ-COMITTED');

        // todo doesnt work $dbal1->executeStatement('set SESSION lock_wait_timeout = 10');
        // todo doesnt work $dbal2->executeStatement('set SESSION lock_wait_timeout = 10');

        // see https://stackoverflow.com/questions/7813321/how-to-deliberately-cause-a-deadlock
        $dbal1->insert(self::TABLE, [
            'test_key' => 'A',
            'test_value' => 'initial'
        ]);
        $dbal1->insert(self::TABLE, [
            'test_key' => 'B',
            'test_value' => 'initial'
        ]);

        $dbal1->beginTransaction();
        $dbal2->beginTransaction();

        $dbal1->update(self::TABLE, [
            'test_value' => 'updated first, dbal 1'
        ], [
            'test_key' => 'A'
        ]);

        $dbal2->update(self::TABLE, [
            'test_value' => 'updated first, dbal 2'
        ], [
            'test_key' => 'B'
        ]);

        // causes Doctrine\DBAL\Exception\LockWaitTimeoutException: An exception occurred while executing a query: SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded; try restarting transaction
        $dbal1->update(self::TABLE, [
            'test_value' => 'updated second, dbal 1'
        ], [
            'test_key' => 'B'
        ]);

        // not necessary
        // $dbal2->update(self::TABLE, [
        //     'test_value' => 'updated second, dbal 2'
        // ], [
        //     'test_key' => 'A'
        // ]);
    }

    /** @test */
    public function dbalTransactionTimesoutOnApply()
    {
        // Test for
        // Failed to insert hierarchy relation: An exception occurred while executing a query: SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction

        // $this->markTestSkipped('too slow because timeout is to long and i couldnt change it');

        $dbal1 = $this->getObject(Connection::class);
        $dbal2 = self::$secondConnection;
        $dbal1->insert(self::TABLE, [
            'test_key' => 'A',
            'test_value' => 'initial'
        ]);
        $dbal1->insert(self::TABLE, [
            'test_key' => 'B',
            'test_value' => 'initial'
        ]);

        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::any())->method('status')->willReturn(ProjectionStatus::ok());
        $this->fakeProjection->expects(self::exactly(1))->method('apply');
        $this->subscriptionEngine->setup();
        $this->subscriptionEngine->boot();

        // commit an event
        $this->commitExampleContentStreamEvent();

        // catchup active tries to apply the commited event
        $exception = null;
        $this->secondFakeProjection->injectSaboteur(function (EventEnvelope $eventEnvelope) use ($dbal1, $dbal2, &$exception) {
            self::assertEquals(SequenceNumber::fromInteger(1), $eventEnvelope->sequenceNumber);
            self::assertEquals('ContentStreamWasCreated', $eventEnvelope->event->type->value);
            // $dbal1 is in a transaction
            $dbal1->update(self::TABLE, [
                'test_value' => 'updated first, dbal 1'
            ], [
                'test_key' => 'A'
            ]);

            $dbal2->beginTransaction();
            $dbal2->update(self::TABLE, [
                'test_value' => 'updated first, dbal 2'
            ], [
                'test_key' => 'B'
            ]);

            try {
                $dbal1->update(self::TABLE, [
                    'test_value' => 'updated second, dbal 1'
                ], [
                    'test_key' => 'B'
                ]);
            } catch (\Throwable $e) {
                $exception = $e;
                throw $e;
            }
        });


        $result = $this->subscriptionEngine->catchUpActive();
        self::assertTrue($result->hadErrors());

        self::assertStringContainsString('1205 Lock wait timeout exceeded; try restarting transaction', $exception->getMessage());
        self::assertInstanceOf(LockWaitTimeoutException::class, $exception);
        self::assertSame($result->errors->first()->throwable, $exception);

        $expectedFailure = ProjectionSubscriptionStatus::create(
            subscriptionId: SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
            subscriptionStatus: SubscriptionStatus::ERROR,
            subscriptionPosition: SequenceNumber::fromInteger(0),
            subscriptionError: SubscriptionError::fromPreviousStatusAndException(SubscriptionStatus::ACTIVE, $exception),
            setupStatus: ProjectionStatus::ok(),
        );

        // the transaction was never really dead, thats why the subscription status was commited
        self::assertEquals($expectedFailure, $this->subscriptionStatus('Vendor.Package:SecondFakeProjection'));

        // ... and also previously commited values
        self::assertEquals(
            [1],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumberValues()
        );

        // ... including everything until the dead-lock
        self::assertEquals([
            [
                'test_key' => 'A',
                'test_value' => 'updated first, dbal 1'
            ],
            [
                'test_key' => 'B',
                'test_value' => 'initial'
            ]
        ], $dbal1->fetchAllAssociative('SELECT * FROM ' . self::TABLE));
    }
}
