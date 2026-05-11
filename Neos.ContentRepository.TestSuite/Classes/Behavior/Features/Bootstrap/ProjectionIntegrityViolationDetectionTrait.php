<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap;

use Behat\Hook\AfterScenario;
use Behat\Hook\BeforeScenario;
use Neos\ContentRepository\Core\Projection\ContentGraph\ProjectionIntegrityViolationDetectionRunnerFactoryInterface;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Result;
use PHPUnit\Framework\Assert;

trait ProjectionIntegrityViolationDetectionTrait
{
    protected ?Result $lastIntegrityViolationDetectionResult = null;

    /**
     * Prevents feature authors from running the detection but never asserting
     */
    protected bool $lastIntegrityViolationDetectionResultWasAsserted = false;

    /**
     * @When /^I run integrity violation detection$/
     */
    public function iRunIntegrityViolationDetection(): void
    {
        $projectionIntegrityViolationDetectionRunner = $this->getContentRepositoryService(
            $this->getObject(ProjectionIntegrityViolationDetectionRunnerFactoryInterface::class)
        );
        $this->lastIntegrityViolationDetectionResult = $projectionIntegrityViolationDetectionRunner->run();
    }

    #[BeforeScenario]
    public function setupIntegrityViolationDetection(): void
    {
        $this->lastIntegrityViolationDetectionResult = null;
        $this->lastIntegrityViolationDetectionResultWasAsserted = false;
    }

    #[AfterScenario]
    public function afterScenarioEnsureIntegrityViolationDetectionWasRun(): void
    {
        if ($this->lastIntegrityViolationDetectionResult !== null) {
            if ($this->lastIntegrityViolationDetectionResultWasAsserted === false) {
                throw new \RuntimeException(sprintf('Integrity violation result "%s" was not asserted', $this->lastIntegrityViolationDetectionResult->getFirstError()), 1777650586);
            }
        } else {
            if ($this->currentContentRepository === null) {
                // CR Has not been used.
                return;
            }
            $this->iRunIntegrityViolationDetection();
            $this->iExpectTheIntegrityViolationDetectionResultToContainExactlyNErrors(0);
        }
    }

    /**
     * @Then /^I expect the integrity violation detection result to contain exactly (\d+) errors?$/
     * @param int $expectedNumberOfErrors
     */
    public function iExpectTheIntegrityViolationDetectionResultToContainExactlyNErrors(int $expectedNumberOfErrors): void
    {
        $this->lastIntegrityViolationDetectionResultWasAsserted = true;
        Assert::assertCount(
            $expectedNumberOfErrors,
            $this->lastIntegrityViolationDetectionResult->getErrors(),
            'Errors were: ' . implode(', ', array_map(fn (Error $e) => $e->render(), $this->lastIntegrityViolationDetectionResult->getErrors()))
        );
    }

    /**
     * @Then /^I expect integrity violation detection result error number (\d+) to have code (\d+)$/
     * @param int $errorNumber
     * @param int $expectedErrorCode
     */
    public function iExpectIntegrityViolationDetectionResultErrorNumberNToHaveCodeX(int $errorNumber, int $expectedErrorCode): void
    {
        $this->lastIntegrityViolationDetectionResultWasAsserted = true;
        /** @var Error $error */
        $error = $this->lastIntegrityViolationDetectionResult->getErrors()[$errorNumber - 1];
        Assert::assertSame(
            $expectedErrorCode,
            $error->getCode()
        );
    }
}
