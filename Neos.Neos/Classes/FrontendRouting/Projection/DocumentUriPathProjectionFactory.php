<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\Projection;

use Neos\ContentRepository\Factory\ProjectionFactoryDependencies;
use Neos\ContentRepository\Projection\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Projection\ProjectionFactoryInterface;
use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Projection\Projections;

/**
 * @implements ProjectionFactoryInterface<DocumentUriPathProjection>
 */
final class DocumentUriPathProjectionFactory implements ProjectionFactoryInterface
{
    public function __construct(
        private readonly Connection $dbal
    ) {
    }

    public function build(
        ProjectionFactoryDependencies $projectionFactoryDependencies,
        array $options,
        CatchUpHookFactoryInterface $catchUpHookFactory,
        Projections $projectionsSoFar
    ): DocumentUriPathProjection {
        $projectionShortName = strtolower(str_replace(
            'Projection',
            '',
            (new \ReflectionClass(DocumentUriPathProjection::class))->getShortName()
        ));
        return new DocumentUriPathProjection(
            $projectionFactoryDependencies->eventNormalizer,
            $projectionFactoryDependencies->nodeTypeManager,
            $this->dbal,
            sprintf(
                'neos_cr_%s_projection_%s',
                $projectionFactoryDependencies->contentRepositoryIdentifier,
                $projectionShortName
            ),
        );
    }
}
