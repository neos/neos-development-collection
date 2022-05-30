<?php
declare(strict_types=1);
namespace Neos\ESCR\Export;

use Neos\ESCR\Export\Filesystem\FilesystemFactoryInterface;
use Neos\ESCR\Export\Middleware\Context;
use Neos\ESCR\Export\Middleware\MiddlewareInterface;
use Neos\ESCR\Export\ValueObject\ParameterDefinitions;
use Neos\ESCR\Export\ValueObject\Parameters;
use Neos\ESCR\Export\ValueObject\PresetId;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\PositionalArraySorter;

#[Flow\Scope('singleton')]
final class HandlerFactory
{
    /**
     * @param array<string, array{parameters?: array<string, array<string, string|int|bool|null>>, fileSystem: array{factory?: string, options?: array<string, string|int|bool|null>}, middlewares?: array<string, array<mixed>>}> $presetConfiguration
     */
    public function __construct(
        private readonly array $presetConfiguration,
        private readonly ObjectManagerInterface $objectManager,
    ) {}

    public function get(PresetId $presetId, Parameters $parameters): Handler
    {
        $presetOptions = $this->presetConfiguration[$presetId->toString()] ?? null;
        if ($presetOptions === null) {
            throw new \InvalidArgumentException(sprintf('Failed to load preset with id "%s"', $presetId->toString()), 1638204989);
        }
        $parameterDefinitions = ParameterDefinitions::fromArray($presetOptions['parameters'] ?? []);
        unset($presetOptions['parameters']);
        foreach ($parameters as $parameterName => $_) {
            if (!$parameterDefinitions->has($parameterName)) {
                throw new \InvalidArgumentException(sprintf('Unknown parameter "%s"', $parameterName), 1638379431);
            }
        }
        foreach ($parameterDefinitions as $parameterDefinition) {
            if ($parameterDefinition->isRequired() && !$parameters->has($parameterDefinition->name)) {
                throw new \InvalidArgumentException(sprintf('Missing parameter "%s"', $parameterDefinition->name), 1638382227);
            }
        }
        array_walk_recursive($presetOptions, static function (&$option) use ($parameters) {
            if (!\is_string($option)) {
                return;
            }
            $option = preg_replace_callback('/{parameters\.([a-z0-9-_]+)}/i', static fn($matches) => (string)$parameters->get($matches[1]), $option);
        });
        if (!isset($presetOptions['fileSystem']['factory'])) {
            throw new \RuntimeException(sprintf('Missing "fileSystem.factory" settings for preset "%s"', $presetId->toString()), 1646305420);
        }
        $filesystemFactory = $this->objectManager->get($presetOptions['fileSystem']['factory']);
        if (!$filesystemFactory instanceof FilesystemFactoryInterface) {
            throw new \RuntimeException(sprintf('The configured fileSystem.factory "%s" for preset "%s" does not implement %s', $presetOptions['fileSystem']['factory'], $presetId->toString(), FilesystemFactoryInterface::class), 1646305580);
        }
        if (!isset($presetOptions['middlewares'])) {
            throw new \InvalidArgumentException(sprintf('Missing "middlewares" settings for preset "%s"', $presetId->toString()), 1646242255);
        }
        $middlewares = [];
        foreach ((new PositionalArraySorter($presetOptions['middlewares']))->toArray() as $middlewareId => $middlewareOptions) {
            if (!isset($middlewareOptions['className'])) {
                throw new \RuntimeException(sprintf('Missing "className" configuration for middleware "%s" in preset "%s"', $middlewareId, $presetId->toString()), 1646242589);
            }
            $middleware = $this->objectManager->get($middlewareOptions['className']);
            if (!$middleware instanceof MiddlewareInterface) {
                throw new \RuntimeException(sprintf('The configured className "%s" for middleware "%s" in preset "%s" does not implement %s', $middlewareOptions['className'], $middlewareId, $presetId->toString(), MiddlewareInterface::class), 1646242590);
            }
            $middlewares[] = $middleware;
        }
        $filesystem = $filesystemFactory->create($presetOptions['fileSystem']['options'] ?? []);
        $context = new Context($filesystem, $parameters);
        return Handler::fromContextAndMiddlewares($context, ...$middlewares);
    }

}
