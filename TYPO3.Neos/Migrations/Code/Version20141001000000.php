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
 * Migrates the former HtmlEditor to CodeEditor
 */
class Version20141001000000 extends AbstractMigration
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
        return 'TYPO3.Neos-201410010000';
    }

    /**
     * Renames all occurrences of the HtmlEditor to CodeEditor
     *
     * TYPO3.Neos/Inspector/Editors/HtmlEditor -> TYPO3.Neos/Inspector/Editors/CodeEditor
     *
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TYPO3.Neos/Inspector/Editors/HtmlEditor', 'TYPO3.Neos/Inspector/Editors/CodeEditor', ['yaml']);
    }

    /**
     * Renames all occurrences of the CodeEditor to HtmlEditor
     *
     * TYPO3.Neos/Inspector/Editors/CodeEditor -> TYPO3.Neos/Inspector/Editors/HtmlEditor
     * .neos-secondary-inspector-code-editor -> .neos-secondary-inspector-html-editor
     *
     * @return void
     */
    public function down()
    {
        $this->searchAndReplace('TYPO3.Neos/Inspector/Editors/CodeEditor', 'TYPO3.Neos/Inspector/Editors/HtmlEditor', ['yaml']);
    }
}
