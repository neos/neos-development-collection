<?php

namespace Neos\ContentRepository\Core\SharedModel\Exception;

/**
 * @api Might be encountered when childNode information is requested for a child node which was never configured.
 */
class TetheredNodeNotConfigured extends \DomainException
{
}
