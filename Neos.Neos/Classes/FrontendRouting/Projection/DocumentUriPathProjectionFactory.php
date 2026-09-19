<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\Projection;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Factory\SubscriberFactoryDependencies;
use Neos\ContentRepository\Core\Projection\ProjectionFactoryInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;

/**
 * @implements ProjectionFactoryInterface<DocumentUriPathProjection>
 * @internal implementation detail to manage document node uris. For resolving please use the NodeUriBuilder and for matching the Router.
 */
final class DocumentUriPathProjectionFactory implements ProjectionFactoryInterface
{
    public function __construct(
        private readonly Connection $dbal
    ) {
    }

    public static function projectionTableNamePrefix(
        ContentRepositoryId $contentRepositoryId
    ): string {
        $projectionShortName = strtolower(
            str_replace(
                'Projection',
                '',
                (new \ReflectionClass(DocumentUriPathProjection::class))->getShortName()
            )
        );

        return sprintf('cr_%s_p_neos_%s', $contentRepositoryId->value, $projectionShortName);
    }


    public function build(
        SubscriberFactoryDependencies $projectionFactoryDependencies,
        array $options,
    ): DocumentUriPathProjection {

        return new DocumentUriPathProjection(
            $projectionFactoryDependencies->nodeTypeManager,
            $this->dbal,
            self::projectionTableNamePrefix($projectionFactoryDependencies->contentRepositoryId),
        );
    }
}
