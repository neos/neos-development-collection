<?php
declare(strict_types=1);

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
use PHPUnit\Framework\Assert;

/**
 * Features context
 */
trait FlowSubcommandTrait
{
    private static $onlyOnceRanStepsWhichShouldBeSkipped = [];

    /**
     * @return \Neos\Flow\ObjectManagement\ObjectManagerInterface
     */
    abstract protected function getObjectManager();

    protected function setupFlowSubcommandTrait()
    {
    }

    /**
     * @BeforeFeature
     */
    public static function onlyOncePerFeatureReset()
    {
        self::$onlyOnceRanStepsWhichShouldBeSkipped = [];
    }

    /**
     * @Given /^I start with a clean database only once per feature$/
     */
    public function onlyOnce()
    {
        if (isset(self::$onlyOnceRanStepsWhichShouldBeSkipped['CLEAN_DATABASE'])) {
            // already executed, skipping
            return;
        }
        self::$onlyOnceRanStepsWhichShouldBeSkipped['CLEAN_DATABASE'] = true;

        $this->resetTestFixtures(null);
    }

    protected function getConfigurationManager(): \Neos\Flow\Configuration\ConfigurationManager
    {
        return $this->getObjectManager()->get(\Neos\Flow\Configuration\ConfigurationManager::class);
    }

    /**
     * @Given /^I execute the flow command "([^"]*)"(?: with the following arguments)?( only once per feature)?:?$/
     */
    public function iExecuteTheFlowCommandWithTheFollowingArguments($flowCommandName, $onlyOncePerFeature = false, TableNode $arguments = null)
    {
        if ($onlyOncePerFeature) {
            if (isset(self::$onlyOnceRanStepsWhichShouldBeSkipped[$flowCommandName])) {
                // already executed, skipping
                return;
            }
            self::$onlyOnceRanStepsWhichShouldBeSkipped[$flowCommandName] = true;
        }

        $preparedCommandArguments = [];

        if ($arguments !== null) {
            foreach ($arguments->getHash() as $row) {
                $preparedCommandArguments[$row['Name']] = $row['Value'];
            }
        }

        $flowSettings = $this->getConfigurationManager()->getConfiguration(\Neos\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow');
        ob_start();
        $commandResult = \Neos\Flow\Core\Booting\Scripts::executeCommand($flowCommandName, $flowSettings, true, $preparedCommandArguments);
        $output = ob_get_clean();
        Assert::assertTrue($commandResult, 'Command was not successful. Output was: ' . $output);
    }
}
