<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Gherkin\Node\PyStringNode;
use Neos\ContentRepository\NodeMigration\NodeMigrationService;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\NodeMigration\Command\ExecuteMigration;
use Neos\ContentRepository\NodeMigration\Command\MigrationConfiguration;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use Symfony\Component\Yaml\Yaml;

/**
 * Custom context trait for "Node Migration" related concerns
 */
trait MigrationsTrait
{
    use CRTestSuiteRuntimeVariables;

    /**
     * @When I run the following node migration for workspace :workspaceName, creating content streams :contentStreams:
     */
    public function iRunTheFollowingNodeMigration(string $workspaceName, string $contentStreams, PyStringNode $string)
    {
        $migrationConfiguration = new MigrationConfiguration(Yaml::parse($string->getRaw()));
        $contentStreamIds = array_map(
            fn (string $cs) => ContentStreamId::fromString($cs),
            explode(',', $contentStreams)
        );
        $command = new ExecuteMigration($migrationConfiguration, WorkspaceName::fromString($workspaceName), $contentStreamIds);

        $this->getNodeMigrationService()->executeMigration($command);
    }

    abstract protected function getNodeMigrationService(): NodeMigrationService;

    /**
     * @When I run the following node migration for workspace :workspaceName, creating content streams :contentStreams and exceptions are caught:
     */
    public function iRunTheFollowingNodeMigrationAndExceptionsAreCaught(string $workspaceName, string $contentStreams, PyStringNode $string)
    {
        try {
            $this->iRunTheFollowingNodeMigration($workspaceName, $contentStreams, $string);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }
}
