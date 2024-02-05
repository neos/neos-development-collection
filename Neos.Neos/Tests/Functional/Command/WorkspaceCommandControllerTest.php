<?php

namespace Neos\Neos\Tests\Functional\Command;

use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\Flow\Cli\Exception\StopCommandException;
use Neos\Flow\Cli\Response;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Neos\Command\WorkspaceCommandController;

class WorkspaceCommandControllerTest extends FunctionalTestCase
{
    protected static $testablePersistenceEnabled = true;

    /**
     * @var WorkspaceCommandController
     */
    private $commandController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandController = $this->objectManager->get(WorkspaceCommandController::class);
        $this->inject($this->commandController, 'response', new Response());
    }

    /**
     * @test
     */
    public function test_createCommand_withInvalidWorkspaceName_throwsException()
    {
        $this->expectException(StopCommandException::class);
        $this->commandController->createCommand('invalid_workspace_name');
    }

    /**
     * @test
     */
    public function test_createCommand_withValidWorkspaceName_succeeds()
    {
        $workspaceRepository = $this->objectManager->get(WorkspaceRepository::class);
        $liveWorkspace = new Workspace('live', null, null);
        $liveWorkspace->setDescription('The live workspace');
        $workspaceRepository->add($liveWorkspace);
        $this->persistenceManager->persistAll();

        $workspaceName = 'my-new-workspace';
        $this->commandController->createCommand(
            $workspaceName,
            'live',
            'Test Workspace',
            'A test workspace'
        );
        $this->persistenceManager->persistAll();


        $workspace = $workspaceRepository->findOneByName($workspaceName);
        self::assertNotNull($workspace);
        self::assertEquals($workspaceName, $workspace->getName());
    }
}
