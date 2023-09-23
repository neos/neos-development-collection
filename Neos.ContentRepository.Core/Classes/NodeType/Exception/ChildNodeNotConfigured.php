<?php

namespace Neos\ContentRepository\Core\NodeType\Exception;

/**
 * @api Might be encountered when childNode information is requested for a child node which was never configured.
 */
class ChildNodeNotConfigured extends \DomainException
{
}
