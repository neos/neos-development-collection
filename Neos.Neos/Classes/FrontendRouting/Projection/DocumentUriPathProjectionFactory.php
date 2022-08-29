<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\Projection;

use Neos\ContentRepository\Core\Factory\ContentRepositoryIdentifier;
use Neos\ContentRepository\Core\Factory\ProjectionFactoryDependencies;
use Neos\ContentRepository\Core\Projection\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Core\Projection\ProjectionFactoryInterface;
use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Projection\Projections;

/**
 * @implements ProjectionFactoryInterface<DocumentUriPathProjection>
 */
final class DocumentUriPathProjectionFactory implements ProjectionFactoryInterface
{
    public function __construct(
        private readonly Connection $dbal
    ) {
    }

    public static function projectionTableNamePrefix(
        ContentRepositoryIdentifier $contentRepositoryIdentifier
    ): string {
        $projectionShortName = strtolower(str_replace(
            'Projection',
            '',
            (new \ReflectionClass(DocumentUriPathProjection::class))->getShortName()
        ));

        return sprintf(
            'cr_%s_p_neos_%s',
            $contentRepositoryIdentifier,
            $projectionShortName
        );
    }


    public function build(
        ProjectionFactoryDependencies $projectionFactoryDependencies,
        array $options,
        CatchUpHookFactoryInterface $catchUpHookFactory,
        Projections $projectionsSoFar
    ): DocumentUriPathProjection {

        return new DocumentUriPathProjection(
            $projectionFactoryDependencies->eventNormalizer,
            $projectionFactoryDependencies->nodeTypeManager,
            $this->dbal,
            self::projectionTableNamePrefix($projectionFactoryDependencies->contentRepositoryIdentifier)
        );
    }
}
