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
 * Migrate namespaces for fusion core implementation and helper classes
 */
class Version20161201222211 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.Neos-20161201222211';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('Neos\Neos\TypoScript', 'Neos\Neos\Fusion');
    }
}
