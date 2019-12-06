<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Tests\Behavior\Features\Bootstrap;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\EventSourcedContentRepository\Domain\Context\Integrity\Command\AddMissingTetheredNodes;
use Neos\EventSourcedContentRepository\Domain\Context\Integrity\IntegrityViolationDetector;
use Neos\EventSourcedContentRepository\Domain\Context\Integrity\IntegrityViolationCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\Integrity\Violations;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use PHPUnit\Framework\Assert;

/**
 * Custom context trait for "Integrity Violation" related concerns
 */
trait IntegrityViolationTrait
{

    /**
     * @var IntegrityViolationDetector
     */
    protected $integrityViolationDetector;

    /**
     * @var IntegrityViolationCommandHandler
     */
    protected $integrityViolationResolver;

    /**
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @return ObjectManagerInterface
     */
    abstract protected function getObjectManager();

    protected function setupIntegrityViolationTrait(): void
    {
        $this->integrityViolationDetector = $this->getObjectManager()->get(IntegrityViolationDetector::class);
        $this->integrityViolationResolver = $this->getObjectManager()->get(IntegrityViolationCommandHandler::class);
        $this->nodeTypeManager = $this->getObjectManager()->get(NodeTypeManager::class);
    }

    /**
     * @When /^I add missing tethered nodes for node type "([^"]*)" and node name "([^"]*)"$/
     * @param string $nodeTypeName
     * @param string $tetheredNodeName
     * @throws NodeTypeNotFoundException
     */
    public function iAddmissingTetheredNodes(string $nodeTypeName, string $tetheredNodeName): void
    {
        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);

        // FIXME Hack to initialize the node type (to be fixed in NodeType::getTypeOfAutoCreatedChildNode())
        $nodeType->getFullConfiguration();

        $this->lastCommandOrEventResult = $this->integrityViolationResolver->handleAddMissingTetheredNodes(new AddMissingTetheredNodes($nodeType, NodeName::fromString($tetheredNodeName)));
    }

    /**
     * @Then I expect no tethered node violations for type :nodeTypeName
     * @param string $nodeTypeName
     * @throws NodeTypeNotFoundException
     */
    public function iExpectNoTetheredNodeViolationsForType(string $nodeTypeName): void
    {
        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);
        $violations = $this->integrityViolationDetector->detectTetheredNodeViolations($nodeType);
        Assert::assertTrue($violations->isEmpty());
    }

    /**
     * @Then /^I expect the following tethered node violations for type "([^"]*)":$/
     * @param string $nodeTypeName
     * @param TableNode $expectedViolations
     * @throws NodeTypeNotFoundException
     */
    public function iExpectTheFollowingTetheredNodeViolationsForType(string $nodeTypeName, TableNode $expectedViolations): void
    {
        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);
        $actualViolations = $this->integrityViolationDetector->detectTetheredNodeViolations($nodeType);

        $this->assertEqualViolations($expectedViolations, $actualViolations);
    }

    protected function assertEqualViolations(TableNode $expectedViolations, Violations $actualViolations): void
    {
        $convertedViolations = [];
        foreach ($actualViolations as $violation) {
            $convertedViolations[] = [
                'Violation' => (new \ReflectionClass($violation))->getShortName(),
                'Parameters' => json_encode($violation->getParameters()),
            ];
        }
        Assert::assertSame($expectedViolations->getHash(), $convertedViolations, sprintf('expected violations: %s, actual violations: %s', json_encode($expectedViolations->getHash()), json_encode($convertedViolations)));
    }
}
