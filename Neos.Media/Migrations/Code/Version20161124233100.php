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
 * Adjusts code to package renaming from "TYPO3.Media" to "Neos.Media"
 */
class Version20161124233100 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.Media-20161124233100';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TYPO3\Media', 'Neos\Media');
        $this->searchAndReplace('TYPO3.Media', 'Neos.Media');

        $this->moveSettingsPaths('TYPO3.Media', 'Neos.Media');
    }
}
