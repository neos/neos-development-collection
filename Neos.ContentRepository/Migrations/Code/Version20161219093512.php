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
 * Migrate name for the CR node type configuration
 */
class Version20161219093512 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.ContentRepository-20161219093512';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TYPO3_TYPO3CR_FullNodeTypeConfiguration', 'Neos_ContentRepository_FullNodeTypeConfiguration', ['php', 'yaml']);
    }
}
