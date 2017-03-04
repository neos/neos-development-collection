<?php
namespace PackageFactory\AtomicFusion\AFX\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Utility\Arrays;
use PackageFactory\Afx\Parser as AfxParser;
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
        $parser = new AfxParser($afxCode);
        $ast = $parser->parse();
        $fusion = self::astNodeToFusion($ast, $indentation);
        return $fusion;
    }

    /**
     * @param array $astNode
     * @param string $indentation
     * @return string
     */
    protected static function astToFusion ($ast, $indentation = '')
    {
        switch ($ast['type']){
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
            default :
                throw new Exception(sprintf('ast type %s is unkonwn', $ast['type'] ));
        }
    }

    /**
     * @param array $astBoolean
     * @param string $indentation
     * @return string
     */
    protected static function astBooleanToFusion ($astBoolean, $indentation = '')
    {
        return 'true';
    }

    /**
     * @param array $astExpression
     * @param string $indentation
     * @return string
     */
    protected static function astExpressionToFusion ($astExpression, $indentation = '')
    {
        return '${' . $astExpression . '}';
    }

    /**
     * @param array $astText
     * @param string $indentation
     * @return string
     */
    protected static function astStringToFusion ($astText, $indentation = '')
    {
        return '\'' . $astText . '\'';
    }

    /**
     * @param array $astText
     * @param string $indentation
     * @return string
     */
    protected static function astTextToFusion ($astText, $indentation = '')
    {
        if (trim($astText) === '') {
            return null;
        }
        return '\'' . $astText . '\'';
    }

    /**
     * @param array $astNode
     * @param string $indentation
     * @return string
     */
    protected static function astNodeToFusion ($astNode, $indentation = '')
    {
        if (!$astNode['identifier']) {
            \Neos\Flow\var_dump($astNode);
        }
        $tagName = $astNode['identifier'];

        $attributePrefix = NULL;
        $attributePrefixExceptions = NULL;

        // Tag
        if (strpos($tagName, ':') !== false) {
            // Named fusion-object
            $fusion = $tagName . ' {' . PHP_EOL;
        } else {
            // Neos.Fusion:Tag
            $fusion = 'Neos.Fusion:Tag {' . PHP_EOL;
            $fusion .= $indentation . self::INDENTATION .'tagName = \'' .  $tagName . '\'' . PHP_EOL;
            $attributePrefix = 'attributes.';
            $attributePrefixExceptions = ['content', 'selfClosingTag', 'omitClosingTag'];
        }

        // Attributes
        if ($astNode['props'] && count($astNode['props']) > 0) {
            foreach ($astNode['props'] as $propName => $prop) {
                if ($propName == '@key' || $propName == '@children') {
                    continue;
                } else {
                    if ($attributePrefix && $attributePrefixExceptions && !in_array($propName, $attributePrefixExceptions)) {
                        $fusionName = $attributePrefix . $propName;
                    } else {
                        $fusionName = $propName;
                    }
                    $propFusion =  self::astToFusion($prop, $indentation . self::INDENTATION );
                    if ($propFusion !== NULL) {
                        $fusion .= $indentation . self::INDENTATION . $fusionName . ' = ' . $propFusion . PHP_EOL;
                    }
                }
            }
        }

        // Children
        if ($astNode['children'] && count($astNode['children']) > 0) {
            $childrenProp = Arrays::getValueByPath($astNode, 'props.@children');
            if ($childrenProp) {
                if ($childrenProp['type'] == 'string') {
                    $childrenPropertyName = $prop['payload'];
                } else {
                    throw new Exception(sprintf('@children only supports string payloads %s found', $childrenProp['type']));
                }
            } else {
                $childrenPropertyName = 'content';
            }
            $childFusion = self::astNodeListToFusion($astNode['children'], $indentation . self::INDENTATION);
            if ($childFusion !== NULL) {
                $fusion .= $indentation . self::INDENTATION . $childrenPropertyName . ' = ' . $childFusion . PHP_EOL;
            }
        }

        $fusion .= $indentation . '}';

        return $fusion;
    }


    /**
     * @param array $astNodeList
     * @param string $indentation
     * @return string
     */
    protected static function astNodeListToFusion ($astNodeList, $indentation = '')
    {
        $fusion = 'Neos.Fusion:Array {' . PHP_EOL;
        $index = 1;
        foreach ($astNodeList as $astNode) {
            $fusionName = $index;
            if ($keyProperty = Arrays::getValueByPath($astNode, 'payload.props.@key')) {
                if ($keyProperty['type'] == 'string') {
                    $fusionName = $keyProperty['payload'];
                } else {
                    throw new Exception(sprintf('@key only supports string payloads %s was given', $astNode['props']['@key']['type']));
                }
            }
            $nodeFusion = self::astToFusion($astNode, $indentation .  self::INDENTATION );
            if ($nodeFusion !== NULL) {
                $fusion .= $indentation . self::INDENTATION . $fusionName . ' = ' . $nodeFusion . PHP_EOL;
                $index++;
            }
        }
        $fusion .= $indentation . '}';
        return $fusion;
    }
}
