<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\ReferenceLabel;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\Eel\EelEvaluatorInterface;
use Neos\Eel\Utility;
use Neos\Flow\Annotations as Flow;

/**
 * The expression based reference label generator that is used as default if a label expression is configured.
 *
 * @internal please reference the interface {@see ReferenceLabelGeneratorInterface} instead.
 */
class ExpressionBasedReferenceLabelGenerator implements ReferenceLabelGeneratorInterface
{
    #[Flow\Inject]
    protected EelEvaluatorInterface $eelEvaluator;

    /**
     * @var array<string, string>
     */
    #[Flow\InjectConfiguration('labelGenerator.eel.defaultContext', 'Neos.Neos')]
    protected ?array $defaultContextConfiguration;

    protected string $expression = <<<'EEL'
    ${reference.referenceTypeName.value}
    EEL;

    public function getExpression(): string
    {
        return $this->expression;
    }

    public function setExpression(string $expression): void
    {
        $this->expression = $expression;
    }

    /**
     * Render a reference label based on an eel expression or return the expression if it is not valid eel.
     * @throws \Neos\Eel\Exception
     */
    public function getLabel(Reference $reference): string
    {
        $expression = $this->getExpression();

        if (Utility::parseEelExpression($expression) === null) {
            return $expression;
        }

        return (string)Utility::evaluateEelExpression(
            $expression,
            $this->eelEvaluator,
            ['reference' => $reference],
            $this->defaultContextConfiguration ?? []
        );
    }
}
