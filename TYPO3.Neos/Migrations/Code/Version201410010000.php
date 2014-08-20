<?php
namespace TYPO3\Flow\Core\Migrations;

/**
 * Migrates the former HtmlEditor to CodeEditor
 */
class Version201410010000 extends AbstractMigration {

	/**
	 * Renames all occurrences of the HtmlEditor to CodeEditor
	 *
	 * TYPO3.Neos/Inspector/Editors/HtmlEditor -> TYPO3.Neos/Inspector/Editors/CodeEditor
	 *
	 * @return void
	 */
	public function up() {
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
	public function down() {
		$this->searchAndReplace('TYPO3.Neos/Inspector/Editors/CodeEditor', 'TYPO3.Neos/Inspector/Editors/HtmlEditor', ['yaml']);
	}
}