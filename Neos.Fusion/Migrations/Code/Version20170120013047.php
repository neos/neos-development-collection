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
class Version20170120013047 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getIdentifier()
    {
        return 'Neos.Fusion-20170120013047';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('Neos\Fusion\TypoScriptObjects', 'Neos\Fusion\FusionObjects', ['php']);
        $this->searchAndReplace('AbstractTypoScriptObject', 'AbstractFusionObject', ['php']);
        $this->searchAndReplace('AbstractArrayTypoScriptObject', 'AbstractArrayFusionObject', ['php']);
    }
}
