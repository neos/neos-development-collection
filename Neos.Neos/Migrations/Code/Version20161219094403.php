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
 * Migrate several cache keys for the Neos.Neos package
 */
class Version20161219094403 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.Neos-20161219094403';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TYPO3_Neos_Configuration_Version', 'Neos_Neos_Configuration_Version', ['php', 'yaml']);
        $this->searchAndReplace('TYPO3_Neos_XliffToJsonTranslations', 'Neos_Neos_XliffToJsonTranslations', ['php', 'yaml']);
        $this->searchAndReplace('TYPO3_Neos_LoginTokenCache', 'Neos_Neos_LoginTokenCache', ['php', 'yaml']);
    }
}
