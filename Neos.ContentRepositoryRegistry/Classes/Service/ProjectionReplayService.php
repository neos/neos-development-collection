<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Service;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Projection\Projections;

/**
 * Content Repository service to perform Projection replays
 *
 * @internal this is currently only used by the {@see CrCommandController}
 */
final class ProjectionReplayService implements ContentRepositoryServiceInterface
{

    public function __construct(
        private readonly Projections $projections,
        private readonly ContentRepository $contentRepository,
    ) {
    }

    public function replayProjection(string $projectionAliasOrClassName): void
    {
        $projectionClassName = $this->resolveProjectionClassName($projectionAliasOrClassName);
        $this->contentRepository->resetProjectionState($projectionClassName);
        $this->contentRepository->catchUpProjection($projectionClassName);
    }

    public function replayAllProjections(): void
    {
        foreach ($this->projectionClassNamesAndAliases() as $classNamesAndAlias) {
            $this->contentRepository->resetProjectionState($classNamesAndAlias['className']);
            $this->contentRepository->catchUpProjection($classNamesAndAlias['className']);
        }
    }

    private function resolveProjectionClassName(string $projectionAliasOrClassName): string
    {
        $lowerCaseProjectionName = strtolower($projectionAliasOrClassName);
        $projectionClassNamesAndAliases = $this->projectionClassNamesAndAliases();
        foreach ($projectionClassNamesAndAliases as $classNamesAndAlias) {
            if (strtolower($classNamesAndAlias['className']) === $lowerCaseProjectionName || strtolower($classNamesAndAlias['alias']) === $lowerCaseProjectionName) {
                return $classNamesAndAlias['className'];
            }
        }
        throw new \InvalidArgumentException(sprintf(
            'The projection "%s" is not registered for this Content Repository. The following projection aliases (or fully qualified class names) can be used: %s',
            $projectionAliasOrClassName,
            implode('', array_map(static fn (array $classNamesAndAlias) => sprintf(chr(10) . ' * %s (%s)', $classNamesAndAlias['alias'], $classNamesAndAlias['className']), $projectionClassNamesAndAliases))
        ), 1680519624);
    }

    /**
     * @return array<array{className: class-string, alias: string}>
     */
    private function projectionClassNamesAndAliases(): array
    {
        return array_map(
            static fn (string $projectionClassName) => [
                'className' => $projectionClassName,
                'alias' => self::projectionAlias($projectionClassName),
            ],
            array_keys(iterator_to_array($this->projections))
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
