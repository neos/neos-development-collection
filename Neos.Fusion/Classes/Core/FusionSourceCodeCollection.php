<?php
declare(strict_types=1);

namespace Neos\Fusion\Core;

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
 * @implements \IteratorAggregate<int, FusionSourceCode>
 * @api
 */
final class FusionSourceCodeCollection implements \IteratorAggregate, \Countable
{
    /** @var array<int, FusionSourceCode> */
    private array $fusionCodeCollection;

    /**
     * @Flow\Autowiring(false)
     * @param $fusionSourceCode array<int, FusionSourceCode>
     */
    public function __construct(FusionSourceCode ...$fusionSourceCode)
    {
        $this->fusionCodeCollection = self::deduplicateItemsAndKeepLast($fusionSourceCode);
    }

    public static function fromFilePath(string $filePath): self
    {
        return new self(FusionSourceCode::fromFilePath($filePath));
    }

    public static function fromString(string $string): self
    {
        return new self(FusionSourceCode::fromString($string));
    }

    public static function tryFromFilePath(string $filePath): self
    {
        if (!is_readable($filePath)) {
            return self::empty();
        }
        return self::fromFilePath($filePath);
    }

    public static function tryFromPackageRootFusion(string $packageKey): self
    {
        $fusionPathAndFilename = sprintf('resource://%s/Private/Fusion/Root.fusion', $packageKey);
        return self::tryFromFilePath($fusionPathAndFilename);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function union(FusionSourceCodeCollection $other): self
    {
        return new self(...$this->fusionCodeCollection, ...$other->fusionCodeCollection);
    }

    /**
     * @return \Traversable<int,FusionSourceCode>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->fusionCodeCollection;
    }

    public function count(): int
    {
        return count($this->fusionCodeCollection);
    }

    /**
     * @param array<int|string, FusionSourceCode> $fusionSourceCode
     * @return array<int, FusionSourceCode>
     */
    private static function deduplicateItemsAndKeepLast(array $fusionSourceCode): array
    {
        $deduplicated = [];
        $includedFilePathsAndTheirPreviousIndex = [];
        foreach ($fusionSourceCode as $index => $sourceCode) {
            if (isset($includedFilePathsAndTheirPreviousIndex[$sourceCode->getFilePath()])) {
                unset($deduplicated[$includedFilePathsAndTheirPreviousIndex[$sourceCode->getFilePath()]]);
            }
            $deduplicated[$index] = $sourceCode;
            if ($sourceCode->getFilePath()) {
                $includedFilePathsAndTheirPreviousIndex[$sourceCode->getFilePath()] = $index;
            }
        }
        return array_values($deduplicated);
    }
}
