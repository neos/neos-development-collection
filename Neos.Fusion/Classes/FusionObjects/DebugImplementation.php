<?php
namespace Neos\Fusion\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * A Fusion object for debugging ts-values
 *
 * If only value is given it is debugged directly. Otherwise all keys except title an plaintext are debugged.
 *
 * //tsPath value The variable to display a dump of.
 * //tsPath title $title optional custom title for the debug output
 * //tsPath plaintext If TRUE, the dump is in plain text, if FALSE the debug output is in HTML format. If not specified, the mode is guessed from FLOW_SAPITYPE
 * @api
 */
class DebugImplementation extends ArrayImplementation
{
    /**
     * If you iterate over "properties" these in here should usually be ignored.
     * For example additional properties in "Case" that are not "Matchers".
     *
     * @var array
     */
    protected $ignoreProperties = ['__meta', 'title', 'plaintext'];

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->fusionValue('title');
    }

    /**
     * @return mixed
     */
    public function getPlaintext()
    {
        return $this->fusionValue('plaintext');
    }

    /**
     * Return the values in a human readable form
     *
     * @return void|string
     */
    public function evaluate()
    {
        $title = $this->getTitle();
        $plaintext = $this->getPlaintext();

        $debugData = array();
        foreach (array_keys($this->properties) as $key) {
            if (in_array($key, $this->ignoreProperties)) {
                continue;
            }
            $debugData[$key] = $this->fusionValue($key);
        }

        if (count($debugData) === 0) {
            $debugData = null;
        } elseif (array_key_exists('value', $debugData) && count($debugData) === 1) {
            $debugData = $debugData['value'];
        }

        return \Neos\Flow\var_dump($debugData, $title, true, $plaintext);
    }
}
