<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\StructureAdjustment;

use Neos\Error\Messages\Message;
use Neos\ContentRepository\SharedModel\Node\ReadableNodeAggregateInterface;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Projection\Content\NodeInterface;

#[Flow\Proxy(false)]
class StructureAdjustment extends Message
{
    public const TETHERED_NODE_MISSING = 'TETHERED_NODE_MISSING';
    public const NODE_IS_NOT_TETHERED_BUT_SHOULD_BE = 'NODE_IS_NOT_TETHERED_BUT_SHOULD_BE';
    public const TETHERED_NODE_TYPE_WRONG = 'TETHERED_NODE_TYPE_WRONG';
    public const DISALLOWED_TETHERED_NODE = 'DISALLOWED_TETHERED_NODE';
    public const NODE_TYPE_MISSING = 'NODE_TYPE_MISSING';
    public const DISALLOWED_CHILD_NODE = 'DISALLOWED_CHILD_NODE';
    public const OBSOLETE_PROPERTY = 'OBSOLETE_PROPERTY';
    public const MISSING_DEFAULT_VALUE = 'MISSING_DEFAULT_VALUE';
    public const NON_DESERIALIZABLE_PROPERTY = 'NON_DESERIALIZABLE_PROPERTY';
    public const TETHERED_NODE_WRONGLY_ORDERED = 'TETHERED_NODE_WRONGLY_ORDERED';
    public const NODE_COVERS_GENERALIZATION_OR_PEERS = 'NODE_COVERS_GENERALIZATION_OR_PEERS';

    private ?\Closure $adjustment;

    private string $type;

    /**
     * @param string $message An english error message which is used if no other error message can be resolved
     * @param int|null $code A unique error code
     * @param array<string,mixed> $arguments Array of arguments to be replaced in message
     * @api
     */
    private function __construct(
        string $message,
        ?int $code = null,
        array $arguments = [],
        string $type = '',
        ?\Closure $adjustment = null
    ) {
        parent::__construct($message, $code, $arguments);
        $this->adjustment = $adjustment;
        $this->type = $type;
    }

    public static function createForNode(
        NodeInterface $node,
        string $type,
        string $errorMessage,
        ?\Closure $remediation = null
    ): self {
        return new self(
            'Content Stream: %s; Dimension Space Point: %s, Node Aggregate: %s --- '
                . ($remediation ? '' : '!!!NOT AUTO-FIXABLE YET!!! ') . $errorMessage,
            null,
            [
                'contentStream' => $node->getContentStreamIdentifier()->jsonSerialize(),
                'dimensionSpacePoint' => json_encode($node->getOriginDimensionSpacePoint()->jsonSerialize()),
                'nodeAggregateIdentifier' => $node->getNodeAggregateIdentifier()->jsonSerialize(),
                'isAutoFixable' => ($remediation !== null)
            ],
            $type,
            $remediation
        );
    }

    public static function createForNodeAggregate(
        ReadableNodeAggregateInterface $nodeAggregate,
        string $type,
        string $errorMessage,
        ?\Closure $remediation = null
    ): self {
        return new self(
            'Content Stream: %s; Dimension Space Point: %s, Node Aggregate: %s --- '
                . ($remediation ? '' : '!!!NOT AUTO-FIXABLE YET!!! ') . $errorMessage,
            null,
            [
                'contentStream' => $nodeAggregate->getContentStreamIdentifier()->jsonSerialize(),
                'nodeAggregateIdentifier' => $nodeAggregate->getIdentifier()->jsonSerialize(),
                'isAutoFixable' => ($remediation !== null)
            ],
            $type,
            $remediation
        );
    }

    public function fix(): CommandResult
    {
        if ($this->adjustment === null) {
            return CommandResult::createEmpty();
        }

        $adjustment = $this->adjustment;
        $commandResult = $adjustment();
        assert($commandResult instanceof CommandResult);
        return $commandResult;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
