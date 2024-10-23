<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\Import;

use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\Flow\Persistence\Doctrine\Service as DoctrineService;

final readonly class DoctrineMigrateProcessor implements ProcessorInterface
{
    public function __construct(
        private DoctrineService $doctrineService,
    ) {
    }

    public function run(ProcessingContext $context): void
    {
        $this->doctrineService->executeMigrations();
    }
}
