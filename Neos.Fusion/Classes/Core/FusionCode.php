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

#[Flow\Proxy(false)]
class FusionCode
{
    private function __construct(
        private string $sourceCode,
        private ?string $contextPathAndFilename
    ) {
        if (trim($sourceCode) === '') {
            throw FusionCodeIsInvalid::becauseTheSourceCodeIsEmpty($this->contextPathAndFilename);
        }
    }

    public static function fromString(string $string): self
    {
        return new self($string, null);
    }

    public static function fromFile(string $fileName): self
    {
        $sourceCode = file_get_contents($fileName);
        if ($sourceCode === false) {
            throw FusionCodeIsInvalid::becauseTheFileNameIsNotReadable($fileName);
        }
        return new self($sourceCode, $fileName);
    }

    /**
     * Watch out for unexpected behaviour {@link https://github.com/neos/neos-development-collection/issues/3835}
     */
    public static function fromDangerousPotentiallyDifferingSourceCodeAndContextPath(string $sourceCode, string $contextPathAndFilename): self
    {
        return new self($sourceCode, $contextPathAndFilename);
    }

    public function getSourceCode(): string
    {
        return $this->sourceCode;
    }

    public function getContextPathAndFilename(): ?string
    {
        return $this->contextPathAndFilename;
    }
}
