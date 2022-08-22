<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Rector\Utility;

use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class CodeSampleLoader
{
    static function fromFile(string $description, string $rectorClassName): RuleDefinition
    {
        $shortName = (new \ReflectionClass($rectorClassName))->getShortName();
        $fileName = __DIR__ . '/../../tests/Rules/' . $shortName . '/Fixture/some_class.php.inc';
        list($beforeCode, $afterCode) = explode('-----', file_get_contents($fileName));
        return new RuleDefinition($description, [new CodeSample(trim($beforeCode), trim($afterCode))]);
    }
}
