<?php
declare(strict_types=1);

namespace Neos\Fusion\Core\Parser;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Fusion;
use Neos\Utility\Arrays;

/**
 * Collection of methods for the Fusion Parser
 * @internal
 */
class AstBuilder
{
    /**
     * The Fusion object tree
     * @var array
     */
    protected $objectTree = [];

    public static function objectPathIsPrototype(array $path): bool
    {
        return ($path[count($path) - 2] ?? null) === '__prototypes';
    }

    public static function getParentPath(array $path): array
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

    public function removeValueInObjectTree(array $targetObjectPath): void
    {
        $this->objectTree = Arrays::unsetValueByPath($this->objectTree, $targetObjectPath);
        $this->setValueInObjectTree($targetObjectPath, ['__stopInheritanceChain' => true]);
    }

    public function copyValueInObjectTree(array $targetObjectPath, array $sourceObjectPath): void
    {
        // retrieve a value in the object tree, specified by the object path array ($sourceObjectPath).
        $originalValue = Arrays::getValueByPath($this->objectTree, $sourceObjectPath);
        $this->setValueInObjectTree($targetObjectPath, $originalValue);
    }

    public function inheritPrototypeInObjectTree(array $targetPrototypeObjectPath, array $sourcePrototypeObjectPath): void
    {
        if (count($targetPrototypeObjectPath) !== 2 || count($sourcePrototypeObjectPath) !== 2) {
            // one of the path has not a length of 2: this means
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
     * @param scalar|null|array $value The value to assign, is a non-array type or an array with __eelExpression etc.
     */
    public function setValueInObjectTree(array $objectPathArray, $value): void
    {
        self::arraySetOrMergeValueByPathWithCallback($this->objectTree, $objectPathArray, $value, static function($simpleType) {
            return [
                '__value' => $simpleType,
                '__eelExpression' => null,
                '__objectType' => null
            ];
        });
    }

    protected static function arraySetOrMergeValueByPathWithCallback(array &$subject, array $path, $value, callable $toArray): void
    {
        // points to the current path element, but inside the tree.
        $pointer = &$subject;
        foreach ($path as $pathSegment) {
            // can be null because `&$foo['undefined'] === null`
            if ($pointer === null) {
                $pointer = [];
            }
            if (is_array($pointer) === false) {
                $pointer = $toArray($pointer);
            }
            // set pointer to current path (we can access undefined indexes due to &)
            $pointer = &$pointer[$pathSegment];
        }
        // we got a reference &$pointer of the $path in the $subject array, setting the final value:
        if (is_array($pointer)) {
            $arrayValue = is_array($value) ? $value : $toArray($value);
            $pointer = Arrays::arrayMergeRecursiveOverrule($pointer, $arrayValue);
            return;
        }
        $pointer = $value;
    }

    /**
     * Precalculate merged configuration for inherited prototypes.
     *
     * @return void
     * @throws Fusion\Exception
     */
    public function buildPrototypeHierarchy(): void
    {
        if (isset($this->objectTree['__prototypes']) === false) {
            return;
        }

        foreach (array_keys($this->objectTree['__prototypes']) as $prototypeName) {
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
