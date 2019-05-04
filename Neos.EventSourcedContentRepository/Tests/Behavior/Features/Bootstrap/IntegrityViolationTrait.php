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
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\EventSourcedContentRepository\Integrity\IntegrityViolationDetector;
use Neos\EventSourcedContentRepository\Integrity\IntegrityViolationResolver;
use Neos\EventSourcedContentRepository\Integrity\Violations;
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
     * @var IntegrityViolationResolver
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
        $this->integrityViolationResolver = $this->getObjectManager()->get(IntegrityViolationResolver::class);
        $this->nodeTypeManager = $this->getObjectManager()->get(NodeTypeManager::class);
    }

    /**
     * @Then /^I expect no tethered node violations for type "([^"]*)"$/
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
