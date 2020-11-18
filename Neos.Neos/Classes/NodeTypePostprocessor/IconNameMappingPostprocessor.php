<?php
namespace Neos\Neos\NodeTypePostprocessor;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\NodeTypePostprocessor\NodeTypePostprocessorInterface;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Neos\Service\IconNameMappingService;
use Neos\Utility\Arrays;

/**
 * Map all icon- prefixed icon names to the corresponding
 * names in the used icon implementation
 */
class IconNameMappingPostprocessor implements NodeTypePostprocessorInterface
{
    /**
     * @Flow\Inject
     * @var IconNameMappingService
     */
    protected $iconNameMappingService;

    /**
     * @param NodeType $nodeType (uninitialized) The node type to process
     * @param array $configuration input configuration
     * @param array $options The processor options
     * @return void
     */
    public function process(NodeType $nodeType, array &$configuration, array $options): void
    {
        if (isset($configuration['ui']['icon'])) {
            $configuration['ui']['icon'] = $this->iconNameMappingService->convert($configuration['ui']['icon']);
        }

        $inspectorConfiguration = Arrays::getValueByPath($configuration, 'ui.inspector');
        if (is_array($inspectorConfiguration)) {
            foreach ($inspectorConfiguration as $elementTypeName => $elementTypeItems) {
                foreach ($elementTypeItems as $elementName => $elementConfiguration) {
                    if (isset($inspectorConfiguration[$elementTypeName][$elementName]['icon'])) {
                        $configuration['ui']['inspector'][$elementTypeName][$elementName]['icon'] = $this->iconNameMappingService->convert($inspectorConfiguration[$elementTypeName][$elementName]['icon']);
                    }
                }
            }
        }
    }
}
