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
 * Migrate usages of TypoScriptService to FusionService
 */
class Version20161219122512 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getIdentifier()
    {
        return 'Neos.Neos-20161219122512';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TypoScriptService', 'FusionService');
        $this->searchAndReplace('typoScriptService', 'fusionService');
        $this->searchAndReplace('setSiteRootTypoScriptPattern', 'setSiteRootFusionPattern');
        $this->searchAndReplace('getPrependTypoScriptIncludes', 'getPrependFusionIncludes');
        $this->searchAndReplace('setPrependTypoScriptIncludes', 'setPrependFusionIncludes');
        $this->searchAndReplace('getAppendTypoScriptIncludes', 'getAppendFusionIncludes');
        $this->searchAndReplace('setAppendTypoScriptIncludes', 'setAppendFusionIncludes');
    }
}
