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
 * Expand Neos.Neos-FusionObjects without namespace to fully qualified names
 */
class Version20180211175500 extends AbstractMigration
{
    /**
     * The name of all prototypes from Neos.Neos that could be used without
     * namespace previously because the `default`-namespace was overwritten
     * in Neos
     *
     * @var array
     */
    protected $namesToMigrate = [
        'BreadcrumbMenu',
        'Content',
        'Menu',
        'ContentCase',
        'ContentCollectionRenderer',
        'ContentCollection',
        'ContentComponent',
        'ContentElementEditable',
        'ContentElementWrapping',
        'ConvertNodeUris',
        'ConvertUris',
        'DimensionsMenu',
        'Document',
        'DocumentMetadata',
        'Editable',
        'FallbackNode',
        'ImageUri',
        'ImageTag',
        'Menu',
        'NodeUri',
        'Page',
        'Plugin',
        'PluginView',
        'PrimaryContent',
        'Shortcut',
        'RawContent'
    ];

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return 'Neos.Fusion-20180211175500';
    }

    /**
     * @return void
     */
    public function up()
    {
        foreach ($this->namesToMigrate as $name) {
            // fusion object assignments
            $this->searchAndReplaceRegex('/(?<=\\=)([\\s]*)(' . preg_quote($name) . ')(?=[$\\s\\{])/u', '$1Neos.Neos:$2', ['ts2', 'fusion']);
            // prototype declarations
            $this->searchAndReplaceRegex('/(?<=prototype\\()(' . preg_quote($name) . ' )(?=\\))/u', 'Neos.Neos:$1', ['ts2', 'fusion']);
        }
    }
}
