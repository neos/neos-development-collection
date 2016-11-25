<?php
namespace Neos\Neos\Validation\Validator;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Validation\Validator\RegularExpressionValidator;
use Neos\ContentRepository\Domain\Model\NodeInterface;

/**
 * Validator for node names
 */
class NodeNameValidator extends RegularExpressionValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = array(
        'regularExpression' => array(NodeInterface::MATCH_PATTERN_NAME, 'The regular expression to use for validation, used as given', 'string')
    );
}
