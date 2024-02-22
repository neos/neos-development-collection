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

use Neos\Fusion;

final class FusionSourceCode
{
    protected function __construct(
        private ?string $filePath,
        private string|\Closure $sourceCodeOrFactory,
    ) {
    }

    public static function fromString(string $string): self
    {
        return new self(null, $string);
    }

    public static function fromFilePath(string $filePath): self
    {
        if (is_readable($filePath) === false) {
            throw new Fusion\Exception("Trying to read Fusion source code from file, but '$filePath' is not readable.", 1657963790);
        }
        return new self($filePath, fn () => file_get_contents($filePath));
    }

    /**
     * Watch out for unexpected behaviour {@link https://github.com/neos/neos-development-collection/issues/3835}
     */
    public static function fromDangerousPotentiallyDifferingSourceCodeAndFilePath(string $sourceCode, string $filePath): self
    {
        return new self($filePath, $sourceCode);
    }

    public function getSourceCode(): string
    {
        return $this->sourceCodeOrFactory instanceof \Closure
            ? $this->sourceCodeOrFactory = ($this->sourceCodeOrFactory)()
            : $this->sourceCodeOrFactory;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }
}
