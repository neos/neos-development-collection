<?php
namespace Neos\Flow\Core\Migrations;

/*
 * This file is part of the TYPO3.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Adjust to renamed NodeLabelGeneratorInterface.
 * You should refactor your custom NodeLabelGenerators to implement the
 * (new) NodeLabelGeneratorInterface.
 */
class Version20140911160326 extends AbstractMigration
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
        return 'TYPO3.TYPO3CR-140911160326';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('NodeLabelGeneratorInterface', 'NodeDataLabelGeneratorInterface', array('php'));
        $this->searchAndReplace('DefaultNodeLabelGenerator', 'FallbackNodeDataLabelGenerator', array('php'));

        $this->processConfiguration(
            'NodeTypes',
            function (&$configuration) {
                foreach ($configuration as &$nodeType) {
                    if (isset($nodeType['nodeLabelGenerator'])) {
                        $nodeType['label'] = array(
                            'generatorClass' => $nodeType['nodeLabelGenerator']
                        );
                        unset($nodeType['nodeLabelGenerator']);
                    }
                }
            },
            true
        );
    }
}
