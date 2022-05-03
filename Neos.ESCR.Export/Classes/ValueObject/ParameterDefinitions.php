<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\ValueObject;

use Neos\Flow\Annotations as Flow;
use Webmozart\Assert\Assert;

/**
 * @Flow\Proxy(false)
 * @implements \IteratorAggregate<ParameterDefinition>
 */
final class ParameterDefinitions implements \IteratorAggregate
{

    /**
     * @param array<ParameterDefinition> $definitions
     */
    private function __construct(
        private readonly array $definitions
    ) {
        Assert::allIsInstanceOf($definitions, ParameterDefinition::class);
    }

    /**
     * @param array<ParameterDefinition|array<string, string|int|bool|null>> $definitions
     * @return static
     */
    public static function fromArray(array $definitions): self
    {
        $result = [];
        foreach ($definitions as $name => $definition) {
            if (\is_array($definition)) {
                $definition = ParameterDefinition::fromNameAndDefaultValue($name, $definition['defaultValue'] ?? null);
            }
            if (!$definition instanceof ParameterDefinition) {
                throw new \InvalidArgumentException(sprintf('Expected parameter of type %s, got: %s for parameter "%s"', ParameterDefinition::class, get_debug_type($definition), $name), 1646402124);
            }
            $result[$name] = $definition;
        }
        return new self($result);
    }

    /**
     * @return \Iterator<ParameterDefinition>
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->definitions);
    }

    public function has(string $parameterName): bool
    {
        return \array_key_exists($parameterName, $this->definitions);
    }
}
