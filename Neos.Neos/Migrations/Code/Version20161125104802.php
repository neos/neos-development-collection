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
 * Adjusts code to package renaming from "TYPO3.Neos" to "Neos.Neos" in Fusion files
 */
class Version20161125104802 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.Neos-20161125104802';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TYPO3.Neos', 'Neos.Neos', ['ts2', 'ts']);
    }
}
