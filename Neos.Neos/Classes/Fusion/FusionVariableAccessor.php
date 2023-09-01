<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Fusion\Core\Runtime;

/**
 * Fusion makes no promise about the availability and type of any variable.
 * But in the context of the Neos Fusion rendering, we assume certain variables like `request` and `node`.
 * This accessor provides a type safe access to the conventional variables.
 */
final class FusionVariableAccessor
{
    public static function request(Runtime $runtime): ActionRequest
    {
        $value = $runtime->getContextVariable('request');
        if (!$value instanceof ActionRequest) {
            throw new \RuntimeException(sprintf('Expected Fusion variable "request" to be of type ActionRequest, got value of type "%s".', get_debug_type($value)), 1693557504154);
        }
        return $value;
    }

    public static function node(Runtime $runtime): Node
    {
        $value = $runtime->getContextVariable('node');
        if (!$value instanceof Node) {
            throw new \RuntimeException(sprintf('Expected Fusion variable "node" to be of type Node, got value of type "%s".', get_debug_type($value)), 1693558012279);
        }
        return $value;
    }

    public static function documentNode(Runtime $runtime): Node
    {
        $value = $runtime->getContextVariable('documentNode');
        if (!$value instanceof Node) {
            throw new \RuntimeException(sprintf('Expected Fusion variable "documentNode" to be of type Node, got value of type "%s".', get_debug_type($value)), 1693558014772);
        }
        return $value;
    }

    public static function site(Runtime $runtime): Node
    {
        $value = $runtime->getContextVariable('site');
        if (!$value instanceof Node) {
            throw new \RuntimeException(sprintf('Expected Fusion variable "site" to be of type Node, got value of type "%s".', get_debug_type($value)), 1693557504154);
        }
        return $value;
    }
}
