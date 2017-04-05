<?php
namespace PackageFactory\AtomicFusion\AFX\Aspects;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use PackageFactory\AtomicFusion\AFX\Package as AfxPackage;
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
     * @Flow\Around("method(Neos\Fusion\Core\Parser->parse())")
     * @param JoinPointInterface $joinPoint The current join point
     * @return mixed
     */
    public function expandAfxToFusion(JoinPointInterface $joinPoint)
    {
        $fusionCode = $joinPoint->getMethodArgument('sourceCode');

        if (preg_match(AfxPackage::SCAN_PATTERN_AFX, $fusionCode)) {
            $fusionCodeProcessed = preg_replace_callback(
                AfxPackage::SCAN_PATTERN_AFX,
                function ($matches) {
                    $indentation = $matches[1];
                    $property = $matches[2];
                    $afx = $matches[3];
                    $fusion = $indentation . $property . ' = ' . AfxService::convertAfxToFusion($afx, $indentation);
                    return $fusion;
                },
                $fusionCode
            );
            $joinPoint->setMethodArgument('sourceCode', $fusionCodeProcessed);
        }

        return $joinPoint->getAdviceChain()->proceed($joinPoint);
        ;
    }
}
