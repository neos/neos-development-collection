<?php

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

    abstract function getObjectManager(): \Neos\Flow\ObjectManagement\ObjectManagerInterface;

    protected function setupFlowSubcommandTrait()
    {
    }

    protected function getConfigurationManager(): \Neos\Flow\Configuration\ConfigurationManager {
        return $this->getObjectManager()->get(\Neos\Flow\Configuration\ConfigurationManager::class);
    }

    /**
     * @Given /^I execute the flow command "([^"]*)"(?: with the following arguments:)?$/
     */
    public function iExecuteTheFlowCommandWithTheFollowingArguments($flowCommandName, TableNode $arguments = null)
    {
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
