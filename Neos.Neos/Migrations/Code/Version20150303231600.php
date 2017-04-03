<?php
namespace Neos\Flow\Core\Migrations;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Migrates ImageVariant to ImageInterface
 */
class Version20150303231600 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'TYPO3.Neos-20150303231600';
    }

    /**
     * Renames all ImageVariant property types to ImageInterface
     *
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TYPO3\Media\Domain\Model\ImageVariant', 'TYPO3\Media\Domain\Model\ImageInterface', ['yaml', 'ts2']);
    }
}
