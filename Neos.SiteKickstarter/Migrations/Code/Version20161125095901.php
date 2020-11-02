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
 * Fix wronly renamed Neos.SiteKickstarter references. There was a replacement from "TYPO3.Neos" to "Neos.Neos"; which inadvertedly also got applied
 * to the site kickstarter packages. That's why we fix this by renaming "Neos.Neos.Kickstarter" to "Neos.SiteKickstarter"
 */
class Version20161125095901 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.SiteKickstarter-20161125095901';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('Neos\Neos\Kickstarter', 'Neos\SiteKickstarter');
        $this->searchAndReplace('Neos.Neos.Kickstarter', 'Neos.SiteKickstarter');
    }
}
