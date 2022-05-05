<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Infrastructure;

use Doctrine\DBAL\Connection;

/**
 * This is what the ES CR uses to access a database. It needs to be filled from the external world
 */
interface DbalClientInterface
{
    public function getConnection(): Connection;
}
