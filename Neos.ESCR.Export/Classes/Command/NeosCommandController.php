<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\Command;

use Neos\ESCR\Export\Handler;
use Neos\ESCR\Export\HandlerFactory;
use Neos\ESCR\Export\Middleware\MiddlewareInterface;
use Neos\ESCR\Export\ValueObject\Parameters;
use Neos\ESCR\Export\ValueObject\PresetId;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Mvc\Controller\Argument;
use Neos\Flow\Mvc\Exception\NoSuchArgumentException;
use Neos\Flow\Property\Exception as PropertyException;
use Neos\Flow\Security\Exception as SecurityException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

final class NeosCommandController extends CommandController
{
    public function __construct(
        private readonly HandlerFactory $handlerFactory
    )
    {
        parent::__construct();
    }

    /**
     * Performs an import with the behavior configured in the specified $preset
     *
     * @param string $preset Identifier of the preset to use
     * @param bool $quiet If set only errors are outputted
     */
    public function importCommand(string $preset, bool $quiet = false): void
    {
        $this->process(PresetId::fromString($preset), fn(Handler $handler) => $handler->processImport(), $quiet);
    }

    /**
     * Performs an export with the behavior configured in the specified $preset
     *
     * @param string $preset Identifier of the preset to use
     * @param bool $quiet If set only errors are outputted
     */
    public function exportCommand(string $preset, bool $quiet = false): void
    {
        $this->process(PresetId::fromString($preset), fn(Handler $handler) => $handler->processExport(), $quiet);
    }

    // --------------------------

    /**
     * @param PresetId $presetId
     * @param \Closure(Handler $handler): void $callback
     * @param bool $quiet
     */
    private function process(PresetId $presetId, \Closure $callback, bool $quiet): void
    {
        $handler = $this->handlerFactory->get($presetId, $this->parametersFromExceededArguments());
        if (!$quiet) {
            $output = $this->output->getOutput();
            $mainSection = $output instanceof ConsoleOutput ? $output->section() : $output;
            $progressBar = new ProgressBar($mainSection);
            $progressBar->setBarCharacter('<success>●</success>');
            $progressBar->setEmptyBarCharacter('<error>◌</error>');
            $progressBar->setProgressCharacter('<success>●</success>');
            $progressBar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% - %message%');
            $progressBar->setMessage('...');

            $handler->onStart(fn(int $numberOfSteps) => $progressBar->start($numberOfSteps));
            $handler->onStep(function(MiddlewareInterface $middleware) use ($progressBar) {
                $progressBar->advance();
                $progressBar->setMessage($middleware->getLabel());
            });
            if ($output instanceof ConsoleOutput) {
                $logSection = $output->section();
                $handler->onMessage(fn(string $message) => $logSection->writeln($message));
            }

        }
        $callback($handler);
        if (!$quiet) {
            $progressBar->finish();
            $this->outputLine();
        }
    }

    /**
     * @throws PropertyException | SecurityException
     */
    private function convertArgument(Argument $argument, mixed $value): void
    {
        $convertedValue = match ($argument->getDataType()) {
            PresetId::class => is_string($value) ? PresetId::fromString($value) : $value,
            default => $value,
        };
        $argument->setValue($convertedValue);
    }

    private function parametersFromExceededArguments(): Parameters
    {
        $paramsArray = [];
        foreach ($this->request->getExceedingArguments() as $rawArgument) {
            $nameAndValue = explode('=', $rawArgument, 2);
            $paramsArray[$nameAndValue[0]] = $nameAndValue[1];
        }
        return Parameters::fromArray($paramsArray);
    }

    /**
     * @throws SecurityException | NoSuchArgumentException | PropertyException
     */
    protected function mapRequestArgumentsToControllerArguments(): void
    {
        /** @var Argument $argument */
        foreach ($this->arguments as $argument) {
            $argumentName = $argument->getName();

            if ($this->request->hasArgument($argumentName)) {
                $this->convertArgument($argument, $this->request->getArgument($argumentName));
                continue;
            }
            if (!$argument->isRequired()) {
                continue;
            }
            $argumentValue = null;
            while ($argumentValue === null) {
                $argumentValue = $this->output->ask(sprintf('<comment>Please specify the required argument "%s":</comment> ', $argumentName));
            }
            $this->convertArgument($argument, $argumentValue);
        }
    }
}
