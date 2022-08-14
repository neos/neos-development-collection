<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger;

use Neos\ContentRepositoryRegistry\Command\SubprocessProjectionCatchUpCommandController;
use Neos\Flow\Core\Booting\Scripts;

/**
 * See {@see SubprocessProjectionCatchUpCommandController} for the inner part
 */
class SubprocessProjectionCatchUpTriggerScripts extends Scripts
{
    public static function executeCommandAsyncFast(string $commandIdentifier, array $settings, array $commandArguments = [])
    {
        $command = self::buildSubprocessCommand($commandIdentifier, $settings, $commandArguments);
        if (DIRECTORY_SEPARATOR === '/') {
            $descriptorspec = [
                0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
                1 => array("file", "/dev/null", "w"),  // stdout is a pipe that the child will write to
                2 => array("file","/dev/null", "w") // stderr is a file to write to
            ];
            $pipes = [];
            proc_close(proc_open($command . ' &', $descriptorspec, $pipes));
        } else {
            pclose(popen('START /B CMD /S /C "' . $command . '" > NUL 2> NUL &', 'r'));
        }
    }
}
