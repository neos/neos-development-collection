<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use Neos\ContentRepositoryRegistry\Upgrade\Command\CRUpgradeContextFactory;
use Neos\ContentRepositoryRegistry\Upgrade\EventsRecordedAtToUtc\EventsRecordedAtToUtcUpgrade;
use Neos\ContentRepositoryRegistry\Upgrade\Shared\CRUpgradeContext;
use PHPUnit\Framework\Assert;

trait CrUpgradeTrait
{
    use CRTestSuiteRuntimeVariables;

    private array $crUpgrade_outputLines = [];

    protected function outputFn(string $message): void
    {
        $this->crUpgrade_outputLines[] = $message;
    }

    protected function getCrUpgradeContext(): CRUpgradeContext
    {
        return $this->getContentRepositoryService(
            $this->getObject(CRUpgradeContextFactory::class)
        );
    }

    /**
     * @When I upgrade events recordedAt to utc
     * @When /^I upgrade events recordedAt to utc (with force)$/
     */
    public function iExecuteEventsRecordedAtToUtcUpgrade(bool $force = false): void
    {
        $upgrade = new EventsRecordedAtToUtcUpgrade(
            $this->getCrUpgradeContext(),
            $this->outputFn(...)
        );

        $upgrade->execute(
            force: $force
        );
    }

    /**
     * @AfterScenario
     */
    public function failIfUpgradeOutputNotAsserted(): void
    {
        if ($this->crUpgrade_outputLines !== []) {
            throw new \RuntimeException(sprintf('The last upgrade wrote: %s', PHP_EOL . PHP_EOL. join(PHP_EOL, $this->crUpgrade_outputLines)));
        }
    }

    /**
     * @AfterScenario
     */
    public function dropBackupTables(): void
    {
        $context = $this->getCrUpgradeContext();
        foreach ($context->dbal->getSchemaManager()->listTableNames() as $tableName) {
            if (str_starts_with($tableName, $context->eventStoreTableName . '_bkp_')) {
                $context->dbal->executeStatement(sprintf('DROP TABLE %s;', $tableName));
            }
        }
    }

    /**
     * @Then I expect the following upgrade output:
     */
    public function iExpectTheFollowingOutput(PyStringNode $string): void
    {
        Assert::assertSame($string->getRaw(), join(PHP_EOL, $this->crUpgrade_outputLines));
        $this->crUpgrade_outputLines = [];
    }

    /**
     * @Given I have the following raw events to upgrade:
     */
    public function iHaveTheFollowingRawEventsToUpgrade(TableNode $events)
    {
        $context = $this->getCrUpgradeContext();

        $context->dbal->beginTransaction();

        foreach ($events->getColumnsHash() as $row) {
            $context->dbal->insert(
                $context->eventStoreTableName,
                $row
            );
        }

        $context->dbal->commit();
    }
}
