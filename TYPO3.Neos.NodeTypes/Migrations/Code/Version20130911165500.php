<?php
namespace TYPO3\Flow\Core\Migrations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Neos.NodeTypes".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Change node type and TS object names in NodeTypes.yaml and PHP code.
 *
 * NOTE: we deliberately do NOT change TypoScript files here, as the TypoScript Object "TYPO3.Neos:Page" is DIFFERENT than
 * TYPO3.Neos.NodeTypes:Page. We might have a naming collision there; but that's not the scope of this change.
 *
 * TYPO3.Neos:Page -> TYPO3.Neos.NodeTypes:Page
 */
class Version20130911165500 extends AbstractMigration
{
    /**
     * NOTE: This method is overridden for historical reasons. Previously code migrations were expected to consist of the
     * string "Version" and a 12-character timestamp suffix. The suffix has been changed to a 14-character timestamp.
     * For new migrations the classname pattern should be "Version<YYYYMMDDhhmmss>" (14-character timestamp) and this method should *not* be implemented
     *
     * @return string
     */
    public function getIdentifier()
    {
        return 'TYPO3.Neos.NodeTypes-201309111655';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TYPO3.Neos:Page', 'TYPO3.Neos.NodeTypes:Page', array('php'));

        $this->processConfiguration(
            'NodeTypes',
            function (&$configuration) {
                foreach ($configuration as &$nodeType) {
                    if (isset($nodeType['superTypes'])) {
                        foreach ($nodeType['superTypes'] as &$superType) {
                            $superType = str_replace('TYPO3.Neos:Page', 'TYPO3.Neos.NodeTypes:Page', $superType);
                        }
                    }
                    if (isset($nodeType['childNodes'])) {
                        foreach ($nodeType['childNodes'] as &$type) {
                            $type = str_replace('TYPO3.Neos:Page', 'TYPO3.Neos.NodeTypes:Page', $type);
                        }
                    }
                }
            },
            true
        );
    }
}
