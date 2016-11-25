<?php
namespace Neos\ContentRepository\NodeTypePostprocessor;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeType;

/**
 * A NodeType postprocessor can be used in order to programmatically change the configuration of a node type
 * for example to provide dynamic properties.
 */
interface NodeTypePostprocessorInterface
{
    /**
     * Processes the given $nodeType (e.g. changes/adds properties depending on the NodeType configuration and the specified $options)
     *
     * @param NodeType $nodeType (uninitialized) The node type to process
     * @param array $configuration The node type configuration to be processed
     * @param array $options The processor options
     * @return void
     */
    public function process(NodeType $nodeType, array &$configuration, array $options);
}
