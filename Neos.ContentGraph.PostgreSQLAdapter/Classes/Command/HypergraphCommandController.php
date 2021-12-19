<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Command;

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\ContentHypergraph;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\Flow\Cli\CommandController;

final class HypergraphCommandController extends CommandController
{
    private ContentHypergraph $hypergraph;

    public function __construct(ContentHypergraph $hypergraph)
    {
        $this->hypergraph = $hypergraph;
        parent::__construct();
    }

    public function findNodeAggregateCommand(): void
    {
        $nodeAggregate = $this->hypergraph->findNodeAggregateByIdentifier(
            ContentStreamIdentifier::fromString('cs-identifier'),
            NodeAggregateIdentifier::fromString('sir-david-nodenborough')
        );

        \Neos\Flow\var_dump($nodeAggregate);
    }
}
