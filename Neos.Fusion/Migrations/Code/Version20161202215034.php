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
 * Migrate name for the Fusion content cache to Neos_Fusion_Content
 */
class Version20161202215034 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.Fusion-20161202215034';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TYPO3_TypoScript_Content', 'Neos_Fusion_Content', ['php', 'yaml']);
    }
}
