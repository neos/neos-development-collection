<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion\Cache;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Fusion\Core\Cache\FusionContextSerializer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Serializer for Fusion's \@cache.context values
 *
 * Implements special handing for serializing {@see Node} objects in fusions cache context:
 *
 *     \@cache {
 *       mode = 'uncached'
 *       context {
 *         1 = 'node'
 *       }
 *     }
 *
 * The property mapper cannot be relied upon to serialize nodes, as this is willingly not implemented.
 *
 * Serializing falls back to Fusion's standard {@see FusionContextSerializer} which uses Flow's property mapper.
 *
 * @internal
 */
final class NeosFusionContextSerializer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private readonly FusionContextSerializer $fusionContextSerializer,
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry
    ) {
    }

    /**
     * @param array<int|string,mixed> $context
     */
    public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
    {
        if ($type === Node::class) {
            /** @var $data array<string, mixed> */
            return $this->tryDeserializeNode($data);
        }
        return $this->fusionContextSerializer->denormalize($data, $type, $format, $context);
    }

    /**
     * @param array<int|string,mixed> $context
     * @return array<int|string,mixed>
     */
    public function normalize(mixed $object, string $format = null, array $context = [])
    {
        if ($object instanceof Node) {
            return $this->serializeNode($object);
        }
        return $this->fusionContextSerializer->normalize($object, $format, $context);
    }

    /**
     * @param array<string,mixed> $serializedNode
     */
    private function tryDeserializeNode(array $serializedNode): ?Node
    {
        $nodeAddress = NodeAddress::fromArray($serializedNode);

        $contentRepository = $this->contentRepositoryRegistry->get($nodeAddress->contentRepositoryId);

        try {
            $subgraph = $contentRepository->getContentGraph($nodeAddress->workspaceName)->getSubgraph(
                $nodeAddress->dimensionSpacePoint,
                $nodeAddress->workspaceName->isLive()
                    ? VisibilityConstraints::frontend()
                    : VisibilityConstraints::withoutRestrictions()
            );
        } catch (WorkspaceDoesNotExist $exception) {
            // in case the workspace was deleted the rendering should probably not come to this very point
            // still if it does we fail silently
            // this is also the behaviour for when the property mapper is used
            return null;
        }

        $node = $subgraph->findNodeById($nodeAddress->aggregateId);
        if (!$node) {
            // instead of crashing the whole rendering, by silently returning null we will most likely just break
            // rendering of the sub part here that needs the node
            // this is also the behaviour for when the property mapper is used
            return null;
        }
        return $node;
    }

    /**
     * @return array<int|string,mixed>
     */
    private function serializeNode(Node $source): array
    {
        return NodeAddress::fromNode($source)->jsonSerialize();
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null)
    {
        return true;
    }

    public function supportsNormalization(mixed $data, string $format = null)
    {
        return true;
    }
}
