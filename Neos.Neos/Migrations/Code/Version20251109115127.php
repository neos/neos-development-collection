<?php

namespace Neos\Flow\Core\Migrations;

use Neos\Utility\Arrays;
use Neos\Utility\Files;
use Symfony\Component\Yaml\Yaml;

/**
 * Adjust configuration to Neos 9
 *
 * Rewrite Settings.yaml config to new language
 * Rewrite Routes.yaml config to use Neos\Neos\FrontendRouting\FrontendNodeRoutePartHandlerInterface as route part handler
 */
class Version20251109115127 extends AbstractMigration
{
    public function getIdentifier(): string
    {
        return 'Neos.Neos-20251109115127';
    }

    public function up(): void
    {
        $this->replaceInConfiguration('Settings', $this->rewriteDimensionConfiguration(...));
        $this->replaceInConfiguration('Routes', $this->rewriteFrontendRoutePartHandler(...));
    }

    /**
     * FIXME Modified and improved {@see processConfiguration} which 1. does not work with realpaths and thus works during vfs testing and 2. allows to prepend warning comments to the yaml file.
     * But 3. an even improved version should que the changes instead of executing directly in up()
     */
    final public function replaceInConfiguration(string $configurationType, \Closure $processor): void
    {
        if (is_dir($this->targetPackageData['path'] . '/Configuration') === false) {
            return;
        }

        $yamlPathsAndFilenames = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->targetPackageData['path'] . '/Configuration'),
            ),
            '/\.yaml$/',
            \RecursiveRegexIterator::GET_MATCH,
        );

        $configurationPathsAndFilenames = array_filter(array_keys(iterator_to_array($yamlPathsAndFilenames)),
            function ($pathAndFileName) use ($configurationType) {
                if (strpos(basename($pathAndFileName, '.yaml'), $configurationType) === 0) {
                    return true;
                } else {
                    return false;
                }
            }
        );

        foreach ($configurationPathsAndFilenames as $pathAndFilename) {
            $warnings = [];

            $commentPrefix = sprintf('TODO %s ', $this->getIdentifier());
            $onWarning = fn (string $comment) => $this->showWarning(sprintf('File %s: %s', Files::getRelativePath(
                $this->targetPackageData['path'],
                $pathAndFilename
            ), $comment));

            $addWarning = function (string $warning) use (&$warnings, $onWarning) {
                $onWarning($warning);
                $warnings[] = $warning;
            };

            $fileContent = file_get_contents($pathAndFilename);
            $originalConfiguration = Yaml::parse($fileContent);
            $configuration = $processor($originalConfiguration, $addWarning);
            if ($configuration !== $originalConfiguration) {
                // TODO do we need to preserve file header lines like in YamlSource::getHeaderFromFile()?
                // but in that case we need to check if comments exist already
                // if (str_contains($fileContent, '# ' . $commentPrefix)) {
                //     ($onWarning)('No migration todo comments written as the migration was already run.');
                // }
                $contents = Yaml::dump($configuration, 99, 2);
                $precedingComments = array_map(fn ($comment) => '# ' . $commentPrefix . $comment, $warnings);
                if ($warnings) {
                    $contents = implode("\n", $precedingComments) . "\n" . $contents;
                }
                file_put_contents($pathAndFilename, $contents);
            }
        }
    }

    public function rewriteDimensionConfiguration(array $parsed, \Closure $addWarning): array
    {
        if (isset($parsed['Neos']['ContentRepositoryRegistry']['contentRepositories']['default']['contentDimensions'])) {
            // we already have a Neos.ContentRepositoryRegistry.contentRepositories.default.contentDimensions key
            // -> we assume the file has already been processed.
            return $parsed;
        }

        if (!isset($parsed['Neos']['ContentRepository']['contentDimensions'])) {
            // we do not have a Neos.ContentRepository.contentDimensions key; so we do not need
            // to process this file
            return $parsed;
        }

        $defaultDimensionSpacePoint = [];
        $uriPathSegments = [];
        $errors = [];
        foreach ($parsed['Neos']['ContentRepository']['contentDimensions'] as $dimensionName => $oldDimensionConfig) {
            $uriPathSegmentsForDimension = [
                'dimensionIdentifier' => $dimensionName,
                'dimensionValueMapping' => []
            ];
            $newContentDimensionConfig = [];

            if (isset($oldDimensionConfig['label'])) {
                $newContentDimensionConfig['label'] = $oldDimensionConfig['label'];
            }
            if (isset($oldDimensionConfig['icon'])) {
                $newContentDimensionConfig['icon'] = $oldDimensionConfig['icon'];
            }

            if (isset($oldDimensionConfig['default'])) {
                $defaultDimensionSpacePoint[$dimensionName] = $oldDimensionConfig['default'];
            } else {
                $addWarning(sprintf('For content dimension "%s", did not find any default dimension value underneath "default". The defaultDimensionSpacePoint might be incomplete.', $dimensionName));
            }
            foreach ($oldDimensionConfig['presets'] as $presetName => $presetConfig) {
                // we need to use the last dimension value as the the new dimension value name; because that is
                // what the dimension migrator expects.
                //
                // The PresetName is discarded
                $dimensionValueConfig = [];
                if (isset($presetConfig['label'])) {
                    $dimensionValueConfig['label'] = $presetConfig['label'];
                }
                if (isset($presetConfig['icon'])) {
                    $dimensionValueConfig['icon'] = $presetConfig['icon'];
                }

                if (!isset($presetConfig['values'])) {
                    $addWarning(sprintf('For preset "%s", did not find any dimension values underneath "values"', $presetName));
                } else {
                    $valuesExceptLast = $presetConfig['values'];
                    $valuesExceptLast = array_reverse($valuesExceptLast);
                    $lastValue = array_pop($valuesExceptLast);
                    $currentValuePath = &$newContentDimensionConfig['values'];
                    foreach ($valuesExceptLast as $value) {
                        $currentValuePath = &$currentValuePath[$value]['specializations'];
                    }
                    $currentValuePath[$lastValue] = $dimensionValueConfig;

                    if (isset($presetConfig['uriSegment'])) {
                        $uriPathSegmentsForDimension['dimensionValueMapping'][$lastValue] = $presetConfig['uriSegment'];
                    } else {
                        $addWarning(sprintf('For preset "%s", did not find any uriSegment.', $presetName));
                    }
                }
            }

            $parsed['Neos']['ContentRepositoryRegistry']['contentRepositories']['default']['contentDimensions'][$dimensionName] = $newContentDimensionConfig;
            $uriPathSegments[] = $uriPathSegmentsForDimension;
        }
        $parsed['Neos']['ContentRepository']['contentDimensions'] = [];
        $parsed = Arrays::removeEmptyElementsRecursively($parsed);

        $parsed['Neos']['Neos']['sites']['*']['contentDimensions'] = [
            'defaultDimensionSpacePoint' => $defaultDimensionSpacePoint,
            'resolver' => [
                'factoryClassName' => 'Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory',
                'options' => [
                    'segments' => $uriPathSegments
                ]
            ]
        ];

        return $parsed;
    }

    public function rewriteFrontendRoutePartHandler(array $parsed): array
    {
        foreach ($parsed as $routeConfigKey => $routeConfig) {
            if (!is_array($routeConfig)) {
                continue;
            }
            if (!isset($routeConfig['routeParts']) || !is_array($routeConfig['routeParts'])) {
                continue;
            }

            $handlerToReplace = [
                'Neos\Neos\Routing\FrontendNodeRoutePartHandler',
                'Neos\Neos\Routing\FrontendNodeRoutePartHandlerInterface',
            ];

            foreach ($routeConfig['routeParts'] as $routePartKey => $routePart) {
                if (isset($routePart['handler']) && in_array($routePart['handler'], $handlerToReplace)) {
                    $parsed[$routeConfigKey]['routeParts'][$routePartKey]['handler'] = \Neos\Neos\FrontendRouting\FrontendNodeRoutePartHandlerInterface::class;
                }
            }
        }

        return $parsed;
    }
}
