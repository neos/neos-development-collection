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
use Neos\Fusion\DebugMessage;
use Neos\Fusion\Service\DebugStack;

/**
 * A Fusion object for debugging fusion-values
 *
 * If only value is given it is debugged directly. Otherwise all keys except title an plaintext are debugged.
 *
 * //fusionPath value The variable to display a dump of.
 * //fusionPath title $title optional custom title for the debug output
 * //fusionPath plaintext If true, the dump is in plain text, if false the debug output is in HTML format. If not specified, the mode is guessed from FLOW_SAPITYPE
 * @api
 */
class DebugImplementation extends JoinImplementation
{
    /**
     * If you iterate over "properties" these in here should usually be ignored.
     * For example additional properties in "Case" that are not "Matchers".
     *
     * @var array
     */
    protected $ignoreProperties = ['__meta', 'title', 'plaintext'];

    /**
     * @var DebugStack
     * @Flow\Inject
     */
    protected $stack;

    public function getTitle(): string
    {
        return $this->fusionValue('title') ?: '';
    }

    public function getPlaintext(): bool
    {
        return $this->fusionValue('plaintext') ?: false;
    }

    /**
     * Return the values in a human readable form
     *
     * @return string
     */
    public function evaluate()
    {
        $title = trim($this->getTitle());
        $plaintext = $this->getPlaintext();

        $debugData = [];
        foreach (array_keys($this->properties) as $key) {
            if (in_array($key, $this->ignoreProperties)) {
                continue;
            }
            $debugData[$key] = $this->fusionValue($key);
        }

        $title .= ' @ ' . $this->path;

        if (count($debugData) === 0) {
            $debugData = [null];
        }

        foreach ($debugData as $suffix => $data) {
            if (is_string($suffix)) {
                $message = (new DebugMessage(trim($title . '.' . $suffix), $this->path, $data, $plaintext));
            } else {
                $message = (new DebugMessage(trim($title), $this->path, $data, $plaintext));
            }
            $this->stack->register($message);
        }

        return $this->fusionValue('value');
    }
}
