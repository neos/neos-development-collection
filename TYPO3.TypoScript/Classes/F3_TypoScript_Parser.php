<?php
declare(ENCODING = 'utf-8');
namespace F3\TypoScript;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package TypoScript
 * @version $Id$
 */

/**
 * The TypoScript Parser
 *
 * @package TypoScript
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Parser implements \F3\TypoScript\ParserInterface {

	const SCAN_PATTERN_COMMENT = '/(?:#.*)|(?:\/\/.*)|(?:\/\*.*)/';
	const SCAN_PATTERN_OPENINGCONFINEMENT = '/[a-zA-Z][a-zA-Z0-9]*(?:\.(?:[a-zA-Z][a-zA-Z0-9]*))*\s*\{/';
	const SCAN_PATTERN_CLOSINGCONFINEMENT = '/\s*\}/';
	const SCAN_PATTERN_DECLARATION = '/(include|namespace)\s*:/';
	const SCAN_PATTERN_OBJECTDEFINITION = '/[a-zA-Z0-9.\\\\$]+\s*[=|<|>|\.processors\.]/';
	const SCAN_PATTERN_OBJECTPATH = '/\.?[a-zA-Z][a-zA-Z0-9]*(?:\.(?:[a-zA-Z][a-zA-Z0-9]*))*(?:\.(?:(?:\$[a-zA-Z][a-zA-Z0-9])|(?:[0-9]+)))?/';

	const SPLIT_PATTERN_COMMENTTYPE = '/.*(#|\/\/|\/\*|\*\/).*/';
	const SPLIT_PATTERN_CONFINEMENTOBJECTPATH = '/([a-zA-Z][a-zA-Z0-9]*(?:\.(?:[a-zA-Z][a-zA-Z0-9]*))*)\s*\{/';
	const SPLIT_PATTERN_DECLARATION = '/([a-zA-Z]+[a-zA-Z0-9]*)\s*:(.*)/';
	const SPLIT_PATTERN_NAMESPACEDECLARATION = '/\s*([a-zA-Z]+[a-zA-Z0-9]*)\s*=\s*(F3\\\\(?:\w+|\\\\)+)/';
	const SPLIT_PATTERN_OBJECTDEFINITION = '/\s*(?P<ObjectPath>[a-zA-Z0-9.\$]+)\s*(?P<Operator>=<|=|<<|<|>)\s*(?P<Value>.+|$)/';
	const SPLIT_PATTERN_VALUENUMBER = '/^\s*-?\d+\s*$/';
	const SPLIT_PATTERN_VALUEFLOATNUMBER = '/^\s*-?\d+(\.\d+)?\s*$/';
	const SPLIT_PATTERN_VALUELITERAL = '/"((?:\\\\.|[^\\\\"])*)"|\'((?:\\\\.|[^\\\\\'])*)\'/';
	const SPLIT_PATTERN_VALUEVARIABLE = '/(\$[a-zA-Z][a-zA-Z0-9]*)/';
	const SPLIT_PATTERN_VALUEVARIABLES = '/\$[a-zA-Z][a-zA-Z0-9]*(?=[^a-zA-Z0-9]|$)/';
	const SPLIT_PATTERN_VALUEOBJECTTYPE = '/^\s*(?:(?:([a-zA-Z]+[a-zA-Z0-9*]*)\\\\)?([a-zA-Z][a-zA-Z0-9]*)$)|(F3\\\\(?:\w+|\\\\)+)/';
	const SPLIT_PATTERN_INDEXANDMETHODCALL = '/(?P<Index>\d+)\.(?P<ObjectAndMethodName>\w+)\s*\((?P<Arguments>.*?)\)\s*$/';
	const SPLIT_PATTERN_COMPONENTANDMETHODNAME = '/(?:(<?P<ObjectName>(\\\\F3\\\\(?:\w+|\\\\)+))->)?(?P<MethodName>\w+)/';
	const SPLIT_PATTERN_METHODARGUMENTS = '/("(?:\\\\.|[^\\\\"])*"|\'(?:\\\\.|[^\\\\\'])*\'|\$[a-zA-Z0-9]+|-?[0-9]+(\.\d+)?)/';

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var array The TypoScript object tree, created by this parser.
	 */
	protected $objectTree = array();

	/**
	 * @var array Contains the TS object variables used during parse time, indexed by the object path
	 */
	protected $objectVariables = array();

	/**
	 * @var integer The line number which is currently processed
	 */
	protected $currentLineNumber = 1;

	/**
	 * @var array An array of strings of the source code which has
	 */
	protected $currentSourceCodeLines = array();

	/**
	 * @var array The current object path context as defined by confinements.
	 */
	protected $currentObjectPathStack = array();

	/**
	 * @var boolean Determines if a block comment is currently active or not.
	 */
	protected $currentBlockCommentState = FALSE;

	/**
	 * @var array Namespace identifiers and their object name prefix
	 */
	protected $namespaces = array(
		'default' => ''
	);

	/**
	 * Constructs the parser
	 *
	 * @param \F3\FLOW3\ObjectManagerInterface $objectManager A reference to the object manager
	 * @param \F3\FLOW3\ObjectFactoryInterface $objectFactory A reference to the object factory
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Object\ManagerInterface $objectManager, \F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectManager = $objectManager;
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Parses the given TypoScript source code and returns an object tree
	 * as the result.
	 *
	 * @param string $sourceCode The TypoScript source code to parse
	 * @return \F3\TypoScript\ObjectTree A TypoScript object tree, generated from the source code
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parse($sourceCode) {
		if (!is_string($sourceCode)) throw new LogicException('Cannot parse TypoScript - $sourceCode must be of type string!', 1180203775);
		$this->initialize();

		$typoScriptLines = explode(chr(10), $sourceCode);
		foreach ($typoScriptLines as $typoScriptLine) {
			$this->parseTypoScriptLine($typoScriptLine);
			$this->currentLineNumber ++;
		}
		return $this->objectTree;
	}

	/**
	 * Sets the default namespace to the given object name prefix
	 *
	 * @param string $objectNamePrefix The object name to prepend as the default namespace, without trailing "
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setDefaultNamespace($objectNamePrefix) {
		if (!is_string($objectNamePrefix)) throw new LogicException('The object name prefix for the default namespace must be of type string!', 1180600696);
		$this->namespaces['default'] = $objectNamePrefix;
	}

	/**
	 * Initializes the TypoScript parser
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function initialize() {
		$this->currentLineNumber = 1;
		$this->currentObjectPathStack = array();
		$this->currentSourceCodeLines = array();
		$this->currentBlockCommentState = FALSE;
		$this->objectTree = array();
		$this->objectVariables = array();
	}

	/**
	 * Parses one line of TypoScript
	 *
	 * @param string $typoScriptLine One line of TypoScript code
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseTypoScriptLine($typoScriptLine) {
		$typoScriptLine = trim($typoScriptLine);

		if ($this->currentBlockCommentState === TRUE) {
			$this->parseComment($typoScriptLine);
		} else {
			if ($typoScriptLine == '') {
				return;
			} elseif (preg_match(self::SCAN_PATTERN_COMMENT, $typoScriptLine)) {
				$this->parseComment($typoScriptLine);
			} elseif (preg_match(self::SCAN_PATTERN_OPENINGCONFINEMENT, $typoScriptLine)) {
				$this->parseConfinementBlock($typoScriptLine, TRUE);
			} elseif (preg_match(self::SCAN_PATTERN_CLOSINGCONFINEMENT, $typoScriptLine)) {
				$this->parseConfinementBlock($typoScriptLine, FALSE);
			} elseif (preg_match(self::SCAN_PATTERN_DECLARATION, $typoScriptLine)) {
				$this->parseDeclaration($typoScriptLine);
			} elseif (preg_match(self::SCAN_PATTERN_OBJECTDEFINITION, $typoScriptLine)) {
				$this->parseObjectDefinition($typoScriptLine);
			} else {
				throw new \F3\TypoScript\Exception('Syntax error in line ' . $this->currentLineNumber . '. (' . $typoScriptLine . ')', 1180547966);
			}
		}
	}

	/**
	 * Parses a line with comments or a line while parsing is in block comment mode.
	 *
	 * @param string $typoScriptLine One line of TypoScript code
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseComment($typoScriptLine) {
		if (preg_match(self::SPLIT_PATTERN_COMMENTTYPE, $typoScriptLine, $matches, PREG_OFFSET_CAPTURE) === 1) {
			switch ($matches[1][0]) {
				case '/*' :
					$this->currentBlockCommentState = TRUE;
					break;
				case '*/' :
					if ($this->currentBlockCommentState !== TRUE) throw new \F3\TypoScript\Exception('Unexpected closing block comment without matching opening block comment.', 1180615119);
					$this->currentBlockCommentState = FALSE;
					$this->parseTypoScriptLine(\F3\PHP6\Functions::substr($typoScriptLine, ($matches[1][1] + 2)));
					break;
				case '#' :
				case '//' :
				default :
					break;
			}
		} elseif ($this->currentBlockCommentState === FALSE) {
			throw new LogicException('No comment type matched although the comment scan regex matched the TypoScript line (' . $typoScriptLine . ').', 1180614895);
		}
	}

	/**
	 * Parses a line which opens or closes a confinement
	 *
	 * @param string $typoScriptLine One line of TypoScript code
	 * @param boolean $isOpeningConfinement Set to TRUE, if an opening confinement is to be parsed and FALSE if it's a closing confinement.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseConfinementBlock($typoScriptLine, $isOpeningConfinement) {
		if ($isOpeningConfinement) {
			$result = preg_match(self::SPLIT_PATTERN_CONFINEMENTOBJECTPATH, $typoScriptLine, $matches);
			if ($result !== 1) throw new \F3\TypoScript\Exception('Invalid object path in the confinement "' . $typoScriptLine . '".', 1181576407);
			array_push($this->currentObjectPathStack, $this->getCurrentObjectPathPrefix() . $matches[1]);
		} else {
			if (count($this->currentObjectPathStack) < 1) throw new \F3\TypoScript\Exception('Unexpected closing confinement without matching opening confinement. Check the number of your curly braces.', 1181575973);
			array_pop($this->currentObjectPathStack);
		}
	}

	/**
	 * Parses a parser declaration of the form "declarationtype: declaration".
	 *
	 * @param string $typoScriptLine One line of TypoScript code
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseDeclaration($typoScriptLine) {
		$result = preg_match(self::SPLIT_PATTERN_DECLARATION, $typoScriptLine, $matches);
		if ($result !== 1 || count($matches) != 3) throw new \F3\TypoScript\Exception('Invalid declaration "' . $typoScriptLine . '"', 1180544656);

		switch ($matches[1]) {
			case 'namespace' :
				$this->parseNamespaceDeclaration($matches[2]);
				break;
			case 'include' :
				break;
		}
	}

	/**
	 * Parses an object definition.
	 *
	 * @param string $typoScriptLine One line of TypoScript code
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseObjectDefinition($typoScriptLine) {
		$result = preg_match(self::SPLIT_PATTERN_OBJECTDEFINITION, $typoScriptLine, $matches);
		if ($result !== 1) throw new \F3\TypoScript\Exception('Invalid object definition "' . $typoScriptLine . '"', 1180548488);

		$objectPath = $this->getCurrentObjectPathPrefix() . $matches['ObjectPath'];
		switch ($matches['Operator']) {
			case '=' :
				$this->parseValueAssignment($objectPath, $matches['Value']);
				break;
			case '>' :
				$this->parseValueUnAssignment($objectPath);
				break;
			case '<' :
				$this->parseValueCopy($matches['Value'], $objectPath);
				break;
			case '=<' :
				$this->parseValueReference($matches['Value'], $objectPath);
				break;
			case '<<' :
				$this->parseValueProcessing($matches['Value'], $objectPath);
				break;
		}
	}

	/**
	 * Parses a value operation of the type "assignment".
	 *
	 * @param string $objectPath The object path as a string
	 * @param string $value The unparsed value as a string
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseValueAssignment($objectPath, $value) {
		$objectPathArray = $this->getParsedObjectPath($objectPath);
		$processedValue = $this->getProcessedValue($objectPathArray, $value);

		if ($objectPathArray[count($objectPathArray) - 1]{0} == '$') {
			$this->setObjectVariable($objectPathArray, $processedValue);
		} else {
			$this->setValueInObjectTree($objectPathArray, $processedValue);
		}
	}

	/**
	 * Unsets the object, property or variable specified by the object path.
	 *
	 * @param string $objectPath The object path as a string
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseValueUnAssignment($objectPath) {
		$objectPathArray = $this->getParsedObjectPath($objectPath);
		if ($objectPathArray[count($objectPathArray) - 1]{0} == '$') {
			$this->setObjectVariable($objectPathArray, NULL);
		} else {
			$this->setValueInObjectTree($objectPathArray, NULL);
		}
	}

	/**
	 * Copies the object or value specified by sourcObjectPath and assigns
	 * it to targetObjectPath.
	 *
	 * @param string $sourceObjectPath Specifies the location in the object tree from where the object or value will be taken
	 * @param string $targetObjectPath Specifies the location in the object tree where the copy will be stored
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseValueCopy($sourceObjectPath, $targetObjectPath) {
		$sourceObjectPathArray = $this->getParsedObjectPath($sourceObjectPath);
		$targetObjectPathArray = $this->getParsedObjectPath($targetObjectPath);

		$originalValue = $this->getValueFromObjectTree($sourceObjectPathArray);
		$value = is_object($originalValue) ? clone $originalValue : $originalValue;

		if ($targetObjectPathArray[count($targetObjectPathArray) - 1]{0} == '$') {
			$this->setObjectVariable($targetObjectPathArray, $value);
		} else {
			$this->setValueInObjectTree($targetObjectPathArray, $value);
		}
	}

	/**
	 * Assigns a reference of an object or value specified by sourcObjectPath to
	 * the targetObjectPath.
	 *
	 * @param string $sourceObjectPath Specifies the location in the object tree from where the object or value will be taken
	 * @param string $targetObjectPath Specifies the location in the object tree where the reference will be stored
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseValueReference($sourceObjectPath, $targetObjectPath) {
		$sourceObjectPathArray = $this->getParsedObjectPath($sourceObjectPath);
		$targetObjectPathArray = $this->getParsedObjectPath($targetObjectPath);

		$value = $this->getValueFromObjectTree($sourceObjectPathArray);

		if ($targetObjectPathArray[count($targetObjectPathArray) - 1]{0} == '$') {
			$this->setObjectVariable($targetObjectPathArray, $value);
		} else {
			$this->setValueInObjectTree($targetObjectPathArray, $value);
		}
	}

	/**
	 * Creates a processor invocation for a property, defined by the method call
	 * and adds it to the position indicated by an index to the processor chain
	 * of the property specified by the object path.
	 *
	 * @param string $indexAndMethodCall The raw string defining the index within the processor chain and the method call. Eg. 3.wrap("<em>", "</em">)
	 * @param string $objectPropertyPath Specifies the object path of the property to process
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseValueProcessing($indexAndMethodCall, $objectPropertyPath) {
		if (preg_match(self::SPLIT_PATTERN_INDEXANDMETHODCALL, $indexAndMethodCall, $matches) > 0) {
			$objectPropertyPathArray = $this->getParsedObjectPath($objectPropertyPath);
			$objectPathArray = array_slice($objectPropertyPathArray, 0, -1);
			$propertyName = implode(array_slice($objectPropertyPathArray, -1, 1));
			$typoScriptObject = $this->getValueFromObjectTree($objectPathArray);

			if (!method_exists($typoScriptObject, 'set' . ucfirst($propertyName))) throw new \F3\TypoScript\Exception('Tried to process the value of a non-existing property "' . $propertyName . '" of a TypoScript object of type "' . get_class($typoScriptObject) . '".', 1181830570);

			$processorArguments = array();
			if (preg_match_all(self::SPLIT_PATTERN_METHODARGUMENTS, $matches['Arguments'], $matchedArguments) > 0) {
				foreach ($matchedArguments[0] as $matchedArgument) {
					$processorArguments[] = $this->getProcessedValue($objectPropertyPathArray, $matchedArgument);
				}
			}

			$processorChain = $typoScriptObject->propertyHasProcessorChain($propertyName) ? $typoScriptObject->getPropertyProcessorChain($propertyName) : $this->objectFactory->create('F3\TypoScript\ProcessorChain');
			$processorInvocation = $this->getProcessorInvocation($matches['ObjectAndMethodName'], $processorArguments);
			$processorChain->setProcessorInvocation((integer)$matches['Index'], $processorInvocation);
			$typoScriptObject->setPropertyProcessorChain($propertyName, $processorChain);
		} else {
			throw new \F3\TypoScript\Exception('Invalid processing instruction "' . $indexAndMethodCall . '"', 1182705997);
		}
	}

	/**
	 * Parses a namespace declaration and stores the result in the namespace registry.
	 *
	 * @param string $namespaceDeclaration The namespace declaration, for example "cms = \F3\TYPO3\TypoScript"
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseNamespaceDeclaration($namespaceDeclaration) {
		$result = preg_match(self::SPLIT_PATTERN_NAMESPACEDECLARATION, $namespaceDeclaration, $matches);
		if ($result !== 1 || count($matches) != 3) throw new \F3\TypoScript\Exception('Invalid namespace declaration "' . $namespaceDeclaration . '"', 1180547190);

		$namespaceIdentifier = $matches[1];
		$objectNamePrefix = $matches[2];
		$this->namespaces[$namespaceIdentifier] = $objectNamePrefix;
	}

	/**
	 * Parses the given object-and-method-name string and then returns a new processor invocation
	 * object calling the specified processor with the given arguments.
	 *
	 * @param string $processorObjectAndMethodName Either just a method name (then the default namespace is used) or a full object/method name as in "F3\Package\Object->methodName"
	 * @param array $processorArguments An array of arguments which are passed to the processor method, in the same order as expected by the processor method.
	 * @return \F3\TypoScript\ProcessorInvocation The prepared processor invocation object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getProcessorInvocation($processorObjectAndMethodName, array $processorArguments) {
		preg_match(self::SPLIT_PATTERN_COMPONENTANDMETHODNAME, $processorObjectAndMethodName, $matchedObjectAndMethodName);

		$processorObjectName = isset($matchedObjectAndMethodName['ObjectName']) ? $matchedObjectAndMethodName['ObjectName'] : $this->namespaces['default'] . '\Processors';
		$processorMethodName = isset($matchedObjectAndMethodName['MethodName']) ? 'processor_' . $matchedObjectAndMethodName['MethodName'] : NULL;
		if (!$this->objectManager->isObjectRegistered($processorObjectName)) throw new \F3\TypoScript\Exception('Unknown processor object "' . $processorObjectName . '"', 1181903857);
		$processor = $this->objectManager->getObject($processorObjectName);
		if (!method_exists($processor, $processorMethodName)) throw new \F3\TypoScript\Exception('Unknown processor method "' . $processorObjectName . '->' . $processorMethodName . '"', 1181903857);

		return $this->objectFactory->create('F3\TypoScript\ProcessorInvocation', $processor, $processorMethodName, $processorArguments);
	}

	/**
	 * Parse an object path specified as a string and returns an array.
	 *
	 * @param string $objectPath The object path to parse
	 * @return array An object path array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getParsedObjectPath($objectPath) {
		if (preg_match(self::SCAN_PATTERN_OBJECTPATH, $objectPath) === 1) {
			if ($objectPath{0} == '.') {
				$objectPath = $this->getCurrentObjectPathPrefix() . \F3\PHP6\Functions::substr($objectPath, 1);
			}
			$objectPathArray = explode('.', $objectPath);
		} else {
			throw new \F3\TypoScript\Exception('Syntax error: Invalid object path "' . $objectPath . '".', 1180603499);
		}
		return $objectPathArray;
	}

	/**
	 * Parses the given value (which may be a literal, variable or object type) and returns
	 * the evaluated result, including variables replaced by their actual value.
	 *
	 * @param array $objectPathArray The object path specifying the location of the value in the object tree
	 * @param string $unparsedValue The unparsed value
	 * @return mixed The processed value
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getProcessedValue(array $objectPathArray, $unparsedValue) {
		if (preg_match(self::SPLIT_PATTERN_VALUENUMBER, $unparsedValue, $matches) === 1) {
			$processedValue = intval($unparsedValue);
		} elseif (preg_match(self::SPLIT_PATTERN_VALUEFLOATNUMBER, $unparsedValue, $matches) === 1) {
			$processedValue = floatval($unparsedValue);
		} elseif (preg_match(self::SPLIT_PATTERN_VALUELITERAL, $unparsedValue, $matches) === 1) {
			$processedValue = stripslashes(isset($matches[2]) ? $matches[2] : $matches[1]);
			$processedValue = $this->getValueWithEvaluatedVariables($processedValue, $objectPathArray);
		} elseif (preg_match(self::SPLIT_PATTERN_VALUEVARIABLE, $unparsedValue, $matches)) {
			$fullVariableName = implode('.', array_slice($objectPathArray, 0, -1)) . '.' . $unparsedValue;
			$processedValue = isset($this->objectVariables[$fullVariableName]) ? $this->objectVariables[$fullVariableName] : NULL;
		} elseif (preg_match(self::SPLIT_PATTERN_VALUEOBJECTTYPE, $unparsedValue, $matches) === 1) {
			if (count($matches) == 4) {
				$typoScriptObjectName = $matches[3];
			} else {
				$namespace = ($matches[1] === '') ? 'default' : $matches[1];
				if (!isset($this->namespaces[$namespace]) || \F3\PHP6\Functions::strlen($this->namespaces[$namespace]) == 0) throw new \F3\TypoScript\Exception('Referring to undefined namespace "' . $namespace . '" in object type assignment.', 1180605249);
				$typoScriptObjectName = $this->namespaces[$namespace] . '\\' . $matches[2];
			}
			if (!$this->objectManager->isObjectRegistered($typoScriptObjectName)) {
				throw new \F3\TypoScript\Exception('Referring to unknown TypoScript Object Type "' . $typoScriptObjectName . '" in object type assignment.', 1180605250);
			}
			$processedValue = $this->objectFactory->create($typoScriptObjectName);
		} else {
			throw new \F3\TypoScript\Exception('Syntax error: Invalid value "' . $unparsedValue . '" in value assignment.', 1180604192);
		}
		return $processedValue;
	}

	/**
	 * Analyzes the given string and if variables are found within, they are
	 * replaced by their actual value.
	 *
	 * @param string $subject The string to analyze
	 * @param array $objectPathArray The current object path, used as a prefix to the variable name
	 * @return string The original string but with variables replaced by their values
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getValueWithEvaluatedVariables($subject, $objectPathArray) {
		if (preg_match_all(self::SPLIT_PATTERN_VALUEVARIABLES, $subject, $matchedVariables) > 0) {
			foreach ($matchedVariables[0] as $index => $variableName) {
				$pattern = '/\\' . $variableName . '(?=[^a-zA-Z0-9]|$)/';
				$fullVariableName = implode('.', array_slice($objectPathArray, 0, -1)) . '.' . $variableName;
				$replacement = isset($this->objectVariables[$fullVariableName]) ? $this->objectVariables[$fullVariableName] : '';
				$subject = preg_replace($pattern, $replacement, $subject);
			}
		}
		return $subject;
	}

	/**
	 * Assigns a value to an object variable, specified by the object path array
	 *
	 * @param array $objectPathArray The object path, specifying the node and variable name to set
	 * @param mixed $value The value to assign
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function setObjectVariable(array $objectPathArray, $value) {
		$this->objectVariables[implode('.', $objectPathArray)] = $value;
	}

	/**
	 * Assigns a value to a node or a property in the object tree, specified by the object path array.
	 *
	 * @param array $objectPathArray The object path, specifying the node / property to set
	 * @param mixed $value The value to assign
	 * @param array $objectTree The current (sub-) tree, used internally - don't specify!
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function setValueInObjectTree(array $objectPathArray, $value, $objectTree = NULL) {
		if (is_null($objectTree)) $objectTree = &$this->objectTree;

		if (is_object($value)) {
			if (count($objectPathArray)) {
				$currentKey = array_shift($objectPathArray);
				if ((integer)$currentKey > 0) $currentKey = intval($currentKey);
				$this->setChildNodeToEmptyArrayIfNeccessary($objectTree, $currentKey);
				$objectTree[$currentKey] = $this->setValueInObjectTree($objectPathArray, $value, $objectTree[$currentKey]);
			} else {
				$objectTree = $value;
			}
		} else {
			$currentKey = array_shift($objectPathArray);
			if ((integer)$currentKey > 0) $currentKey = (integer)$currentKey;
			if (count($objectPathArray) > 1) {
				$this->setChildNodeToEmptyArrayIfNeccessary($objectTree, $currentKey);
				$objectTree[$currentKey] = $this->setValueInObjectTree($objectPathArray, $value, $objectTree[$currentKey]);
			} else {
				$propertyName = array_shift($objectPathArray);
				if ($propertyName === NULL && $value === NULL) {
					unset($objectTree[$currentKey]);
				} else {
					$this->setObjectProperty($objectTree[$currentKey], $propertyName, $value);
				}
			}
		}
		return $objectTree;
	}

	/**
	 * Retrieves a value from a node in the object tree, specified by the object path array.
	 *
	 * @param array $objectPathArray The object path, specifying the node to retrieve the value of
	 * @param array $objectTree The current (sub-) tree, used internally - don't specify!
	 * @return mixed The value
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getValueFromObjectTree(array $objectPathArray, $objectTree = NULL) {
		if (is_null($objectTree)) $objectTree = &$this->objectTree;

		if (count($objectPathArray) > 0) {
			$currentKey = array_shift($objectPathArray);
			if ((integer)$currentKey > 0) $currentKey = intval($currentKey);
			if (is_object($objectTree) && !is_integer($currentKey)) {
				$value = $this->getObjectProperty($objectTree, $currentKey);
			} else {
				if (isset($objectTree[$currentKey])) {
					$value = $this->getValueFromObjectTree($objectPathArray, $objectTree[$currentKey]);
				} else {
					throw new \F3\TypoScript\Exception('Tried to retrieve value from non existing object path location.', 1181743514);
				}
			}
		} else {
			$value = $objectTree;
		}
		return $value;
	}

	/**
	 * Sets the property of a TypoScript object by calling the setter method
	 * with the name specified by $propertyName.
	 *
	 * @param \F3\TypoScript\Object $object The TypoScript object which has the property
	 * @param string $propertyName Name of the property to set
	 * @param mixed $value The value to assign to the property
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see setValueInObjectTree()
	 */
	protected function setObjectProperty(&$object, $propertyName, $value) {
		$setterMethodName = 'set' . ucfirst($propertyName);
		if (!is_object($object)) throw new \F3\TypoScript\Exception('Tried to assign a value to property "' . $propertyName . '" of a non existing TypoScript object.', 1180609654);
		if (!method_exists($object, $setterMethodName)) throw new \F3\TypoScript\Exception('Tried to assign a value to the non-existing property "' . $propertyName . '" to an object of type "' . get_class($object) . '"', 1180609654);
		$object->$setterMethodName($value);
	}

	/**
	 * Retrieves the property of a TypoScript object by calling the getter method
	 * with the name specified by $propertyName.
	 *
	 * @param \F3\TypoScript\Object $object The TypoScript object which has the property
	 * @param string $propertyName Name of the property to fetch
	 * @return mixed $value The value of the property
	 * @author Robert Lemke <robert@typo3.org>
	 * @see getValueFromObjectTree()
	 */
	protected function getObjectProperty(&$object, $propertyName) {
		$getterMethodName = 'get' . ucfirst($propertyName);
		if (!is_object($object)) throw new \F3\TypoScript\Exception('Tried to retrieve a property "' . $propertyName . '" from a non existing TypoScript object.', 1181745677);
		if (!method_exists($object, $getterMethodName)) throw new \F3\TypoScript\Exception('Tried to retrieve a non-existing property "' . $propertyName . '" from an object of type "' . get_class($object) . '"', 1181745678);
		return $object->$getterMethodName();
	}

	/**
	 * Sets the child node of $objectTree specified by $childNodeKey to an empty array
	 * if the childNodeKey is not the offset of a Content Array and the child node is
	 * not already an array or Content Array.
	 *
	 * @param array &$objectTree An object tree or sub part of the object tree which contains the child node
	 * @param string $childNodeKey Key in the objectTree which identifies the child node
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see setValueInObjectTree()
	 */
	protected function setChildNodeToEmptyArrayIfNeccessary(&$objectTree, $childNodeKey) {
		if (!is_integer($childNodeKey) && !isset($objectTree[$childNodeKey])) {
			$objectTree[$childNodeKey] = array();
		}
	}

	/**
	 * Returns the first part of an object path from the current object path stack
	 * which can be used to prefix a relative object path.
	 *
	 * @return string A part of an object path, ready to use as a prefix
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getCurrentObjectPathPrefix() {
		return (count($this->currentObjectPathStack) > 0) ? $this->currentObjectPathStack[count($this->currentObjectPathStack) - 1] . '.' : '';
	}
}
?>