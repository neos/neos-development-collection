<?php
namespace Neos\Flow\Core\Migrations;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Adjusts code to package renaming from "TYPO3.TYPO3CR" to "Neos.ContentRepository"
 */
class Version20161125012000 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.ContentRepository-20161125012000';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TYPO3\TYPO3CR', 'Neos\ContentRepository');
        $this->searchAndReplace('TYPO3.TYPO3CR', 'Neos.ContentRepository');

        $this->moveSettingsPaths('TYPO3.TYPO3CR', 'Neos.ContentRepository');
    }
}
