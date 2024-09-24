<?php

declare(strict_types=1);

namespace Neos\Fusion\Core;

use Neos\Eel\EelInvocationTracerInterface;
use Neos\Flow\Annotations as Flow;
use Psr\Log\LoggerInterface;

final class EelNeosDeprecationTracer implements EelInvocationTracerInterface
{
    /** @Flow\Inject */
    protected LoggerInterface $logger;

    /**
     * These are the lowercase names of the Neos 9 Node fields that were removed in 9.0.
     * Only the fields name, nodeTypeName and properties will continue to exist.
     */
    private const LEGACY_NODE_FIELDS = [
        'accessrestrictions' => true,
        'accessroles' => true,
        'accessible' => true,
        'autocreated' => true,
        'cacheentryidentifier' => true,
        'childnodes' => true,
        'contentobject' => true,
        'context' => true,
        'contextpath' => true,
        'creationdatetime' => true,
        'depth' => true,
        'dimensions' => true,
        'hidden' => true,
        'hiddenafterdatetime' => true,
        'hiddenbeforedatetime' => true,
        'hiddeninindex' => true,
        'identifier' => true,
        'index' => true,
        'label' => true,
        'lastmodificationdatetime' => true,
        'lastpublicationdatetime' => true,
        'nodeaggregateidentifier' => true,
        'nodedata' => true,
        'nodename' => true,
        'nodetype' => true,
        'numberofchildnodes' => true,
        'othernodevariants' => true,
        'parent' => true,
        'parentpath' => true,
        'path' => true,
        'primarychildnode' => true,
        'propertynames' => true,
        'removed' => true,
        'root' => true,
        'tethered' => true,
        'visible' => true,
        'workspace' => true,
    ];

    public function __construct(
        private readonly string $eelExpression,
        private readonly bool $throwExceptions,
    ) {
    }

    public function recordPropertyAccess(object $object, string $propertyName): void
    {
        if (
            // deliberate cross package reference from Neos.Fusion to simplify the wiring of this temporary migration helper
            $object instanceof \Neos\ContentRepository\Domain\Model\Node
            && array_key_exists(strtolower($propertyName), self::LEGACY_NODE_FIELDS)
        ) {
            $this->logDeprecationOrThrowException(
                sprintf('The node field "%s" is deprecated in "%s"', $propertyName, $this->eelExpression)
            );
        }
    }

    public function recordMethodCall(object $object, string $methodName, array $arguments): void
    {
    }

    public function recordFunctionCall(callable $function, string $functionName, array $arguments): void
    {
    }

    private function logDeprecationOrThrowException(string $message): void
    {
        if ($this->throwExceptions) {
            throw new \RuntimeException($message);
        } else {
            $this->logger->debug($message);
        }
    }
}
