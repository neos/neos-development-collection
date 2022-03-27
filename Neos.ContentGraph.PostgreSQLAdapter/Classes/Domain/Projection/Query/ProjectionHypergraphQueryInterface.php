<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Query;

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
use Doctrine\DBAL\Driver\ResultStatement;

/**
 * The interface to be implemented by projection hypergraph queries
 *
 * @internal
 */
interface ProjectionHypergraphQueryInterface
{
    /**
     * @return ResultStatement<int,mixed>
     */
    public function execute(Connection $databaseConnection): ResultStatement;
}
