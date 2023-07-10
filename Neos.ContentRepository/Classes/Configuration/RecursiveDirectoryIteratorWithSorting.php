<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Configuration;

use Neos\Flow\Annotations as Flow;

/**
 * The {@see \RecursiveDirectoryIterator} doesn't order the returned files.
 *
 * https://www.php.net/manual/en/class.recursivedirectoryiterator.php#120971
 * > On Windows, you will get the files ordered by name. On Linux they are not ordered.
 *
 * To enforce a deterministic behavior, we wrap the RecursiveDirectoryIterator and sort the files level per level.
 *
 * This iterator is lazy, but even fetching one item will evaluate the inner RecursiveDirectoryIterator (one level of the directories)
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
 * @Flow\Proxy(false)
 * @internal
 */
class RecursiveDirectoryIteratorWithSorting implements \RecursiveIterator
{
    private const KEY = 0;

    private const FILE_INFO = 1;

    private const CHILDREN = 2;

    /**
     * @var \RecursiveDirectoryIterator
     */
    private $innerRecursiveDirectoryIterator;

    /**
     * The initialized one level structure of the inner RecursiveDirectoryIterator
     * @var array<string, array<int, mixed>
     */
    private array $files;

    public function __construct(\RecursiveDirectoryIterator $recursiveDirectoryIterator)
    {
        $this->innerRecursiveDirectoryIterator = $recursiveDirectoryIterator;
    }

    private function initialize(): void
    {
        if (isset($this->files)) {
            return;
        }
        $files = [];
        foreach ($this->innerRecursiveDirectoryIterator as $key => $fileInfo) {
            $files[$fileInfo->getFilename()] = [
                self::FILE_INFO => $fileInfo,
                self::CHILDREN => $this->innerRecursiveDirectoryIterator->hasChildren()
                    ? new self($this->innerRecursiveDirectoryIterator->getChildren())
                    : null,
                self::KEY => $key
            ];
        }
        ksort($files);
        $this->files = $files;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        $this->initialize();
        if (($c = current($this->files)) === false) {
            return false;
        }
        return $c[self::FILE_INFO];
    }

    public function next(): void
    {
        $this->initialize();
        next($this->files);
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        $this->initialize();
        if (($c = current($this->files)) === false) {
            throw new \OutOfBoundsException();
        }
        return $c[self::KEY];
    }

    public function valid(): bool
    {
        $this->initialize();
        return current($this->files) !== false;
    }

    public function rewind(): void
    {
        $this->initialize();
        reset($this->files);
    }

    public function hasChildren(): bool
    {
        $this->initialize();
        if (($c = current($this->files)) === false) {
            throw new \OutOfBoundsException();
        }
        return $c[self::CHILDREN] !== null;
    }

    public function getChildren(): \RecursiveIterator
    {
        $this->initialize();
        if (!$this->hasChildren()) {
            throw new \UnexpectedValueException(sprintf('Cannot recurse into %s', current($this->files)[self::FILE_INFO]->getFilename()));
        }
        $c = current($this->files);
        return $c[self::CHILDREN];
    }
}
