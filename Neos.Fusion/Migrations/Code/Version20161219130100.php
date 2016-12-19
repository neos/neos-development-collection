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
 * Migrate name for the TypoScriptView to FusionView
 */
class Version20161219130100 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getIdentifier()
    {
        return 'Neos.Fusion-20161219130100';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TypoScriptView', 'FusionView', ['php']);
        $this->searchAndReplace('setTypoScriptPath', 'setFusionPath', ['php']);
        $this->searchAndReplace('setTypoScriptPathPattern', 'setFusionPathPattern', ['php']);
        $this->searchAndReplace('typoScriptPath', 'fusionPath', ['php']);
        $this->searchAndReplace('typoScriptPathPatterns', 'fusionPathPatterns', ['php']);
    }
}
