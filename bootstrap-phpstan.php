<?php

/**
 * This bootstrap helps phpstan to detect all available constants
 */

$_SERVER['FLOW_ROOTPATH'] = dirname(__DIR__, 2);

new \Neos\Flow\Core\Bootstrap('Testing');
