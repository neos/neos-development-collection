<?php
declare(strict_types=1);

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

use Neos\Fusion\Migrations\FusionMigrationTrait;

/**
 * Replace legacy Neos.Fusion-FusionObjects with their counterparts
 */
class Version20251006080506 extends AbstractMigration
{
    use FusionMigrationTrait;

    public function getIdentifier(): string
    {
        return 'Neos.Fusion-20251006080506';
    }

    public function up(): void
    {
        $this->renameFusionPrototype('Neos.Fusion:Array', 'Neos.Fusion:Join');
        $this->renameFusionPrototype('Neos.Fusion:RawArray', 'Neos.Fusion:DataStructure');
        $this->renameFusionPrototype('Neos.Fusion:Collection', 'Neos.Fusion:Loop', 'Migration of Neos.Fusion:Collection to Neos.Fusion:Loop needs manual action. The key `collection` has to be renamed to `items` which cannot be done automatically');
        $this->renameFusionPrototype('Neos.Fusion:RawCollection', 'Neos.Fusion:Map', 'Migration of Neos.Fusion:RawCollection to Neos.Fusion:Map needs manual action. The key `collection` has to be renamed to `items` which cannot be done automatically');

        $this->addCommentToFusionPrototype('Neos.Fusion:Attributes', 'Neos.Fusion:Attributes will be removed without a replacement in Neos 9.0. You need to replace it by the property attributes in "Neos.Fusion:Tag" or apply the Eel helper "Neos.Array.toHtmlAttributesString(attributes)".');
    }
}
