<?php

declare(strict_types=1);

namespace Neos\Fusion\Tests\Unit\Migrations;

use Neos\Fusion\Migrations\EelExpression\EelExpressionTransformer;
use PHPUnit\Framework\TestCase;

class EelExpressionTransformerTest extends TestCase
{
    public function examples(): iterable
    {
        yield 'L ' . __LINE__ => [
            fn (string $eelExpression) => str_replace('someVariable', 'myNewVariable', $eelExpression),
            <<<'Fusion'
            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                unchanged = ${unchangedVariable}
                value = ${someVariable}
                composed = ${someVariable + unchangedVariable}
            }

            root = ${someVariable}
            Fusion,
            <<<'Fusion'
            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                unchanged = ${unchangedVariable}
                value = ${myNewVariable}
                composed = ${myNewVariable + unchangedVariable}
            }

            root = ${myNewVariable}
            Fusion,
        ];

        yield 'L ' . __LINE__ => [
            fn (string $eelExpression) => str_replace('someVariable', 'myNewVariable', $eelExpression),
            <<<'Fusion'
              prototype(Neos.Fusion.Test:Value)       < prototype(Neos.Fusion:Value)   {
                /*
                      code style is preserved
                     */
                 value = ${someVariable}
                                            }

            # end
            Fusion,
            <<<'Fusion'
              prototype(Neos.Fusion.Test:Value)       < prototype(Neos.Fusion:Value)   {
                /*
                      code style is preserved
                     */
                 value = ${myNewVariable}
                                            }

            # end
            Fusion,
        ];

        yield 'L ' . __LINE__ => [
            fn (string $eelExpression) => str_replace('someVariable', 'myNewVariable', $eelExpression),
            <<<'Fusion'
            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                // comments are preserved fully
                # value = ${someVariable}
                /**
                    value = ${someVariable}
                */
            }
            Fusion,
            <<<'Fusion'
            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                // comments are preserved fully
                # value = ${someVariable}
                /**
                    value = ${someVariable}
                */
            }
            Fusion,
        ];

        yield 'L ' . __LINE__ => [
            fn (string $eelExpression) => str_replace('someVariable', 'myNewVariable', $eelExpression),
            <<<'Fusion'
            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                # comments are preserved
                @context.composed = ${someVariable + unchangedVariable}
                multiline = ${
                    someVariable
                    + 'appendix'
                }
                deep {
                    deep {
                        ocean = ${someVariable}
                    }
                }
                spaces = ${    someVariable    }
            }
            Fusion,
            <<<'Fusion'
            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                # comments are preserved
                @context.composed = ${myNewVariable + unchangedVariable}
                multiline = ${
                    myNewVariable
                    + 'appendix'
                }
                deep {
                    deep {
                        ocean = ${myNewVariable}
                    }
                }
                spaces = ${    myNewVariable    }
            }
            Fusion,
        ];

        yield 'L ' . __LINE__ => [
            fn (string $eelExpression) => str_replace('someVariable', 'myNewVariable', $eelExpression),
            <<<'Fusion'
            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                value = afx`
                    {someVariable}
                    <p>
                        {someVariable}
                    </p>
                `
            }
            Fusion,
            <<<'Fusion'
            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                value = afx`
                    {myNewVariable}
                    <p>
                        {myNewVariable}
                    </p>
                `
            }
            Fusion,
        ];

        yield 'L ' . __LINE__ => [
            fn (string $eelExpression) => str_replace('someVariable', 'myNewVariable', $eelExpression),
            <<<'Fusion'
            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                value = afx`
                    {someVariable}
                    <p>
                        {someVariable}
                    </p>
                `
            }
            Fusion,
            <<<'Fusion'
            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                value = afx`
                    {myNewVariable}
                    <p>
                        {myNewVariable}
                    </p>
                `
            }
            Fusion,
        ];

        yield 'L ' . __LINE__ => [
            fn (string $eelExpression) => str_replace('someVariable', 'myNewVariable', $eelExpression),
            <<<'Fusion'
            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                value = afx`
                    <p {...{name: someVariable}} data-value={someVariable}></p>
                `
            }
            Fusion,
            <<<'Fusion'
            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                value = afx`
                    <p {...{name: myNewVariable}} data-value={myNewVariable}></p>
                `
            }
            Fusion,
        ];

        // bugfix preserve output of single EEL expression in afx
        yield 'L ' . __LINE__ => [
            fn (string $eelExpression) => $eelExpression,
            <<<'Fusion'
            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                value = afx`{props.value}`
            }
            Fusion,
            <<<'Fusion'
            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                value = afx`{props.value}`
            }
            Fusion,
        ];

        yield 'L ' . __LINE__ => [
            fn (string $eelExpression) => str_replace('someVariable', 'myNewVariable', $eelExpression),
            <<<'Fusion'
            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                value = afx`
                    <span>{someVariable}</span>
                    {someVariable}`
            }
            Fusion,
            <<<'Fusion'
            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                value = afx`
                    <span>{myNewVariable}</span>
                    {myNewVariable}`
            }
            Fusion,
        ];

        yield 'L ' . __LINE__ => [
            fn (string $eelExpression) => str_replace('someVariable', 'myNewVariable', $eelExpression),
            <<<'Fusion'
            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                value = afx`
                    {someVariable}
                    <p {...{name: someVariable}} data-value={someVariable}>
                        {someVariable}
                        {unchangedVariable}
                    </p>
                `
            }
            Fusion,
            <<<'Fusion'
            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                value = afx`
                    {myNewVariable}
                    <p {...{name: myNewVariable}} data-value={myNewVariable}>
                        {myNewVariable}
                        {unchangedVariable}
                    </p>
                `
            }
            Fusion,
        ];
    }

    /**
     * @dataProvider examples
     */
    public function testReplacements(\Closure $eelModifier, string $input, string $expectedOutput): void
    {
        self::assertEquals(
            $expectedOutput,
            EelExpressionTransformer::forContent($input, '', fn () => '')->process($eelModifier)->getProcessedContent()
        );
    }
}
