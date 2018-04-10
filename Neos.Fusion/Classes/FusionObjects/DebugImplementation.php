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
    protected $ignoreProperties = ['__meta', 'title', 'plaintext', 'console', 'separator', 'maxDepth'];

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
     * @return bool|null
     */
    public function getConsole()
    {
        return $this->fusionValue('console');
    }

    /**
     * @return string|null
     */
    public function getSeparator()
    {
        return $this->fusionValue('separator');
    }

    /**
     * @return int|null
     */
    public function getMaxDepth()
    {
        return $this->fusionValue('maxDepth');
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
        $console = $this->getConsole();
        $separator = $this->getSeparator();
        $maxDepth = $this->getMaxDepth();

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
        if ($console) {
            return $this->dumpToConsole($debugData, $title, $separator, $maxDepth);
        }
        return \Neos\Flow\var_dump($debugData, $title, true, $plaintext);
    }

    /**
     * Return the values as JSON inside a script tag
     *
     * @param mixed $variable           The data that is supposed to be dumped
     * @param string $title             The title of the data
     * @param string|null $separator    The seperator used for name and class seperation
     * @param int|null $maxDepth        Maximum depth of the dump
     *
     * @return string
     */
    protected function dumpToConsole($variable, $title, $separator, $maxDepth)
    {
        $consoleDump = json_encode($this->renderConsoleDump($variable, 0, $title, $separator, $maxDepth));
        return ('<script>var neosFusionDebug = ' . $consoleDump . '; console.warn(neosFusionDebug);</script>');
    }

    /**
     * Function that renders the console dump
     *
     * @param mixed $variable           The data rendered for the dump
     * @param int $depth                Current depth
     * @param string|null $name         Name of the data
     * @param string|null $separator    The seperator used for name and class seperation
     * @param int|null $maxDepth        Maximum depth of the dump
     *
     * @return array
     */
    protected function renderConsoleDump($variable, $depth, $name, $separator, $maxDepth)
    {
        if ($separator === null) {
            $separator = '::';
        }
        if ($name === null) {
            $name = '';
        } else {
            $name .= $separator;
        }
        if ($maxDepth === null) {
            $maxDepth = 5;
        }
        $result = [];
        if ($depth > $maxDepth) {
            return ['RECURSION ... ' . chr(10)];
        }
        if (is_string($variable)) {
            $croppedValue = (strlen($variable) > 2000) ? substr($variable, 0, 2000) . '…' : $variable;
            $result[$name . 'string'] = $croppedValue;
        } elseif (is_numeric($variable)) {
            $result[$name . gettype($variable)] = $variable;
        } elseif (is_array($variable)) {
            $result[$name . 'array'] = $this->renderArray($variable, $depth, $separator, $maxDepth);
        } elseif (is_object($variable)) {
            $result[$name . gettype($variable)] =
                $this->renderObject($variable, $depth, $separator, $maxDepth);
        } elseif (is_bool($variable)) {
            $result[$name . 'boolean'] = $variable;
        } elseif (is_null($variable) || is_resource($variable)) {
            $result[$name . gettype($variable)] = gettype($variable);
        } else {
            $result[$name . gettype($variable)] = 'UNHANDLED TYPE';
        }
        return $result;
    }

    /**
     * Function that renders an array dump
     *
     * @param array $array      Array that is supposed to be dumped
     * @param int $depth        Current depth
     * @param string $separator The seperator used for name and class seperation
     * @param int $maxDepth     Maximum depth of the dump
     *
     * @return array
     */
    protected function renderArray($array, $depth, $separator, $maxDepth)
    {
        $result = [];
        array_walk($array, function ($value, $key) use (&$result, $depth, $separator, $maxDepth) {
            $renderedValue = $this->renderConsoleDump($value, $depth + 1, $key, $separator, $maxDepth);
            $renderedValueTitle = array_keys($renderedValue)[0];
            $result[$renderedValueTitle] = $renderedValue[$renderedValueTitle];
            unset($renderedValue);
            unset($renderedValueTitle);
        });
        return $result;
    }

    /**
     * Function that renders an object dump
     *
     * @param object $object    Object that is supposed to be dumped
     * @param int $depth        Current depth
     * @param string $separator The seperator used for name and class seperation
     * @param int $maxDepth     Maximum depth of the dump
     *
     * @return array
     */
    protected function renderObject($object, $depth, $separator, $maxDepth)
    {
        $result = [];
        $objectReflection = new \ReflectionObject($object);
        $properties = $objectReflection->getProperties();
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($object);
            $renderedValue = $this->renderConsoleDump($value, $depth + 1, $property->getName(), $separator, $maxDepth);
            $renderedValueTitle = array_keys($renderedValue)[0];
            $result[$renderedValueTitle] = $renderedValue[$renderedValueTitle];
            unset($renderedValue);
            unset($renderedValueTitle);
        }
        return $result;
    }
}
