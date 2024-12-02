<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure;

use Neos\ContentRepository\Core\Projection\ProjectionInterface;

/**
 * @phpstan-require-implements ProjectionInterface
 * @api to simplify creating a custom dbal projection
 */
trait ProjectionTransactionTrait
{
    /**
     * DBAL default implementation for {@see ProjectionInterface::transactional()}
     * @param \Closure(): void $closure
     */
    public function transactional(\Closure $closure): void
    {
        if ($this->dbal->isTransactionActive() === false) {
            $this->dbal->transactional($closure);
            return;
        }
        // technically we could leverage nested transactions from dbal, which effectively does the same.
        // but that requires us to enable this globally first via setNestTransactionsWithSavepoints also making this explicit is more transparent:
        $this->dbal->createSavepoint('PROJECTION');
        try {
            $closure();
        } catch (\Throwable $e) {
            // roll back the partially applied event on the projection
            $this->dbal->rollbackSavepoint('PROJECTION');
            throw $e;
        }
        $this->dbal->releaseSavepoint('PROJECTION');
    }
}
