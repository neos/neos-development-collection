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
 * Migrate name for the media image size cache
 */
class Version20161219094126 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.Media-20161219094126';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TYPO3_Media_ImageSize', 'Neos_Media_ImageSize', ['php', 'yaml']);
    }
}
