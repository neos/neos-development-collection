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
class Version20200120114136 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.NodeTypes-20200120114136';
    }

    /**
     * @return void
     */
    public function up()
    {
        $nodetypeUpgradeMap = [
            'Neos.NodeTypes:MultiColumnItem' => 'Neos.NodeTypes.ColumnLayouts:MultiColumnItem',
            'Neos.NodeTypes:MultiColumn' => 'Neos.NodeTypes.ColumnLayouts:MultiColumn',
        ];

        foreach ($nodetypeUpgradeMap as $search => $replace) {
            $this->searchAndReplace($search, $replace, ['fusion', 'ts2', 'yaml']);
        }
    }
}
