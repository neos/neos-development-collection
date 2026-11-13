<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\PhpstanRules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<FileNode>
 */
class DeclareStrictTypesRule implements Rule
{
    public function getNodeType(): string
    {
        return FileNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $nodes = $node->getNodes();
        if (0 === \count($nodes)) {
            return [];
        }
        $firstNode = \array_shift($nodes);

        if ($firstNode instanceof Node\Stmt\Declare_) {
            foreach ($firstNode->declares as $declare) {
                if (
                    $declare->value instanceof Node\Scalar\LNumber
                    && $declare->key->toLowerString() === 'strict_types'
                    && $declare->value->value === 1
                ) {
                    return [];
                }
            }
        }

        return [
            RuleErrorBuilder::message('File is missing a "declare(strict_types=1)" declaration.')->identifier('cr.missingStrictTypeDeclaration')->build(),
        ];
    }
}
