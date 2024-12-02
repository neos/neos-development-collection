<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional\Subscription;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Neos\ContentRepository\BehavioralTests\TestSuite\DebugEventProjection;

final class ExternalProjectionErrorTest extends AbstractSubscriptionEngineTestCase
{
    use ProjectionRollbackTestTrait;

    static Connection $secondConnection;

    /** @before */
    public function injectExternalFakeProjection(): void
    {
        $entityManager = $this->getObject(EntityManagerInterface::class);

        if (!isset(self::$secondConnection)) {
            self::$secondConnection = DriverManager::getConnection(
                $entityManager->getConnection()->getParams(),
                $entityManager->getConfiguration(),
                $entityManager->getEventManager()
            );
        }

        $this->secondFakeProjection = new DebugEventProjection(
            sprintf('cr_%s_debug_projection', self::$contentRepositoryId->value),
            self::$secondConnection
        );
    }
}
