<?php

namespace Neos\Fusion\Core;

use Neos\Fusion;
use Neos\Utility\Arrays;

/**
 * Collection of methods for the Fusion Parser
 */
class AstBuilder
{
    /**
     * The Fusion object tree
     * @var array
     */
    protected $objectTree = [];

    public static function objectPathIsPrototype($path): bool
    {
        return ($path[count($path) - 2] ?? null) === '__prototypes';
    }

    public static function getParentPath($path): array
    {
        if (self::objectPathIsPrototype($path)) {
            return array_slice($path, 0, -2);
        }
        return array_slice($path, 0, -1);
    }

    public function setObjectTree(array $objectTree)
    {
        $this->objectTree = $objectTree;
    }

    public function getObjectTree(): array
    {
        return $this->objectTree;
    }

    public function removeValueInObjectTree($targetObjectPath)
    {
        $this->setValueInObjectTree($targetObjectPath, null);
        $this->setValueInObjectTree($targetObjectPath, ['__stopInheritanceChain' => true]);
    }

    public function copyValueInObjectTree($targetObjectPath, $sourceObjectPath)
    {
        $originalValue = $this->getValueFromObjectTree($sourceObjectPath);
        $value = is_object($originalValue) ? clone $originalValue : $originalValue;
        $this->setValueInObjectTree($targetObjectPath, $value);
    }

    public function inheritPrototypeInObjectTree($targetPrototypeObjectPath, $sourcePrototypeObjectPath)
    {
        if (count($targetPrototypeObjectPath) !== 2 || count($sourcePrototypeObjectPath) !== 2) {
            // one of the path has not a lenght of 2: this means
            // at least one path is nested (f.e. foo.prototype(Bar))
            // Currently, it is not supported to override the prototypical inheritance in
            // parts of the Fusion rendering tree.
            // Although this might work conceptually, it makes reasoning about the prototypical
            // inheritance tree a lot more complex; that's why we forbid it right away.
            throw new Fusion\Exception('Cannot inherit, when one of the sides is nested (e.g. foo.prototype(Bar)). Setting up prototype inheritance is only supported at the top level: prototype(Foo) < prototype(Bar)', 1358418019);
        }

        // it must be of the form "prototype(Foo) < prototype(Bar)"
        $targetPrototypeObjectPath[] = '__prototypeObjectName';
        $this->setValueInObjectTree($targetPrototypeObjectPath, end($sourcePrototypeObjectPath));
    }

    /**
     * Assigns a value to a node or a property in the object tree, specified by the object path array.
     *
     * @param array $objectPathArray The object path, specifying the node / property to set
     * @param mixed $value The value to assign, is a non-array type or an array with __eelExpression etc.
     * @param array|null $objectTree The current (sub-) tree, used internally - don't specify!
     * @return array The modified object tree
     */
    public function setValueInObjectTree(array $objectPathArray, $value, array &$objectTree = null): array
    {
        if ($objectTree === null) {
            $objectTree = &$this->objectTree;
        }

        $currentKey = array_shift($objectPathArray);
        if (is_numeric($currentKey)) {
            $currentKey = (int)$currentKey;
        }

        if (empty($objectPathArray)) {
            // last part of the iteration, setting the final value
            if (isset($objectTree[$currentKey]) && $value === null) {
                unset($objectTree[$currentKey]);
            } elseif (isset($objectTree[$currentKey]) && is_array($objectTree[$currentKey])) {
                if (is_array($value)) {
                    $objectTree[$currentKey] = Arrays::arrayMergeRecursiveOverrule($objectTree[$currentKey], $value);
                } else {
                    $objectTree[$currentKey]['__value'] = $value;
                    $objectTree[$currentKey]['__eelExpression'] = null;
                    $objectTree[$currentKey]['__objectType'] = null;
                }
            } else {
                $objectTree[$currentKey] = $value;
            }
        } else {
            // we still need to traverse further down
            if (isset($objectTree[$currentKey]) && is_array($objectTree[$currentKey]) === false) {
                // the element one-level-down is already defined, but it is NOT an array. So we need to convert the simple type to __value
                $objectTree[$currentKey] = [
                    '__value' => $objectTree[$currentKey],
                    '__eelExpression' => null,
                    '__objectType' => null
                ];
            } elseif (isset($objectTree[$currentKey]) === false) {
                $objectTree[$currentKey] = [];
            }

            $this->setValueInObjectTree($objectPathArray, $value, $objectTree[$currentKey]);
        }
        return $objectTree;
    }

    /**
     * Retrieves a value from a node in the object tree, specified by the object path array.
     *
     * @param array $objectPathArray The object path, specifying the node to retrieve the value of
     * @param array|string|null $objectTree The current (sub-) tree, used internally - don't specify!
     * @return mixed The value
     */
    public function &getValueFromObjectTree(array $objectPathArray, &$objectTree = null)
    {
        if ($objectTree === null) {
            $objectTree = &$this->objectTree;
        }

        if (count($objectPathArray) > 0) {
            $currentKey = array_shift($objectPathArray);
            if (is_numeric($currentKey)) {
                $currentKey = (int)$currentKey;
            }
            if (isset($objectTree[$currentKey]) === false) {
                $objectTree[$currentKey] = [];
            }
            $value = &$this->getValueFromObjectTree($objectPathArray, $objectTree[$currentKey]);
        } else {
            $value = &$objectTree;
        }
        return $value;
    }

    /**
     * Precalculate merged configuration for inherited prototypes.
     *
     * @return void
     * @throws Fusion\Exception
     */
    public function buildPrototypeHierarchy()
    {
        if (isset($this->objectTree['__prototypes']) === false) {
            return;
        }

        foreach ($this->objectTree['__prototypes'] as $prototypeName => $prototypeConfiguration) {
            $prototypeInheritanceHierarchy = [];
            $currentPrototypeName = $prototypeName;
            while (isset($this->objectTree['__prototypes'][$currentPrototypeName]['__prototypeObjectName'])) {
                $currentPrototypeName = $this->objectTree['__prototypes'][$currentPrototypeName]['__prototypeObjectName'];
                array_unshift($prototypeInheritanceHierarchy, $currentPrototypeName);
                if ($prototypeName === $currentPrototypeName) {
                    throw new Fusion\Exception(sprintf('Recursive inheritance found for prototype "%s". Prototype chain: %s', $prototypeName, implode(' < ', array_reverse($prototypeInheritanceHierarchy))), 1492801503);
                }
            }

            if (count($prototypeInheritanceHierarchy)) {
                // prototype chain from most *general* to most *specific* WITHOUT the current node type!
                $this->objectTree['__prototypes'][$prototypeName]['__prototypeChain'] = $prototypeInheritanceHierarchy;
            }
        }
    }
}
