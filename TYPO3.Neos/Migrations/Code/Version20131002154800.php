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
 * Change TS object names in TypoScript files:
 *
 * ContentCollection.Default -> ContentCollection
 * PrimaryContentCollection -> PrimaryContent
 */
class Version20131002154800 extends AbstractMigration
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
        return 'TYPO3.Neos-201310021548';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('ContentCollection.Default', 'ContentCollection', array('ts2'));
        $this->searchAndReplace('PrimaryContentCollection', 'PrimaryContent', array('ts2'));
    }
}
