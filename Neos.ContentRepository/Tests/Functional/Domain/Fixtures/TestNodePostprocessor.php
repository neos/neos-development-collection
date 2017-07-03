<?php
namespace Neos\ContentRepository\Tests\Functional\Domain\Fixtures;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\NodeTypePostprocessor\NodeTypePostprocessorInterface;
use Neos\ContentRepository\Domain\Model\NodeType;

/**
 * An example NodePostprocessor used by the NodesTests
 */
class TestNodePostprocessor implements NodeTypePostprocessorInterface
{
    /**
     * @param NodeType $nodeType The (uninitialized) node type to process
     * @param array $configuration The configuration of the node type
     * @param array $options The processor options
     * @return void
     */
    public function process(NodeType $nodeType, array &$configuration, array $options)
    {
        if ($nodeType->isOfType('Neos.ContentRepository.Testing:NodeTypeWithProcessor')) {
            $someOption = isset($options['someOption']) ? $options['someOption'] : '';
            $someOtherOption = isset($options['someOtherOption']) ? $options['someOtherOption'] : '';
            $configuration['properties']['test1']['defaultValue'] = sprintf('The value of "someOption" is "%s", the value of "someOtherOption" is "%s"', $someOption, $someOtherOption);
        }
    }
}
