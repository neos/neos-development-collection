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
 * Migrate usages of the Neos.Neos:ImageUri and Neos.Neos:ImageTag Fusion prototypes to Neos.Media:ImageUri and Neos.Media:ImageTag
 */
class Version20191115204551 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getIdentifier()
    {
        return 'Neos.Neos-20191115204551';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('Neos.Neos:ImageUri', 'Neos.Media:ImageUri', ['fusion']);
        $this->searchAndReplace('Neos.Neos:ImageTag', 'Neos.Media:ImageTag', ['fusion']);
    }
}
