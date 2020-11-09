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
 * Migrate constraints of nodeType and childNodes derived from 'Neos.Neos:ContentCollection'
 * after switching from allow '*' to allowing 'Neos.Neos:Content'
 */
class Version20201108113821 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getIdentifier()
    {
        return 'Neos.Neos-20201108113821';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->processConfiguration(
            'NodeTypes',
            function (&$configuration) {
                foreach ($configuration as &$nodeType) {

                    // transform constraints of nodes derived from Neos.Neos:ContentCollection
                    if (isset($nodeType['superTypes'])
                        && in_array('Neos.Neos:ContentCollection', $nodeType['superTypes'])
                        && isset($nodeType['constraints'])
                    ) {
                        $this->transformContentCollectionConstraints($nodeType['constraints']);
                    }

                    // transform constraints of childNodes that are of type Neos.Neos:ContentCollection
                    if (isset($nodeType['childNodes']) && is_array($nodeType['childNodes'])) {
                        foreach ($nodeType['childNodes'] as &$childNodeConfiguration) {
                            if (isset($childNodeConfiguration['type'])
                                && $childNodeConfiguration['type'] == 'Neos.Neos:ContentCollection'
                                && isset($childNodeConfiguration['constraints'])
                            ) {
                                $this->transformContentCollectionConstraints($childNodeConfiguration['constraints']);
                            }
                        }
                    }
                }
            },
            true
        );
    }

    /**
     * Ensure that contentCollections use 'Neos.Neos:Content' for base constraint rules instead of '*'
     *
     * @param array $constraintConfiguration
     */
    public function transformContentCollectionConstraints(&$constraintConfiguration) {
        if (isset($constraintConfiguration['nodeTypes']) && isset($constraintConfiguration['nodeTypes']['*'])) {
            $newNodeTypeConstraints = [];
            // instead of setting and unsetting the keys we iterate to keep the constraint order
            foreach ($constraintConfiguration['nodeTypes'] as $key => $value) {
                if ($key == '*') {
                    $key = 'Neos.Neos:Content';
                }
                $newNodeTypeConstraints[$key] = $value;
            }
            $constraintConfiguration['nodeTypes'] = $newNodeTypeConstraints;
        }
    }
}
