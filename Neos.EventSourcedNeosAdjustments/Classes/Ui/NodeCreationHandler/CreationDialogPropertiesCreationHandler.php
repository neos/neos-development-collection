<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Ui\NodeCreationHandler;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeAggregateWithNode;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\TypeHandling;

/**
 * Generic creation dialog node creation handler that iterates
 * properties that are configured to appear in the Creation Dialog (via "ui.showInCreationDialog" setting)
 * and sets the initial property values accordingly
 */
class CreationDialogPropertiesCreationHandler implements NodeCreationHandlerInterface
{

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    public function handle(CreateNodeAggregateWithNode $command, array $data): CreateNodeAggregateWithNode
    {
        $nodeType = $this->nodeTypeManager->getNodeType($command->getNodeTypeName()->getValue());
        $propertyValues = $command->getInitialPropertyValues();
        foreach ($nodeType->getConfiguration('properties') as $propertyName => $propertyConfiguration) {
            if (!isset($propertyConfiguration['ui']['showInCreationDialog']) || $propertyConfiguration['ui']['showInCreationDialog'] !== true) {
                continue;
            }
            $propertyType = TypeHandling::normalizeType($propertyConfiguration['type'] ?? 'string');
            if (!isset($data[$propertyName])) {
                continue;
            }
            $propertyValue = $data[$propertyName];
            if ($propertyValue === '' && !TypeHandling::isSimpleType($propertyType)) {
                continue;
            }
            $propertyValues = $propertyValues->withValue($propertyName, $propertyValue);
        }
        return $command->withInitialPropertyValues($propertyValues);
    }
}
