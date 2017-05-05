<?php
namespace PackageFactory\AtomicFusion\AFX\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Utility\Arrays;
use PackageFactory\Afx\Parser as AfxParser;
use PackageFactory\Afx\Exception as AfxException;
use Neos\Fusion\Exception as FusionException;

use PackageFactory\AtomicFusion\AFX\Exception\Exception;

/**
 * Class AfxService
 * @package PackageFactory\AtomicFusion\AFX\Service
 * @Flow\Scope("singleton")
 */
class AfxService
{

    const INDENTATION = '    ';

    /**
     * @var string $afxCode the AFX code that is converted
     * @var string $indentation Indentation to start with
     * @return string
     */
    public static function convertAfxToFusion($afxCode, $indentation = '')
    {
        try {
            $parser = new AfxParser(self::preprocess($afxCode));
            $ast = $parser->parse();
            $fusion = self::astNodeToFusion($ast, $indentation);
            return $fusion;
        } catch (AfxException $afxException) {
            throw new FusionException(sprintf('Error during AFX-parsing: %s', $afxException->getMessage()));
        }


    }

    /**
     * Optimize the given afx before processing
     *
     * This step removes all newlines and spaces that are connected to an newline
     * spaces between tags on a single level are preserved.
     *
     * @param string $afxCode
     * @return string
     */
    protected static function preprocess($afxCode)
    {
        return trim($afxCode);
    }

    /**
     * @param array $astNode
     * @param string $indentation
     * @return string
     */
    protected static function astToFusion($ast, $indentation = '')
    {
        switch ($ast['type']) {
            case 'expression':
                return self::astExpressionToFusion($ast['payload'], $indentation);
                break;
            case 'string':
                return self::astStringToFusion($ast['payload'], $indentation);
                break;
            case 'text':
                return self::astTextToFusion($ast['payload'], $indentation);
                break;
            case 'boolean':
                return self::astBooleanToFusion($ast['payload'], $indentation);
                break;
            case 'node':
                return self::astNodeToFusion($ast['payload'], $indentation);
                break;
            default:
                throw new Exception(sprintf('ast type %s is unkonwn', $ast['type']));
        }
    }

    /**
     * @param array $payload
     * @param string $indentation
     * @return string
     */
    protected static function astBooleanToFusion($payload, $indentation = '')
    {
        return 'true';
    }

    /**
     * @param array $payload
     * @param string $indentation
     * @return string
     */
    protected static function astExpressionToFusion($payload, $indentation = '')
    {
        return '${' . $payload . '}';
    }

    /**
     * @param array $payload
     * @param string $indentation
     * @return string
     */
    protected static function astStringToFusion($payload, $indentation = '')
    {
        return '\'' . str_replace('\'', '\\\'', $payload) . '\'';
    }

    /**
     * @param array $payload
     * @param string $indentation
     * @return string
     */
    protected static function astTextToFusion($payload, $indentation = '')
    {
        return '\'' . str_replace('\'', '\\\'', $payload) . '\'';
    }

    /**
     * @param array $payload
     * @param string $indentation
     * @return string
     */
    protected static function astNodeToFusion($payload, $indentation = '')
    {
        $tagName = $payload['identifier'];

        $attributePrefix = null;
        $attributePrefixExceptions = [];

        // Tag
        if (strpos($tagName, ':') !== false) {
            // Named fusion-object
            $fusion = $tagName . ' {' . PHP_EOL;
        } else {
            // Neos.Fusion:Tag
            $fusion = 'Neos.Fusion:Tag {' . PHP_EOL;
            $fusion .= $indentation . self::INDENTATION .'tagName = \'' .  $tagName . '\'' . PHP_EOL;

            $attributePrefix = 'attributes.';
            $attributePrefixExceptions = ['content'];

            $hasContent = false;
            if ($payload['props'] && count($payload['props']) > 0) {
                foreach ($payload['props'] as $propName => $prop) {
                    if ($propName == 'content') {
                        $hasContent = true;
                    }
                }
            }

            if ($payload['selfClosing'] === true && $hasContent === false) {
                $fusion .= $indentation . self::INDENTATION .'selfClosingTag = true' . PHP_EOL;
            }
        }

        // Attributes
        if ($payload['props'] && count($payload['props']) > 0) {
            foreach ($payload['props'] as $propName => $prop) {
                if ($propName == '@key' || $propName == '@children') {
                    continue;
                } else {
                    if ($propName{0} === '@') {
                        $fusionName = $propName;
                    } elseif ($attributePrefix && !in_array($propName, $attributePrefixExceptions)) {
                        $fusionName = $attributePrefix . $propName;
                    } else {
                        $fusionName = $propName;
                    }
                    $propFusion =  self::astToFusion($prop, $indentation . self::INDENTATION);
                    if ($propFusion !== null) {
                        $fusion .= $indentation . self::INDENTATION . $fusionName . ' = ' . $propFusion . PHP_EOL;
                    }
                }
            }
        }

        // Children
        if ($payload['children'] && count($payload['children']) > 0) {
            $childrenProp = Arrays::getValueByPath($payload, 'props.@children');
            if ($childrenProp) {
                if ($childrenProp['type'] == 'string') {
                    $childrenPropertyName = $prop['payload'];
                } else {
                    throw new Exception(
                        sprintf('@children only supports string payloads %s found', $childrenProp['type'])
                    );
                }
            } else {
                $childrenPropertyName = 'content';
            }
            $childFusion = self::astNodeListToFusion($payload['children'], $indentation . self::INDENTATION);
            if ($childFusion !== null) {
                $fusion .= $indentation . self::INDENTATION . $childrenPropertyName . ' = ' . $childFusion . PHP_EOL;
            }
        }

        $fusion .= $indentation . '}';

        return $fusion;
    }

    /**
     * @param array $payload
     * @param string $indentation
     * @return string
     */
    protected static function astNodeListToFusion($payload, $indentation = '')
    {
        $fusion = 'Neos.Fusion:Array {' . PHP_EOL;
        $index = 1;
        foreach ($payload as $astNode) {

            // ignore blank text if it is connected to a newline
            if ($astNode['type'] == 'text') {
                $astNode['payload']  = preg_replace('/[\\s]*\\n[\\s]*/u', '', $astNode['payload'] );
                if ($astNode['payload'] == '') {
                    continue;
                };
            }

            // detect key
            $fusionName = $index;
            if ($keyProperty = Arrays::getValueByPath($astNode, 'payload.props.@key')) {
                if ($keyProperty['type'] == 'string') {
                    $fusionName = $keyProperty['payload'];
                } else {
                    throw new Exception(
                        sprintf('@key only supports string payloads %s was given', $astNode['props']['@key']['type'])
                    );
                }
            }

            // convert node
            $nodeFusion = self::astToFusion($astNode, $indentation .  self::INDENTATION);
            if ($nodeFusion !== null) {
                $fusion .= $indentation . self::INDENTATION . $fusionName . ' = ' . $nodeFusion . PHP_EOL;
                $index++;
            }
        }
        $fusion .= $indentation . '}';
        return $fusion;
    }
}
