<?php
namespace PackageFactory\AtomicFusion\AFX\Aspects;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use PackageFactory\AtomicFusion\AFX\Service\AfxService;

/**
 * Class FusionParserAspect
 *
 * @package PackageFactory\AtomicFusion\AFX\Aspects
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class FusionParserAspect
{

    /**
     * Regex Pattern to detect the afx code in the fusion that will be parsed
     */
    const SCAN_PATTERN_AFX = "/([ \\t]*)([a-zA-Z0-9\\.]+)[ \\t]*=[ \\t]*afx`(.*?)`/us";

    /**
     * @Flow\Around("method(Neos\Fusion\Core\Parser->parse())")
     * @param JoinPointInterface $joinPoint The current join point
     * @return mixed
     */
    public function expandAfxToFusion(JoinPointInterface $joinPoint)
    {
        $fusionCode = $joinPoint->getMethodArgument('sourceCode');

        if (preg_match(self::SCAN_PATTERN_AFX, $fusionCode)) {
            $fusionCodeProcessed = preg_replace_callback(
                self::SCAN_PATTERN_AFX,
                function($matches) {
                    $indentation = $matches[1];
                    $property = $matches[2];
                    $afx = $matches[3];
                    $fusion = AfxService::convertAfxToFusion($afx, $indentation);
                    return $indentation . $property . ' = ' . $fusion;
                },
                $fusionCode
            );
            $joinPoint->setMethodArgument('sourceCode', $fusionCodeProcessed);
        }

        return $joinPoint->getAdviceChain()->proceed($joinPoint);;
    }

}