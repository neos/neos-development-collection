<?php
namespace Neos\Flow\Core\Migrations;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Rename "renderTypoScript" VH to just "render"
 */
class Version20130516235550 extends AbstractMigration
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
        return 'TYPO3.TypoScript-130516235550';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplaceRegex(
            '/(:render\\s+path=")([^"]+)"/',
            function (array $matches) {
                return $matches[1] . str_replace('/', '.', $matches[2] . '"');
            },
            array('html')
        );
    }
}
