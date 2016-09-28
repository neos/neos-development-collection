<?php
namespace TYPO3\Neos\Validation\Validator;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Validation\Validator\AbstractValidator;

/**
 * Validator for http://tools.ietf.org/html/rfc1123 compatible host names
 */
class HostnameValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = array(
        'ignoredHostnames' => array('', 'Hostnames that are not to be validated', 'string'),
    );

    /**
     * Validates if the hostname is valid.
     *
     * @param mixed $hostname The hostname that should be validated
     * @return void
     */
    protected function isValid($hostname)
    {
        $pattern = '/(?=^.{4,253}$)(^((?!-)[a-zA-Z0-9-]{1,63}(?<!-)\.)*(?!-)[a-zA-Z]{2,63}(?<!-)$)/';

        if ($this->options['ignoredHostnames']) {
            $ignoredHostnames = explode(',', $this->options['ignoredHostnames']);
            if (in_array($hostname, $ignoredHostnames)) {
                return;
            }
        }

        if (!preg_match($pattern, $hostname)) {
            $this->addError('The hostname "%1$s" was not valid.', 1415392993, array($hostname));
        }
    }
}
