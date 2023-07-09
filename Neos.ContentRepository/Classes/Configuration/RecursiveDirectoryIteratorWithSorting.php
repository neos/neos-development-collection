<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Configuration;

/**
 * The {@see \RecursiveDirectoryIterator} doesn't order the returned files.
 *
 * https://www.php.net/manual/en/class.recursivedirectoryiterator.php#120971
 * > On Windows, you will get the files ordered by name. On Linux they are not ordered.
 *
 * To enforce a deterministic behavior, we wrap the RecursiveDirectoryIterator and sort the files level per level.
 *
 * This iterator is not lazy, as the construction alone will evaluate the inner RecursiveDirectoryIterator
 *
 * The sorting strategy is as follows:
 *
 * We sort directory by directory via the base name.
 *
 * This structure (unordered, so show how it might be scanned by the RecursiveDirectoryIterator)
 *
 * Content/
 * ├─ Z.yaml
 * ├─ A.yaml
 * ├─ Columns/
 * │  ├─ B.yaml
 * │  ├─ A.yaml
 *
 * Will be sorted into
 *
 * Content/
 * ├─ A.yaml
 * ├─ Columns/
 * │  ├─ A.yaml
 * │  ├─ B.yaml
 * ├─ Z.yaml
 *
 * so after applying an {@see \RecursiveIteratorIterator} one will get
 *
 * Content/A.yaml
 * Content/Columns/A.yaml
 * Content/Columns/B.yaml
 * Content/Z.yaml
 *
 */
class RecursiveDirectoryIteratorWithSorting implements \RecursiveIterator
{
    private const KEY = 0;

    private const FILE_INFO = 1;

    private const CHILDREN = 2;

    private \RecursiveDirectoryIterator $recursiveDirectoryIterator;

    private array $files;

    public function __construct(\RecursiveDirectoryIterator $recursiveDirectoryIterator)
    {
        $this->recursiveDirectoryIterator = $recursiveDirectoryIterator;
        $files = [];
        foreach ($this->recursiveDirectoryIterator as $fileInfo) {
            $files[$fileInfo->getFilename()] = [
                self::FILE_INFO => $fileInfo,
                self::CHILDREN => $this->recursiveDirectoryIterator->hasChildren() ? $this->recursiveDirectoryIterator->getChildren() : null,
                self::KEY => $this->recursiveDirectoryIterator->key()
            ];
        }
        ksort($files);
        $this->files = $files;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        if (($c = current($this->files)) === false) {
            return false;
        }
        return $c[self::FILE_INFO];
    }

    public function next(): void
    {
        next($this->files);
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        if (($c = current($this->files)) === false) {
            throw new \OutOfBoundsException();
        }
        return $c[self::KEY];
    }

    public function valid(): bool
    {
        return current($this->files) !== false;
    }

    public function rewind(): void
    {
        reset($this->files);
    }

    public function hasChildren(): bool
    {
        if (($c = current($this->files)) === false) {
            throw new \OutOfBoundsException();
        }
        return $c[self::CHILDREN] !== null;
    }

    public function getChildren(): \RecursiveIterator
    {
        if (!$this->hasChildren()) {
            throw new \UnexpectedValueException(sprintf('Cannot recurse into %s', current($this->files)[self::FILE_INFO]->getFilename()));
        }
        $c = current($this->files);
        return new self($c[self::CHILDREN]);
    }
}
