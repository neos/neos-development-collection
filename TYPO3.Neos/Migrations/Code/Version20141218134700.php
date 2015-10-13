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
 * Rename node property type 'date' to DateTime
 */
class Version20141218134700 extends AbstractMigration
{
    /**
     * @return void
     */
    public function up()
    {
        $this->processConfiguration(
            'NodeTypes',
            function (&$configuration) {
                foreach ($configuration as $nodeTypeName => $nodeTypeConfiguration) {
                    if (!isset($nodeTypeConfiguration['properties'])) {
                        continue;
                    }
                    foreach ($nodeTypeConfiguration['properties'] as $propertyName => $propertyConfiguration) {
                        if (isset($propertyConfiguration['type']) && $propertyConfiguration['type'] === 'date') {
                            $configuration[$nodeTypeName]['properties'][$propertyName]['type'] = 'DateTime';
                        }
                    }
                }
            },
            true
        );
    }
}
