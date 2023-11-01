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

namespace Neos\Neos\Tests\Functional\Command;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Neos\Command\CrCommandController;
use Neos\Utility\Arrays;

class CrCommandControllerTest extends FunctionalTestCase
{
    private CrCommandController $subject;

    private ContentRepositoryRegistry $contentRepositoryRegistry;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->objectManager->get(CrCommandController::class);
        $this->contentRepositoryRegistry = $this->objectManager->get(ContentRepositoryRegistry::class);
    }

    public function testImportThenExport()
    {
        $this->contentRepositoryRegistry->get(ContentRepositoryId::fromString('default'))->setUp();
        $sourcePath = __DIR__ . '/../Fixtures';
        $this->subject->importCommand($sourcePath);
        $targetPath = FLOW_PATH_DATA . 'Temporary';
        $this->subject->exportCommand($targetPath);

        $expectedEvents = Arrays::trimExplode("\n", file_get_contents($sourcePath. '/events.jsonl'));
        $actualEvents = Arrays::trimExplode("\n", file_get_contents($targetPath. '/events.jsonl'));

        self::assertSame(count($expectedEvents), count($actualEvents));
    }
}
