<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\SharedModel\NodeType;

/**
 * A NodeType postprocessor can be used in order to programmatically change the configuration of a node type
 * for example to provide dynamic properties.
 *
 * @api
 */
interface NodeTypePostprocessorInterface
{
    /**
     * Processes the given $nodeType
     * (e.g. changes/adds properties depending on the NodeType configuration and the specified $options)
     *
     * @param NodeType $nodeType (uninitialized) The node type to process
     * @param array<string,mixed> $configuration The node type configuration to be processed
     * @param array<string,mixed> $options The processor options
     * @return void
     */
    public function process(NodeType $nodeType, array &$configuration, array $options);
}
