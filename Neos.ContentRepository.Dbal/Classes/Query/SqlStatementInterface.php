<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Dbal\Query;

/**
 * @internal
 */
interface SqlStatementInterface
{
    public function getParameters(): Parameters;

    public function toSql(): string;
}
