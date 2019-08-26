<?php
namespace Neos\ContentRepository\Domain\Model;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Interface for rendering a node label string based on some strategy
 *
 * @api
 */
interface NodeLabelGeneratorInterface
{
    /**
     * Render a node label
     *
     * @param \Neos\ContentRepository\Domain\Projection\Content\NodeInterface $node
     * @return string
     * @api
     */
    public function getLabel(\Neos\ContentRepository\Domain\Projection\Content\NodeInterface $node): string;
}
