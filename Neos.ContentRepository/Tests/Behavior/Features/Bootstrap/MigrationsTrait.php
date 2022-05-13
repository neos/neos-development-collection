<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Tests\Behavior\Features\Bootstrap;

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
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Feature\Migration\Command\ExecuteMigration;
use Neos\ContentRepository\Feature\Migration\MigrationCommandHandler;
use Neos\ContentRepository\Feature\Migration\Command\MigrationConfiguration;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Custom context trait for "Node Migration" related concerns
 */
trait MigrationsTrait
{
    protected MigrationCommandHandler $migrationCommandHandler;

    /**
     * @return ObjectManagerInterface
     */
    abstract protected function getObjectManager();

    protected function setupMigrationsTrait(): void
    {
        $this->migrationCommandHandler = $this->getObjectManager()->get(MigrationCommandHandler::class);
    }
    /**
     * @When I run the following node migration for workspace :workspaceName, creating content streams :contentStreams:
     */
    public function iRunTheFollowingNodeMigration(string $workspaceName, string $contentStreams, PyStringNode $string)
    {
        $migrationConfiguration = new MigrationConfiguration(Yaml::parse($string->getRaw()));
        $contentStreamIdentifiers = array_map(fn (string $cs) => ContentStreamIdentifier::fromString($cs), explode(',', $contentStreams));
        $command = new ExecuteMigration($migrationConfiguration, WorkspaceName::fromString($workspaceName), $contentStreamIdentifiers);
        $this->migrationCommandHandler->handleExecuteMigration($command);
    }

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
