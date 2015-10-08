<?php

require_once(__DIR__ . '/../../../../../../Application/Flowpack.Behat/Tests/Behat/FlowContext.php');
require_once(__DIR__ . '/StepDefinitionsTrait.php');

/**
 * Features context
 */
class FeatureContext extends Behat\Behat\Context\BehatContext
{
    use StepDefinitionsTrait;

    /**
     * Initializes the context
     *
     * @param array $parameters Context parameters (configured through behat.yml)
     */
    public function __construct(array $parameters)
    {
        $this->useContext('flow', new \Flowpack\Behat\Tests\Behat\FlowContext($parameters));
    }

    /**
     * @BeforeScenario @fixtures
     * @return void
     */
    public function beforeScenarioDispatcher()
    {
        $this->resetNodeInstances();
        $this->resetContentDimensions();
    }
}
