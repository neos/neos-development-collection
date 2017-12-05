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
 * Migrate name for the Fusion cache to Neos_Neos_Fusion
 */
class Version20161219092345 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.Fusion-20161219092345';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TYPO3_Neos_TypoScript', 'Neos_Neos_Fusion', ['php', 'yaml']);
    }
}
