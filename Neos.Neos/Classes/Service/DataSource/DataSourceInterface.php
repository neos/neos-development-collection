<?php
namespace Neos\Neos\Service\DataSource;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Projection\Content\NodeInterface;

/**
 * Data source interface for providing generic data
 *
 * This is used in the user interface to generate dynamic option lists.
 *
 * @api
 */
interface DataSourceInterface
{
    /**
     * @return string The identifier of the data source
     * @api
     */
    public static function getIdentifier();

    /**
     * Get data
     *
     * The return value must be JSON serializable data structure.
     *
     * @param NodeInterface $node The node that is currently edited (optional)
     * @param array<mixed> $arguments Additional arguments (key / value)
     * @return mixed JSON serializable data
     * @api
     */
    public function getData(NodeInterface $node = null, array $arguments = []);
}
