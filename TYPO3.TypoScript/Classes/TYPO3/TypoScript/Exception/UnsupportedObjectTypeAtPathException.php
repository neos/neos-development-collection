<?php
namespace Neos\Fusion\Exception;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Fusion\Exception;

/**
 * This exception is thrown if a TypoScript path needs to contain exactly a specific
 * object type; f.e. a "Case" TypoScript object expects all their children being
 * TypoScript objects and does not support Eel Expressions or simple objects.
 */
class UnsupportedObjectTypeAtPathException extends Exception
{
}
