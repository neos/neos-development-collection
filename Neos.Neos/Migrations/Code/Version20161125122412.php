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
 * Allow to migrate Sites.xml files
 */
class Version20161125122412 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.Neos-20161125122412';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('__classname="TYPO3\Media', '__classname="Neos\Media', 'Resources/Private/Content/Sites.xml');
        $this->searchAndReplace('__type&quot;:&quot;TYPO3\\\\Media', '__type&quot;:&quot;Neos\\\\Media', 'Resources/Private/Content/Sites.xml');

        $this->searchAndReplace('nodeType="TYPO3.Neos.NodeTypes:', 'nodeType="Neos.NodeTypes:', 'Resources/Private/Content/Sites.xml');
        $this->searchAndReplace('nodeType="TYPO3.Neos:', 'nodeType="Neos.Neos:', 'Resources/Private/Content/Sites.xml');
    }
}
