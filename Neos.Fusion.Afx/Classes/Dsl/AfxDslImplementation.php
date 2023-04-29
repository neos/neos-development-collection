<?php
namespace Neos\Fusion\Afx\Dsl;

/*
 * This file is part of the Neos.Fusion.Afx package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Fusion;
use Neos\Fusion\Core\DslInterface;
use Neos\Fusion\Afx\Service\AfxService;
use Neos\Fusion\Afx\Exception\AfxException;
use Neos\Fusion\Afx\Parser\AfxParserException;

/**
 * Class Fusion AFX Dsl
 *
 * @Flow\Scope("singleton")
 */
class AfxDslImplementation implements DslInterface
{

    /**
     * Transpile the given dsl-code to fusion-code
     *
     * @param string $code
     * @return string
     * @throws Fusion\Exception
     */
    public function transpile($code)
    {
        try {
            return AfxService::convertAfxToFusion($code);
        } catch (AfxException $afxException) {
            throw new Fusion\Exception(sprintf('Error during AFX-transpilation: %s', $afxException->getMessage()));
        } catch (AfxParserException $afxException) {
            throw new Fusion\Exception(sprintf('Error during AFX-parsing: %s', $afxException->getMessage()));
        }
    }
}
