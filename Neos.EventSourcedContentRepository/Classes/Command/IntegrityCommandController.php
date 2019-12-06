<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Command;

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
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\EventSourcedContentRepository\Domain\Context\Integrity\Command\AddMissingTetheredNodes;
use Neos\EventSourcedContentRepository\Domain\Context\Integrity\IntegrityViolationDetector;
use Neos\EventSourcedContentRepository\Domain\Context\Integrity\IntegrityViolationCommandHandler;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Exception\StopActionException;

final class IntegrityCommandController extends CommandController
{

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var IntegrityViolationDetector
     */
    protected $integrityViolationDetector;

    /**
     * @Flow\Inject
     * @var IntegrityViolationCommandHandler
     */
    protected $integrityViolationCommandHandler;

    /**
     * @param string $nodeType
     * @throws StopActionException
     * @throws \ReflectionException
     */
    public function detectTetheredNodesViolationsCommand(string $nodeType): void
    {
        $violations = $this->integrityViolationDetector->detectTetheredNodeViolations($this->resolveNodeType($nodeType));
        if ($violations->isEmpty()) {
            $this->outputLine('<success>No tethered node integrity violations detected for nodes of type <b>%s</b></success>', [$nodeType]);
            return;
        }
        $this->outputLine('<error>Tethered Node integrity violations detected:</error>');
        $stats = [];
        foreach ($violations as $violation) {
            $violationClassName = get_class($violation);
            if (!array_key_exists($violationClassName, $stats)) {
                $stats[$violationClassName] = 0;
            }
            $stats[$violationClassName] ++;
        }
        foreach ($stats as $className => $numberOfOccurrences) {
            $shortClassName = (new \ReflectionClass($className))->getShortName();
            $this->outputLine('<b>%s</b>: <b>%d</b> times', [$shortClassName, $numberOfOccurrences]);
        }
    }

    /**
     * @param string $nodeType
     * @param string $name
     * @return void
     * @throws StopActionException
     */
    public function addMissingTetheredNodesCommand(string $nodeType, string $name): void
    {
        $nodeTypeToFix = $this->resolveNodeType($nodeType);
        $command = new AddMissingTetheredNodes($nodeTypeToFix, NodeName::fromString($name));
        $this->integrityViolationCommandHandler->handleAddMissingTetheredNodes($nodeTypeToFix, NodeName::fromString($name));
        $this->outputLine('<success>Done</success>');
    }

    /**
     * @param string $nodeTypeName
     * @return NodeType
     * @throws StopActionException
     */
    private function resolveNodeType(string $nodeTypeName): NodeType
    {
        if (!$this->nodeTypeManager->hasNodeType($nodeTypeName)) {
            $this->outputLine('<error>Node Type <b>%s</b> is not known', [$nodeTypeName]);
            $this->quit(1);
        }
        try {
            return $this->nodeTypeManager->getNodeType($nodeTypeName);
        } catch (NodeTypeNotFoundException $exception) {
            throw new \RuntimeException('This should never happen', 1555005325, $exception);
        }
    }
}
