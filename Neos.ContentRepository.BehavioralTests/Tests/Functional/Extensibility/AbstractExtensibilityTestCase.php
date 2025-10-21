<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional\Extensibility;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Neos\ContentRepository\Core\CommandHandler\CommandHookInterface;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainer;
use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainerFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\TestSuite\Fakes\FakeCommandHookFactory;
use Neos\ContentRepository\TestSuite\Fakes\FakeContentDimensionSourceFactory;
use Neos\ContentRepository\TestSuite\Fakes\FakeNodeTypeManagerFactory;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Core\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal, only for tests of the Neos.* namespace
 */
abstract class AbstractExtensibilityTestCase extends TestCase // we don't use Flows functional test case as it would reset the database afterwards
{
    protected static ContentRepositoryId $contentRepositoryId;

    protected ContentRepository $contentRepository;

    protected CommandHookInterface&MockObject $fakeCommandHook;

    public static function setUpBeforeClass(): void
    {
        static::$contentRepositoryId = ContentRepositoryId::fromString('t_extensibility');
    }

    public function setUp(): void
    {
        if ($this->getObject(Connection::class)->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            $this->markTestSkipped('TODO: The content graph is not available in postgres currently: https://github.com/neos/neos-development-collection/issues/3855');
        }

        $this->fakeCommandHook = $this->getMockBuilder(CommandHookInterface::class)->disableAutoReturnValueGeneration()->getMock();

        FakeCommandHookFactory::setCommandHook(
            $this->fakeCommandHook
        );

        FakeNodeTypeManagerFactory::setConfiguration([
            'Neos.ContentRepository:Root' => [],
            'Neos.ContentRepository.Testing:Document' => [
                'properties' => [
                    'title' => [
                        'type' => 'string'
                    ]
                ]
            ]
        ]);
        FakeContentDimensionSourceFactory::setWithoutDimensions();

        $this->getObject(ContentRepositoryRegistry::class)->resetFactoryInstance(static::$contentRepositoryId);

        /** @var ContentRepositoryMaintainer $contentRepositoryMaintainer */
        $contentRepositoryMaintainer = $this->getObject(ContentRepositoryRegistry::class)->buildService(static::$contentRepositoryId, new ContentRepositoryMaintainerFactory());
        $contentRepositoryMaintainer->setUp();
        // reset events and projections
        $contentRepositoryMaintainer->prune();

        $this->contentRepository = $this->getObject(ContentRepositoryRegistry::class)->get(static::$contentRepositoryId);
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    final protected function getObject(string $className): object
    {
        return Bootstrap::$staticObjectManager->get($className);
    }
}
