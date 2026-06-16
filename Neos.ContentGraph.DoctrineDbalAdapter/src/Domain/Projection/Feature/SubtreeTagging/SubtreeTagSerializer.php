<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\SubtreeTagging;

/**
 * @internal
 */
final readonly class SubtreeTagSerializer
{
    private function __construct()
    {
    }

    /**
     * Decode a subtree tags JSON column into an associative array of tag key => (true for explicit, null for inherited).
     *
     * @return array<string, true|null>
     */
    public static function decodeSubtreeTags(?string $json): array
    {
        if ($json === null || $json === '' || $json === '{}' || $json === '[]') {
            return [];
        }
        /** @var array<string, true|null> $decoded */
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return $decoded;
    }

    /**
     * Encode a recomputed tag set back to its JSON column representation, forcing an object so an emptied set becomes
     * `{}` rather than `[]`.
     *
     * @param array<string, true|null> $tags
     */
    public static function encodeSubtreeTags(array $tags): string
    {
        if ($tags === []) {
            return '{}';
        }
        return json_encode($tags, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT);
    }

    /**
     * Order-independent comparison of two decoded subtree tag sets (so an unchanged relation is skipped regardless of
     * key order in the stored JSON).
     *
     * @param array<string, true|null> $a
     * @param array<string, true|null> $b
     */
    public static function subtreeTagsEqual(array $a, array $b): bool
    {
        if (count($a) !== count($b)) {
            return false;
        }
        return array_diff_assoc($a, $b) === [];
    }
}
