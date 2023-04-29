<?php
namespace Neos\Flow\Core\Migrations;

/*
 * This file is part of the Neos.NodeTypes package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Adjusts code to rename nodetypes that were extracted to subpackages in fusion and nodetype definitions
 */
class Version20190917101945 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.NodeTypes-20190917101945';
    }

    /**
     * @return void
     */
    public function up()
    {
        $nodetypeUpgradeMap = [
            'Neos.NodeTypes:AssetList' => 'Neos.NodeTypes.AssetList:AssetList',
            'Neos.NodeTypes:ContentReferences' => 'Neos.NodeTypes.ContentReferences:ContentReferences',
            'Neos.NodeTypes:Form' => 'Neos.NodeTypes.Form:Form',
            'Neos.NodeTypes:Html' => 'Neos.NodeTypes.Html:Html',
            'Neos.NodeTypes:Menu' => 'Neos.NodeTypes.Navigation:Navigation',
            'Neos.NodeTypes:Column' => 'Neos.NodeTypes.ColumnLayouts:Column',
            'Neos.NodeTypes:TwoColumn' => 'Neos.NodeTypes.ColumnLayouts:TwoColumn',
            'Neos.NodeTypes:ThreeColumn' => 'Neos.NodeTypes.ColumnLayouts:ThreeColumn',
            'Neos.NodeTypes:FourColumn' => 'Neos.NodeTypes.ColumnLayouts:FourColumn',
            'Neos.NodeTypes:Records' => 'Neos.NodeTypes.ContentReferences:ContentReferences'
        ];

        foreach ($nodetypeUpgradeMap as $search => $replace) {
            $this->searchAndReplace($search, $replace, ['fusion', 'ts2', 'yaml']);
        }
    }
}
