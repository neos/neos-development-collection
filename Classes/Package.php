<?php
namespace PackageFactory\AtomicFusion\AFX;

use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class Package extends BasePackage
{

    /**
     * Regex Pattern to detect the afx code in the fusion that will be parsed
     */
    const SCAN_PATTERN_AFX = "/([ \\t]*)([a-zA-Z0-9\\.]+)[ \\t]*=[ \\t]*afx`(.*?)`/us";
}
