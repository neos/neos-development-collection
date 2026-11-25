<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Configuration;

use Neos\Flow\Configuration\Exception as ConfigurationException;
use Neos\Flow\Configuration\Exception\ParseErrorException;
use Neos\Flow\Configuration\Loader\LoaderInterface;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Utility\Arrays;
use Neos\Utility\Files;

readonly class ReferenceTypesLoader implements LoaderInterface
{
    public function __construct(
        private YamlSource $yamlSource,
        private string $configurationBasePath = FLOW_PATH_CONFIGURATION,
    ) {
    }

    /**
     * @param FlowPackageInterface[] $packages
     * @param ApplicationContext $context
     * @return array<int|string, mixed>
     * @throws ParseErrorException | ConfigurationException
     */
    public function load(array $packages, ApplicationContext $context): array
    {
        $configuration = [];

        // ReferenceTypes Directory Configuration
        foreach ($packages as $package) {
            $referenceTypesDirectory = Files::concatenatePaths([$package->getPackagePath(), 'ReferenceTypes']);
            if (\is_dir($referenceTypesDirectory)) {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($referenceTypesDirectory));
                $allYamlFilesIterator = new \CallbackFilterIterator($iterator, static function (\SplFileInfo $fileInfo) {
                    return $fileInfo->isFile() && $fileInfo->getExtension() === 'yaml';
                });
                /** @var \SplFileInfo $fileInfo */
                foreach ($allYamlFilesIterator as $fileInfo) {
                    $path = Files::concatenatePaths([
                        $fileInfo->getPath(),
                        $fileInfo->getBasename('.' . $fileInfo->getExtension())
                    ]);
                    $single = $this->yamlSource->load($path, false);
                    $configuration = Arrays::arrayMergeRecursiveOverrule(
                        $configuration,
                        $single
                    );
                }
            }

            // Package configuration
            $configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $this->yamlSource->load($package->getConfigurationPath() . 'ReferenceTypes', true));
        }
        $configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $this->yamlSource->load($this->configurationBasePath . 'ReferenceTypes', true));

        // Context configuration
        foreach ($context->getHierarchy() as $contextName) {
            foreach ($packages as $package) {
                $configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $this->yamlSource->load($package->getConfigurationPath() . $contextName . '/' . 'ReferenceTypes', true));
            }
            $configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $this->yamlSource->load($this->configurationBasePath . $contextName . '/' . 'ReferenceTypes', true));
        }

        return $configuration;
    }
}
