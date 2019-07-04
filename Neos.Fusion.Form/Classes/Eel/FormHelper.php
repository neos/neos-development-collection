<?php
declare(strict_types=1);

namespace Neos\Fusion\Form\Eel;

/*
 * This file is part of the Neos.Fusion.Form package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService;

class FormHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var MvcPropertyMappingConfigurationService
     */
    protected $mvcPropertyMappingConfigurationService;

    /**
     * @Flow\Inject
     * @var HashService
     */
    protected $hashService;

    /**
     * Calculate the trusted properties token for the given form content
     *
     * @param array $arguments
     * @param string|null $fieldNamePrefix
     */
    public function argumentsWithHmac(array $arguments = [], string $excludeNamespace = '')
    {
        if ($excludeNamespace !== null && isset($arguments[$excludeNamespace])) {
            unset($arguments[$excludeNamespace]);
        }
        return $this->hashService->appendHmac(base64_encode(serialize($arguments)));
    }

    /**
     * Calculate the trusted properties token for the given form content
     *
     * @param string $content
     * @param string|null $fieldNamePrefix
     */
    public function trustedPropertiesToken(string $content, string $fieldNamePrefix = '')
    {
        $domDocument = new \DOMDocument('1.0', 'UTF-8');
        // ignore parsing errors
        $useInternalErrorsBackup = libxml_use_internal_errors(true);
        $domDocument->loadHTML($content);
        $xpath = new \DOMXPath($domDocument);
        if ($useInternalErrorsBackup !== true) {
            libxml_use_internal_errors($useInternalErrorsBackup);
        }

        $elements = $xpath->query("//*[@name]");
        $formFieldNames = [];
        foreach($elements as $element) {
            $formFieldNames[] = (string)$element->getAttribute('name');
        }
        return $this->mvcPropertyMappingConfigurationService->generateTrustedPropertiesToken($formFieldNames, $fieldNamePrefix);
    }

    /**
     * Returns CSRF token which is required for "unsafe" requests (e.g. POST, PUT, DELETE, ...)
     *
     * @return string
     */
    public function csrfToken(): string
    {
        return $this->securityContext->getCsrfProtectionToken();
    }

    /**
     * Prepend the gigen fieldNamePrefix to the fieldName the
     *
     * @param string $name
     * @param string|null $prefix
     * @return string
     */
    public function prefixFieldName(string $fieldName, string $fieldNamePrefix = null)
    {
        if (!$fieldNamePrefix) {
            return $fieldName;
        } else {
            $fieldNameSegments = explode('[', $fieldName, 2);
            $fieldName = $fieldNamePrefix . '[' . $fieldNameSegments[0] . ']';
            if (count($fieldNameSegments) > 1) {
                $fieldName .= '[' . $fieldNameSegments[1];
            }
            return $fieldName;
        }
    }

    /**
     * @param string $methodName
     * @return bool
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }

}
