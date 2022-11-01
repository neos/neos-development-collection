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

/**
 * Expand Neos.Fusion-FusionObjects without namespace to fully qualified names
 */
class Version20220326120900 extends AbstractMigration
{
    /**
     * The name of all prototypes from Neos.Fusion that could be used without
     * namespace previously because of the `default` namespace
     */
    protected array $namesToMigrate = [
        'Array',
        'RawArray',
        'Join',
        'DataStructure',
        'Template',
        'Case',
        'Matcher',
        'Renderer',
        'Value',
        'Component',
        'CanRender',
        'DebugDump',
        'Debug',
        'Collection',
        'RawCollection',
        'Loop',
        'Map',
        'Reduce',
        'Http.ResponseHead',
        'Http.Message',
        'Attributes',
        'Tag',
        'UriBuilder',
        'Augmenter',
        'ResourceUri',
        'Link.Action',
        'Link.Resource',
        'Fragment',
        'GlobalCacheIdentifiers'
    ];

    public function getIdentifier():string
    {
        return 'Neos.Fusion-20220326120900';
    }

    public function up():void
    {
        foreach ($this->namesToMigrate as $name) {
            // fusion object assignments
            $this->searchAndReplaceRegex('/(?<=\\=)([\\s]*)(' . preg_quote($name) . ')(?=[$\\s\\{])/u', '$1Neos.Fusion:$2', ['fusion']);
            // prototype declarations
            $this->searchAndReplaceRegex('/(?<=prototype\\()(' . preg_quote($name) . ')(?=\\))/u', 'Neos.Fusion:$1', ['fusion']);
        }
    }
}
