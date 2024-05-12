<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Exception;

use Neos\ContentRepository\Core\ContentRepository;

/**
 * @api because exception might be thrown on invalid interactions with a foreign ContentRepository instance
 */
class ForeignContentRepositoryUsed extends \DomainException
{
}
