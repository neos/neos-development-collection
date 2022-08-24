<?php

use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;

class SomeClass
{
    protected ContextFactoryInterface $contextFactory;
    public function run(string $workspace): Neos\ContentRepository\Domain\Service\Context
    {
        return $this->contextFactory->create([
          'workspace' => $workspace,
          'dimensions' => [
            'language' => ['de_DE']
          ]
        ]);
    }

  public function run2(): Neos\Neos\Domain\Service\ContentContext {
  }
}

?>
-----
<?php

use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;

class SomeClass
{
    protected ContextFactoryInterface $contextFactory;
    public function run(string $workspace): \Neos\ContentRepository\Rector\Legacy\LegacyContextStub
    {
        return new \Neos\ContentRepository\Rector\Legacy\LegacyContextStub([
          'workspace' => $workspace,
          'dimensions' => [
            'language' => ['de_DE']
          ]
        ]);
    }

  public function run2(): \Neos\ContentRepository\Rector\Legacy\LegacyContextStub {
  }
}

?>
