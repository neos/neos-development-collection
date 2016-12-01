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
 * Moves fusion files from old path ``Resources/Private/TypoScript/`` to the new path
 * ``Resources/Private/Fusion``
 */
class Version20161201202543 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.Fusion-20161201202543';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->moveFile('Resources/Private/TypoScript/*', 'Resources/Private/Fusion');
        $this->searchAndReplace('/Private/TypoScript/', '/Private/Fusion/', ['ts2', 'fusion']);

        // Adjust root fusion file to our autoIncludePattern. This is necessary as we can only search for a
        // singe legacy auto include pattern file wich is Private/TypoScript/Root.ts2 - Private/Fusion/Root.ts2
        // would not be respected
        $this->moveFile('Resources/Private/Fusion/Root.ts2', 'Resources/Private/Fusion/Root.fusion');
    }
}
