<?php

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use PHPUnit_Framework_Assert as Assert;
use Symfony\Component\Yaml\Yaml;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Neos\Domain\Service\UserService;
use TYPO3\Neos\EventLog\Domain\Model\Event;
use TYPO3\Neos\EventLog\Domain\Model\NodeEvent;
use TYPO3\Neos\EventLog\Domain\Repository\EventRepository;
use TYPO3\Neos\EventLog\Integrations\EntityIntegrationService;
use TYPO3\Neos\EventLog\Integrations\TYPO3CRIntegrationService;

/**
 * A trait with shared step definitions for common use by other contexts
 *
 * Note that this trait requires that the Flow Object Manager must be available via $this->getSubcontext('flow')->getObjectManager().
 */
trait HistoryDefinitionsTrait
{
    /**
     * @BeforeScenario @fixtures
     * @return void
     */
    public function resetHistory()
    {
        try {
            $eventRepository = $this->getEventRepository();
            $eventRepository->removeAll();
            $this->getTYPO3CRIntegrationService()->reset();
        } catch (\Doctrine\DBAL\DBALException $e) {
            // Ignore DB exceptions, because the trait runs before applying migrations in FlowContext
        }
    }

    /**
     * @Given /^I have an empty history$/
     */
    public function iHaveAnEmptyHistory()
    {
        $this->resetHistory();
        $this->getSubcontext('flow')->persistAll();
    }

    /**
     * @Then /^I should have the following history entries(| \(ignoring order\)):$/
     * @param TableNode $table
     */
    public function iShouldHaveTheFollowingHistoryEntries($ignoringOrder, TableNode $table)
    {
        $this->getSubcontext('flow')->persistAll();
        $allEvents = $this->getEventRepository()->findAll()->toArray();
        $eventsByInternalId = array();
        $unmatchedParentEvents = array();

        if ($ignoringOrder) {
            foreach ($table->getHash() as $i => $row) {
                foreach ($allEvents as $event) {
                    try {
                        $this->checkSingleEvent($row, $event, $eventsByInternalId, $unmatchedParentEvents);
                        // no exception thrown so far, so that means there is an $event which fits to the current expectation row $i. Thus, we continue in the next iteration.
                        continue 2;
                    } catch (PHPUnit_Framework_ExpectationFailedException $assertionFailed) {
                        // do nothing, we just retry the row on the next event.
                    }
                }

                // If we are that far, there was no match for the current row:
                Assert::fail('There was no match for row: ' . json_encode($row));
            }
        } else {
            foreach ($table->getHash() as $i => $row) {
                if (!isset($allEvents[$i])) {
                    Assert::fail(sprintf('Only %s events found, while the expected table contains %s events.', count($allEvents), count($table->getHash())));
                }
                $event = $allEvents[$i];
                $this->checkSingleEvent($row, $event, $eventsByInternalId, $unmatchedParentEvents);
            }
        }

        Assert::assertEquals(count($table->getHash()), count($allEvents), 'Number of expected events does not match total number of events.');
        Assert::assertEmpty($unmatchedParentEvents, 'Unmatched parent events found');
    }

    protected function checkSingleEvent($expected, Event $event, &$eventsByInternalId, &$unmatchedParentEvents)
    {
        /* @var $event NodeEvent */
        $rowId = null;
        foreach ($expected as $rowName => $rowValue) {
            switch ($rowName) {
                case 'ID':
                    if ($rowValue === '') {
                        break;
                    }
                    $rowId = $rowValue;
                    break;
                case 'Parent Event':
                    if ($rowValue === '') {
                        break;
                    }
                    if (isset($eventsByInternalId[$rowValue])) {
                        Assert::assertSame($eventsByInternalId[$rowValue], $event->getParentEvent(), 'Parent event does not match. (1)');
                    } elseif (isset($unmatchedParentEvents[$rowValue]) && $unmatchedParentEvents[$rowValue] !== $event->getParentEvent()) {
                        Assert::fail(sprintf('Parent event "%s" does not match another parent event with the same identifier.', $rowValue));
                    } else {
                        $unmatchedParentEvents[$rowValue] = $event->getParentEvent();
                    }
                    break;
                case 'Event Type':
                    Assert::assertEquals($rowValue, $event->getEventType(), 'Event Type does not match. Expected: ' . $rowValue . '. Actual: ' . $event->getEventType());
                    break;
                case 'Node Identifier':
                    if ($rowValue === '') {
                        break;
                    }
                    Assert::assertEquals($rowValue, $event->getNodeIdentifier(), 'Node Identifier does not match.');
                    break;
                case 'Document Node Identifier':
                    Assert::assertEquals($rowValue, $event->getDocumentNodeIdentifier(), 'Document Node Identifier does not match.');
                    break;
                case 'Workspace':
                    Assert::assertEquals($rowValue, $event->getWorkspaceName(), 'Workspace does not match.');
                    break;
                case 'Explanation':
                    break;
                default:
                    throw new \Exception('Row Name ' . $rowName . ' not supported.');
            }
        }

        if ($rowId !== null) {
            $eventsByInternalId[$rowId] = $event;
            if (isset($unmatchedParentEvents[$rowId])) {
                Assert::assertSame($eventsByInternalId[$rowId], $event, 'Parent event does not match. (2)');
                unset($unmatchedParentEvents[$rowId]);
            }
        }
    }

    /**
     * @return EventRepository
     */
    protected function getEventRepository()
    {
        return $this->getObjectManager()->get(EventRepository::class);
    }

    /**
     * @return TYPO3CRIntegrationService
     */
    protected function getTYPO3CRIntegrationService()
    {
        return $this->getObjectManager()->get(TYPO3CRIntegrationService::class);
    }

    /**
     * @Given /^I have the following "monitorEntities" configuration:$/
     */
    public function iHaveTheFollowingMonitorEntitiesConfiguration(PyStringNode $string)
    {
        $configuration = Yaml::parse($string->getRaw());
        /* @var $entityIntegrationService EntityIntegrationService */
        $entityIntegrationService = $this->getObjectManager()->get(EntityIntegrationService::class);
        $entityIntegrationService->setMonitorEntitiesSetting($configuration);
    }

    /**
     * @When /^I create the following accounts:$/
     */
    public function iCreateTheFollowingAccounts(TableNode $table)
    {
        foreach ($table->getHash() as $row) {
            $user = $this->getObjectManager()->get(UserService::class)->createUser(
                $row['User'],
                $row['Password'],
                $row['First Name'],
                $row['Last Name'],
                Arrays::trimExplode(',', $row['Roles'])
            );
        }

        $this->getSubcontext('flow')->persistAll();
    }
}
