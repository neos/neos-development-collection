<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Service\ContentStreamPrunerFactory;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\StructureAdjustment\Adjustment\StructureAdjustment;
use Neos\ContentRepository\StructureAdjustment\StructureAdjustmentServiceFactory;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Annotations as Flow;

final class StructureAdjustmentsCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;


    /**
     * Detect required structure adjustments for the specified node type in the given content repository.
     *
     * @param string|null $nodeType The node type to find structure adjustments for. If not provided, all adjustments will be shown. (Default: null)
     * @param string $contentRepositoryIdentifier The content repository identifier. (Default: 'default')
     */
    public function detectCommand(string $nodeType = null, string $contentRepositoryIdentifier = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepositoryIdentifier);
        $structureAdjustmentService = $this->contentRepositoryRegistry->buildService($contentRepositoryId, new StructureAdjustmentServiceFactory());

        if ($nodeType !== null) {
            $errors = $structureAdjustmentService->findAdjustmentsForNodeType(
                NodeTypeName::fromString($nodeType)
            );
        } else {
            $errors = $structureAdjustmentService->findAllAdjustments();
        }

        $this->printErrors($errors);
    }

    /**
     * Apply required structure adjustments for the specified node type in the given content repository.
     *
     * @param string|null $nodeType The node type to apply structure adjustments for. If not provided, all found adjustments will be applied. (Default: null)
     * @param string $contentRepositoryIdentifier The content repository identifier. (Default: 'default')
     * @return void
     */
    public function fixCommand(string $nodeType = null, string $contentRepositoryIdentifier = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepositoryIdentifier);
        $structureAdjustmentService = $this->contentRepositoryRegistry->buildService($contentRepositoryId, new StructureAdjustmentServiceFactory());

        if ($nodeType !== null) {
            $errors = $structureAdjustmentService->findAdjustmentsForNodeType(
                NodeTypeName::fromString($nodeType)
            );
        } else {
            $errors = $structureAdjustmentService->findAllAdjustments();
        }

        foreach ($errors as $error) {
            assert($error instanceof StructureAdjustment);
            $this->outputLine($error->render());
            $structureAdjustmentService->fixError($error);
        }
        $this->outputLine('Fixed all.');
    }

    /**
     * @param \Generator<int,StructureAdjustment> $errors
     */
    private function printErrors(\Generator $errors): void
    {
        foreach ($errors as $error) {
            assert($error instanceof StructureAdjustment);
            $this->outputLine($error->render());
        }
    }
}
