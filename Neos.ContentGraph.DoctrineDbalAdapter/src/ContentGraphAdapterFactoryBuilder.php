<?php
namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentRepository\Core\Factory\ProjectionFactoryDependencies;
use Neos\ContentRepository\Core\Feature\ContentGraphAdapterFactoryBuilderInterface;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;

/**
 * Builder to combine injected dependencies and ProjectionFActoryDependencies into a ContentGraphAdapterFactory
 * @internal
 */
class ContentGraphAdapterFactoryBuilder implements ContentGraphAdapterFactoryBuilderInterface
{
    public function __construct(private readonly DbalClientInterface $dbalClient)
    {
    }

    public function build(ProjectionFactoryDependencies $projectionFactoryDependencies): ContentGraphAdapterFactory
    {
        return new ContentGraphAdapterFactory($this->dbalClient->getConnection(), $projectionFactoryDependencies);
    }
}
