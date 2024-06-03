<?php declare(strict_types=1);
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

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalProjectionIntegrityViolationDetectionRunnerFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Error\Messages\Result;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

final class ContentGraphIntegrityCommandController extends CommandController
{
    private const OUTPUT_MODE_CONSOLE = 'console';
    private const OUTPUT_MODE_LOG = 'log';

    #[Flow\Inject()]
    protected Connection $dbal;

    #[Flow\Inject()]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    public function runViolationDetectionCommand(string $contentRepository = 'default', string $outputMode = null): void
    {
        $detectionRunner = $this->contentRepositoryRegistry->buildService(
            ContentRepositoryId::fromString($contentRepository),
            new DoctrineDbalProjectionIntegrityViolationDetectionRunnerFactory($this->dbal)
        );

        $outputMode = $this->resolveOutputMode($outputMode);
        /** @var Result $result */
        $result = $detectionRunner->run();
        switch ($outputMode) {
            case self::OUTPUT_MODE_CONSOLE:
                foreach ($result->getErrors() as $error) {
                    $this->outputLine($error->getMessage());
                }
                break;
            case self::OUTPUT_MODE_LOG:
                $now = new \DateTimeImmutable();
                if (!defined('FLOW_PATH_DATA')) {
                    throw new \Exception('Flow data path is undefined.', 1645393269);
                }
                $fileName = FLOW_PATH_DATA . 'Logs/ContentGraphIntegrityReport_' . $now->format('YmdHis') . '.log';
                touch($fileName);
                $fileContent = '';
                if (empty($result->getErrors())) {
                    $fileContent = 'The content graph showed no integrity violations.';
                } else {
                    foreach ($result->getErrors() as $error) {
                        $fileContent .= $error->getMessage() . "\n";
                    }
                }

                file_put_contents($fileName, $fileContent);
                break;
            default:
        }
    }

    private function resolveOutputMode(?string $outputMode): string
    {
        while ($outputMode !== self::OUTPUT_MODE_CONSOLE && $outputMode !== self::OUTPUT_MODE_LOG) {
            $outputMode = $this->output->ask('Please specify the output mode: (c)onsole or (l)og: ');
            if ($outputMode === 'c') {
                $outputMode = self::OUTPUT_MODE_CONSOLE;
            } elseif ($outputMode === 'l') {
                $outputMode = self::OUTPUT_MODE_LOG;
            }
        }

        return $outputMode;
    }
}
