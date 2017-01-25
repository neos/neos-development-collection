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
 * Migrate usages of the Settings path Neos.Flow.security.authentication.providers.Typo3BackendProvider to Neos.Flow.security.authentication.providers[Neos.Neos:Backend]
 */
class Version20170115114620 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getIdentifier()
    {
        return 'Neos.Neos-20170115114620';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('Typo3BackendProvider', 'Neos.Neos:Backend', ['php', 'ts2', 'fusion', 'js', 'json', 'html']);
        $this->searchAndReplace('Typo3BackendProvider', "'Neos.Neos:Backend'", ['yaml']);
    }
}
