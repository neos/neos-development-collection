<?php
namespace TYPO3\Flow\Core\Migrations;

/**
 * Migrates ImageVariant to ImageInterface
 */
class Version20150303231600 extends AbstractMigration {

	/**
	 * Renames all ImageVariant property types to ImageInterface
	 *
	 * @return void
	 */
	public function up() {
		$this->searchAndReplace('TYPO3\Media\Domain\Model\ImageVariant', 'TYPO3\Media\Domain\Model\ImageInterface', ['yaml', 'ts2']);
	}

}