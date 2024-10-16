<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Service;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Projection\CatchUpOptions;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\Projections;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventStream\VirtualStreamName;

/**
 * @api
 */
class ProjectionService implements ContentRepositoryServiceInterface
{
    public function __construct(
        private readonly Projections $projections,
        private readonly ContentRepository $contentRepository,
        private readonly EventStoreInterface $eventStore
    ) {
    }

    public function replayProjection(string $projectionAliasOrClassName, CatchUpOptions $options): void
    {
        $projection = $this->resolveProjection($projectionAliasOrClassName);
        $projection->reset();
        $this->contentRepository->catchUpProjection($projection::class, $options);
    }

    public function replayAllProjections(CatchUpOptions $options, ?\Closure $progressCallback = null): void
    {
        foreach ($this->projectionClassNamesAndAliases() as $classNamesAndAlias) {
            if ($progressCallback) {
                $progressCallback($classNamesAndAlias['alias']);
            }
            $projection = $this->projections->get($classNamesAndAlias['className']);
            $projection->reset();
            $this->contentRepository->catchUpProjection($projection::class, $options);
        }
    }

    public function resetAllProjections(): void
    {
        $this->projections->resetAll();
    }

    public function highestSequenceNumber(): SequenceNumber
    {
        foreach ($this->eventStore->load(VirtualStreamName::all())->backwards()->limit(1) as $eventEnvelope) {
            return $eventEnvelope->sequenceNumber;
        }
        return SequenceNumber::none();
    }

    public function numberOfProjections(): int
    {
        return count($this->projections);
    }

    /**
     * @return ProjectionInterface<ProjectionStateInterface>
     */
    private function resolveProjection(string $projectionAliasOrClassName): ProjectionInterface
    {
        $lowerCaseProjectionName = strtolower($projectionAliasOrClassName);
        $projectionClassNamesAndAliases = $this->projectionClassNamesAndAliases();
        foreach ($projectionClassNamesAndAliases as $classNamesAndAlias) {
            if (strtolower($classNamesAndAlias['className']) === $lowerCaseProjectionName || strtolower($classNamesAndAlias['alias']) === $lowerCaseProjectionName) {
                return $this->projections->get($classNamesAndAlias['className']);
            }
        }
        throw new \InvalidArgumentException(sprintf(
            'The projection "%s" is not registered for this Content Repository. The following projection aliases (or fully qualified class names) can be used: %s',
            $projectionAliasOrClassName,
            implode('', array_map(static fn (array $classNamesAndAlias) => sprintf(chr(10) . ' * %s (%s)', $classNamesAndAlias['alias'], $classNamesAndAlias['className']), $projectionClassNamesAndAliases))
        ), 1680519624);
    }

    /**
     * @return array<array{className: class-string<ProjectionInterface<ProjectionStateInterface>>, alias: string}>
     */
    private function projectionClassNamesAndAliases(): array
    {
        return array_map(
            static fn (string $projectionClassName) => [
                'className' => $projectionClassName,
                'alias' => self::projectionAlias($projectionClassName),
            ],
            $this->projections->getClassNames()
        );
    }

    private static function projectionAlias(string $className): string
    {
        $alias = lcfirst(substr(strrchr($className, '\\') ?: '\\' . $className, 1));
        if (str_ends_with($alias, 'Projection')) {
            $alias = substr($alias, 0, -10);
        }
        return $alias;
    }
}
