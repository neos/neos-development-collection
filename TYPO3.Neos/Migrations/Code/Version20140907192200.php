<?php
namespace TYPO3\Flow\Core\Migrations;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Adjust to updated folder name for TypoScript in site packages
 */
class Version20140907192200 extends AbstractMigration
{
    /**
     * NOTE: This method is overridden for historical reasons. Previously code migrations were expected to consist of the
     * string "Version" and a 12-character timestamp suffix. The suffix has been changed to a 14-character timestamp.
     * For new migrations the classname pattern should be "Version<YYYYMMDDhhmmss>" (14-character timestamp) and this method should *not* be implemented
     *
     * @return string
     */
    public function getIdentifier()
    {
        return 'TYPO3.Neos-201409071922';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->moveFile('Resources/Private/TypoScripts/Library/*', 'Resources/Private/TypoScript');
        $this->searchAndReplace('Resources/Private/TypoScripts/Library/', 'Resources/Private/TypoScript/', array('ts2'));
    }
}
