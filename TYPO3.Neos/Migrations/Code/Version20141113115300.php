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

/**
 * Rename setting TYPO3.Neos.modules.<moduleName>.resource to "privilegeTarget"
 */
class Version20141113115300 extends AbstractMigration
{
    /**
     * @return void
     */
    public function up()
    {
        $this->processConfiguration(
            'Settings',
            function (&$configuration) {
                if (!isset($configuration['TYPO3']['Neos']['modules'])) {
                    return;
                }
                foreach ($configuration['TYPO3']['Neos']['modules'] as &$moduleConfiguration) {
                    $this->processModuleConfiguration($moduleConfiguration);
                }
            },
            true
        );
    }

    /**
     * @param array $moduleConfiguration
     * @return void
     */
    protected function processModuleConfiguration(array &$moduleConfiguration)
    {
        if (isset($moduleConfiguration['resource'])) {
            $moduleConfiguration['privilegeTarget'] = $moduleConfiguration['resource'];
            unset($moduleConfiguration['resource']);
        }
        if (isset($moduleConfiguration['submodules'])) {
            foreach ($moduleConfiguration['submodules'] as &$subModuleConfiguration) {
                $this->processModuleConfiguration($subModuleConfiguration);
            }
        }
    }
}
