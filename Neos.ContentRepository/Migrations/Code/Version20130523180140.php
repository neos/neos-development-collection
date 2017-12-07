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
 * Adjust to removed TYPO3.ContentRepository:Folder node type by replacing it
 * with unstructured. In a Neos context, you probably want to replace
 * it with TYPO3.Neos:Document instead!
 */
class Version20130523180140 extends AbstractMigration
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
        return 'TYPO3.TYPO3CR-130523180140';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TYPO3.TYPO3CR:Folder', 'unstructured', array('php', 'ts2'));

        $this->processConfiguration(
            'NodeTypes',
            function (&$configuration) {
                foreach ($configuration as &$nodeType) {
                    if (isset($nodeType['superTypes'])) {
                        $this->replaceArrayKeysOrValues($nodeType['superTypes'], 'TYPO3.TYPO3CR:Folder', 'unstructured');
                    }
                    if (isset($nodeType['childNodes'])) {
                        $this->replaceArrayKeysOrValues($nodeType['childNodes'], 'TYPO3.TYPO3CR:Folder', 'unstructured');
                    }
                }
            },
            true
        );
    }

    /**
     * Iterates through the given $array and replaces $oldValue by $newValue
     * If the array is associative it will replace the keys, otherwise the values
     *
     * Example:
     * ['some', '<oldValue>'] => ['some', '<newValue>']
     * ['some' => 'foo', '<oldValue>' => 'bar'] => ['some' => 'foo', '<newValue>' => 'bar']
     *
     * @param array $array
     * @param string $oldValue
     * @param string $newValue
     * @return void
     */
    private function replaceArrayKeysOrValues(array &$array, $oldValue, $newValue)
    {
        if ($array === []) {
            return;
        }
        $isAssoc = array_keys($array) !== range(0, count($array) - 1);

        if ($isAssoc) {
            $keys = array_keys($array);
            $index = array_search($oldValue, $keys, true);
            if ($index === false) {
                return;
            }
            $keys[$index] = $newValue;
            $array = array_combine($keys, $array);
        } else {
            $array = str_replace($oldValue, $newValue, $array);
        }
    }
}
