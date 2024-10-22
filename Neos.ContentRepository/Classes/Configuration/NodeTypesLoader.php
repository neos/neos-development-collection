<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Configuration;

use Neos\Flow\Configuration\Exception as ConfigurationException;
use Neos\Flow\Configuration\Exception\ParseErrorException;
use Neos\Flow\Configuration\Loader\LoaderInterface;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Package\PackageInterface;
use Neos\Utility\Arrays;
use Neos\Utility\Files;

class NodeTypesLoader implements LoaderInterface
{
    /**
     * @var YamlSource
     */
    private $yamlSource;

    /**
     * @var string
     */
    private $configurationBasePath;

    public function __construct(YamlSource $yamlSource, string $configurationBasePath = FLOW_PATH_CONFIGURATION)
    {
        $this->yamlSource = $yamlSource;
        $this->configurationBasePath = $configurationBasePath;
    }

    /**
     * @param PackageInterface[] $packages
     * @param ApplicationContext $context
     * @return array
     * @throws ParseErrorException | ConfigurationException
     */
    public function load(array $packages, ApplicationContext $context): array
    {
        $configuration = [];

        // NodeTypes Directory Configuration
        foreach ($packages as $package) {
            $nodeTypesDirectory = Files::concatenatePaths([$package->getPackagePath(), 'NodeTypes']);
            if (\is_dir($nodeTypesDirectory)) {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($nodeTypesDirectory));
                $allYamlFilesIterator = new \CallbackFilterIterator($iterator, static function (\SplFileInfo $fileInfo) {
                    return $fileInfo->isFile() && $fileInfo->getExtension() === 'yaml';
                });
                /** @var \SplFileInfo $fileInfo */
                foreach ($allYamlFilesIterator as $fileInfo) {
                    $path = Files::concatenatePaths([
                        $fileInfo->getPath(),
                        $fileInfo->getBasename('.' . $fileInfo->getExtension())
                    ]);
                    $configuration = Arrays::arrayMergeRecursiveOverrule(
                        $configuration,
                        $this->yamlSource->load($path, false)
                    );
                }
            }

            // Package configuration
            $configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $this->yamlSource->load($package->getConfigurationPath() . 'NodeTypes', true));
        }
        $configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $this->yamlSource->load($this->configurationBasePath . 'NodeTypes', true));

        // Context configuration
        foreach ($context->getHierarchy() as $contextName) {
            foreach ($packages as $package) {
                $configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $this->yamlSource->load($package->getConfigurationPath() . $contextName . '/' . 'NodeTypes', true));
            }
            $configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $this->yamlSource->load($this->configurationBasePath . $contextName . '/' . 'NodeTypes', true));
        }

        return $configuration;
    }
}
