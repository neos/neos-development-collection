<?php
namespace TYPO3\Flow\Core\Migrations;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\Configuration\ConfigurationManager;

/**
 * Prefix editing / preview mode settings
 */
class Version20161103173400 extends AbstractMigration
{
    /**
     * Renames all ImageVariant property types to ImageInterface
     *
     * @return void
     */
    public function up()
    {
        $package = $this->targetPackageData['packageKey'];
        $neosEditingPreviewModes = ['live', 'inPlace', 'rawContent', 'desktop'];

        $this->processConfiguration(
            ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            function (&$configuration) use ($package, $neosEditingPreviewModes) {
                if (isset($configuration['TYPO3']['Neos']['userInterface']['editPreviewModes'])) {
                    foreach ($configuration['TYPO3']['Neos']['userInterface']['editPreviewModes'] as $name => &$modeConfiguration) {
                        if (strpos($name, ':') !== false) {
                            continue;
                        }
                        if (in_array($name, $neosEditingPreviewModes)) {
                            $prefixedName = 'Neos.Neos:' . $name;
                        } else {
                            $prefixedName = $package . ':' . $name;
                        }
                        $configuration['TYPO3']['Neos']['userInterface']['editPreviewModes'][$prefixedName] = $modeConfiguration;
                        unset($configuration['TYPO3']['Neos']['userInterface']['editPreviewModes'][$name]);
                    }
                }
            },
            true
        );
    }
}
