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
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeAggregateWithNode;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Service\TransliterationService;

/**
 * Node creation handler that sets the "title" property for new content elements according
 * to the incoming title from a creation dialog.
 *
 * Note: This is not actually a Command Handler in the sense of CQRS but rather some kind of
 *       "command enricher"
 */
class ContentTitleNodeCreationHandler implements NodeCreationHandlerInterface
{
    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var TransliterationService
     */
    protected $transliterationService;

    /**
     * Set the node title for the newly created Content node
     *
     * @param CreateNodeAggregateWithNode $command
     * @param array $data incoming data from the creationDialog
     * @return CreateNodeAggregateWithNode
     * @throws NodeTypeNotFoundException
     */
    public function handle(CreateNodeAggregateWithNode $command, array $data): CreateNodeAggregateWithNode
    {
        if (!$this->nodeTypeManager->getNodeType($command->getNodeTypeName()->getValue())
            ->isOfType('Neos.Neos:Content')) {
            return $command;
        }

        $propertyValues = $command->getInitialPropertyValues();
        if (isset($data['title'])) {
            $propertyValues = $propertyValues->withValue('title', $data['title']);
        }

        return $command->withInitialPropertyValues($propertyValues);
    }
}
