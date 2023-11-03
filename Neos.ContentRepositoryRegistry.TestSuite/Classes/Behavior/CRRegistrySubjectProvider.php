<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\TestSuite\Behavior;

/*
 * This file is part of the Neos.ContentRepositoryRegistry.TestSuite package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Exception\ContentRepositoryNotFoundException;
use Neos\EventStore\EventStoreInterface;

/**
 * The CR registry subject provider trait for behavioral tests
 */
trait CRRegistrySubjectProvider
{
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    protected ?ContentRepository $currentContentRepository = null;

    /**
     * @var array<ContentRepositoryId>
     */
    protected array $alreadySetUpContentRepositories = [];

    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    abstract protected function getObject(string $className): object;

    protected function setUpCRRegistry(): void
    {
        $this->contentRepositoryRegistry = $this->getObject(ContentRepositoryRegistry::class);
    }

    /**
     * @Given /^I initialize content repository "([^"]*)"$/
     */
    public function iInitializeContentRepository(string $contentRepositoryId): void
    {
        $contentRepository = $this->getContentRepository(ContentRepositoryId::fromString($contentRepositoryId));
        /** @var EventStoreInterface $eventStore */
        $eventStore = (new \ReflectionClass($contentRepository))->getProperty('eventStore')->getValue($contentRepository);
        /** @var Connection $databaseConnection */
        $databaseConnection = (new \ReflectionClass($eventStore))->getProperty('connection')->getValue($eventStore);
        $eventTableName = sprintf('cr_%s_events', $contentRepositoryId);
        $databaseConnection->executeStatement('TRUNCATE ' . $eventTableName);

        if (!in_array($contentRepository->id, $this->alreadySetUpContentRepositories)) {
            $contentRepository->setUp();
        }
        $contentRepository->resetProjectionStates();
    }

    /**
     * @throws \DomainException if the requested content repository instance does not exist
     */
    protected function getContentRepository(ContentRepositoryId $id): ContentRepository
    {
        try {
            return $this->contentRepositoryRegistry->get($id);
        } catch (ContentRepositoryNotFoundException $exception) {
            throw new \DomainException($exception->getMessage(), 1692343514, $exception);
        }
    }

    protected function getContentRepositoryService(
        ContentRepositoryServiceFactoryInterface $factory
    ): ContentRepositoryServiceInterface {
        return $this->contentRepositoryRegistry->buildService(
            $this->currentContentRepository->id,
            $factory
        );
    }
}
