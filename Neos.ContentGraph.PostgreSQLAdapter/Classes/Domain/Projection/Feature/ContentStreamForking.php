<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature;

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HierarchyHyperrelationRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\RestrictionHyperrelationRecord;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Event\ContentStreamWasForked;

/**
 * The content stream forking feature set for the hypergraph projector
 */
trait ContentStreamForking
{
    /**
     * @throws \Throwable
     */
    public function whenContentStreamWasForked(ContentStreamWasForked $event): void
    {
        $this->transactional(function () use ($event) {
            $parameters = [
                'sourceContentStreamIdentifier' => (string)$event->getSourceContentStreamIdentifier(),
                'targetContentStreamIdentifier' => (string)$event->getContentStreamIdentifier()
            ];

            $this->getDatabaseConnection()->executeQuery(/** @lang PostgreSQL */
                'INSERT INTO ' . HierarchyHyperrelationRecord::TABLE_NAME . '
                    (contentstreamidentifier, parentnodeanchor,
                     dimensionspacepoint, dimensionspacepointhash, childnodeanchors)
                SELECT :targetContentStreamIdentifier, parentnodeanchor,
                    dimensionspacepoint, dimensionspacepointhash, childnodeanchors
                FROM ' . HierarchyHyperrelationRecord::TABLE_NAME . ' source
                WHERE source.contentstreamidentifier = :sourceContentStreamIdentifier',
                $parameters
            );

            $this->getDatabaseConnection()->executeQuery(/** @lang PostgreSQL */
                'INSERT INTO ' . RestrictionHyperrelationRecord::TABLE_NAME . '
                    (contentstreamidentifier, dimensionspacepointhash,
                     originnodeaggregateidentifier, affectednodeaggregateidentifiers)
                SELECT :targetContentStreamIdentifier, dimensionspacepointhash,
                    originnodeaggregateidentifier, affectednodeaggregateidentifiers
                FROM ' . RestrictionHyperrelationRecord::TABLE_NAME . ' source
                WHERE source.contentstreamidentifier = :sourceContentStreamIdentifier',
                $parameters
            );
        });
    }

    /**
     * @throws \Throwable
     */
    abstract protected function transactional(callable $operations): void;

    abstract protected function getDatabaseConnection(): Connection;
}
