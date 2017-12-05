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
 * Adjusts code to package renaming from "TYPO3.TypoScript" to "Neos.Fusion"
 */
class Version20161125013710 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.Fusion-20161125013710';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TYPO3\TypoScript', 'Neos\Fusion');
        $this->searchAndReplace('TYPO3.TypoScript', 'Neos.Fusion');

        $this->moveSettingsPaths('TYPO3.TypoScript', 'Neos.Fusion');
    }
}
