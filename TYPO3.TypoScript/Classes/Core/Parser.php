<?php
namespace TYPO3\TypoScript\Core;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * The TypoScript Parser
 *
 * @api
 */
class Parser implements \TYPO3\TypoScript\Core\ParserInterface {

	const SCAN_PATTERN_COMMENT = '/
		^\s*                       # beginning of line; with numerous whitespace
		(
			\#                     # this can be a comment char
			|\/\/                  # or two slashes
			|\/\*                  # or slash followed by star
		)
	/x';
	const SCAN_PATTERN_OPENINGCONFINEMENT = '/
		^\s*                      # beginning of line; with numerous whitespace
		[a-zA-Z0-9():]*           # first part of a TS path
		(?:                       # followed by multiple .<tsPathPart> sections:
			\.
			[a-zA-Z0-9():]*
		)*
		\s*                       # followed by multiple whitespace
		\{                        # followed by opening {
		\s*$                      # followed by multiple whitespace (possibly) and nothing else.
	/x';

	const SCAN_PATTERN_CLOSINGCONFINEMENT = '/
		^\s*                      # beginning of line; with numerous whitespace
		\}                        # closing confinement
		\s*$                      # followed by multiple whitespace (possibly) and nothing else.
	/x';
	const SCAN_PATTERN_DECLARATION = '/
		^\s*                      # beginning of line; with numerous whitespace
		(include|namespace)       # followed by namespace or include
		\s*:                      # followed by numerous whitespace and a colon
	/x';
	const SCAN_PATTERN_OBJECTDEFINITION = '/
		^\s*                      # beginning of line; with numerous whitespace
		[a-zA-Z0-9.\\\\$():]+
		\s*
		(=|<|>|\.processors\.)
	/x';
	const SCAN_PATTERN_OBJECTPATH = '/
		^
			\.?
			(?:
				[a-zA-Z0-9]*
				| prototype\([a-zA-Z0-9.:]+\)
			)
			(?:
				\.
				(?:
					[a-zA-Z0-9]*
					| prototype\([a-zA-Z0-9.:]+\)
				)
			)*
		$
	/x';

	/**
	 * Split an object path like "foo.bar.baz.asdf" or "foo.prototype(TYPO3.TypoScript:Blah).bar.baz"
	 * at the dots (but not the dots inside the prototype definition prototype(...))
	 */
	const SPLIT_PATTERN_OBJECTPATH = '/
		\.                       # we split at dot characters...
		(?!                      # which are not inside prototype(...). Thus, the dot does NOT match IF it is followed by:
			[^(]*                # - any character except (
			\)                    # - the character )
		)
	/x';

	/**
	 * Analyze an object path segment like foo" or "prototype(TYPO3.TypoScript:Blah)"
	 * and detect the latter
	 */
	const SCAN_PATTERN_OBJECTPATHSEGMENT_IS_PROTOTYPE = '/
		^
			prototype\([a-zA-Z0-9:.]+\)
		$
	/x';

	const SPLIT_PATTERN_COMMENTTYPE = '/.*(#|\/\/|\/\*|\*\/).*/';
	const SPLIT_PATTERN_DECLARATION = '/([a-zA-Z]+[a-zA-Z0-9]*)\s*:(.*)/';
	const SPLIT_PATTERN_NAMESPACEDECLARATION = '/\s*([a-zA-Z]+[a-zA-Z0-9]*)\s*=\s*(\w(?:\w+|\\\\)+)/';
	const SPLIT_PATTERN_OBJECTDEFINITION = '/
		^\s*                              # beginning of line; with numerous whitespace
		(?P<ObjectPath>                   # begin ObjectPath

			\.?
			(?:
				[a-zA-Z0-9]*
				|prototype\([a-zA-Z0-9.:]+\)
			)
			(?:
				\.
				(?:
					[a-zA-Z0-9]*
					|prototype\([a-zA-Z0-9.:]+\)
				)
			)*
		)
		\s*
		(?P<Operator>                     # the operators which are supported
			=<|=|<<|<|>
		)
		\s*
		(?P<Value>                        # the remaining line inside the value
			.*
		)$
	/x';
	const SPLIT_PATTERN_VALUENUMBER = '/^\s*-?\d+\s*$/';
	const SPLIT_PATTERN_VALUEFLOATNUMBER = '/^\s*-?\d+(\.\d+)?\s*$/';
	const SPLIT_PATTERN_VALUELITERAL = '/^"((?:\\\\.|[^\\\\"])*)"|\'((?:\\\\.|[^\\\\\'])*)\'$/';
	const SPLIT_PATTERN_VALUEMULTILINELITERAL = '/^(?P<DoubleQuoteChar>")(?P<DoubleQuoteValue>(?:\\\\.|[^\\\\"])*)$|(?P<SingleQuoteChar>\')(?P<SingleQuoteValue>(?:\\\\.|[^\\\\\'])*)$/';


	const SCAN_PATTERN_VALUEOBJECTTYPE = '/
		^\s*                      # beginning of line; with numerous whitespace
		(?:                       # non-capturing submatch containing the namespace followed by ":" (optional)
			(?P<namespace>
				[a-zA-Z0-9.]+     # Namespace
			)
			:                     # : as delimiter
		)?
		(?P<unqualifiedType>
			[a-zA-Z0-9.]+         # the unqualified type
		)
		\s*$
	/x';


	const SPLIT_PATTERN_INDEXANDPROCESSORCALL = '/(?P<Index>\d+)\.(?P<ProcessorSignature>[^(]+)\s*\((?P<Arguments>.*?)\)\s*$/';
	const SPLIT_PATTERN_NAMESPACEANDPROCESSORNAME = '/(?:(?P<NamespaceReference>[a-zA-Z]+[a-zA-Z0-9]*+)\s*:\s*)?(?P<ProcessorName>\w+)/';
	const SPLIT_PATTERN_PROCESSORARGUMENTS = '/(?P<ArgumentName>[a-zA-Z0-9]+):\s*(?P<ArgumentValue>"(?:\\\\.|[^\\\\"])*"|\'(?:\\\\.|[^\\\\\'])*\'|-?[0-9]+(\.\d+)?)/';


	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * The TypoScript object tree, created by this parser.
	 * @var array
	 */
	protected $objectTree = array();

	/**
	 * The line number which is currently processed
	 * @var integer
	 */
	protected $currentLineNumber = 1;

	/**
	 * An array of strings of the source code which has
	 * @var array
	 */
	protected $currentSourceCodeLines = array();

	/**
	 * The current object path context as defined by confinements.
	 * @var array
	 */
	protected $currentObjectPathStack = array();

	/**
	 * Determines if a block comment is currently active or not.
	 * @var boolean
	 */
	protected $currentBlockCommentState = FALSE;

	/**
	 * Namespace identifiers and their object name prefix
	 * @var array
	 */
	protected $namespaces = array(
		'default' => ''
	);

	protected $contextPathAndFilename = NULL;

	/**
	 * Parses the given TypoScript source code and returns an object tree
	 * as the result.
	 *
	 * @param string $sourceCode The TypoScript source code to parse
	 * @return \TYPO3\TypoScript\ObjectTree A TypoScript object tree, generated from the source code
	 * @api
	 */
	public function parse($sourceCode, $contextPathAndFilename = NULL, $objectTreeUntilNow = array()) {
		if (!is_string($sourceCode)) throw new \TYPO3\TypoScript\Exception('Cannot parse TypoScript - $sourceCode must be of type string!', 1180203775);
		$this->initialize();
		$this->objectTree = $objectTreeUntilNow;
		$this->contextPathAndFilename = $contextPathAndFilename;
		$this->currentSourceCodeLines = explode(chr(10), $sourceCode);
		while(($typoScriptLine = $this->getNextTypoScriptLine()) !== FALSE) {
			$this->parseTypoScriptLine($typoScriptLine);
		}
		return $this->objectTree;
	}

	/**
	 * Sets the default namespace to the given object name prefix
	 *
	 * @param string $objectNamePrefix The object name to prepend as the default namespace, without trailing \
	 * @return void
	 * @api
	 */
	public function setDefaultNamespace($objectNamePrefix) {
		if (!is_string($objectNamePrefix)) throw new \TYPO3\TypoScript\Exception('The object name prefix for the default namespace must be of type string!', 1180600696);
		$this->namespaces['default'] = $objectNamePrefix;
	}

	/**
	 * Initializes the TypoScript parser
	 *
	 * @return void
	 */
	protected function initialize() {
		$this->currentLineNumber = 1;
		$this->currentObjectPathStack = array();
		$this->currentSourceCodeLines = array();
		$this->currentBlockCommentState = FALSE;
		$this->objectTree = array();
	}

	/**
	 * Get the next, unparsed line of TypoScript from this->currentSourceCodeLines and increase the pointer
	 *
	 * @return string next line of typoscript to parse
	 */
	protected function getNextTypoScriptLine() {
		$typoScriptLine = current($this->currentSourceCodeLines);
		next($this->currentSourceCodeLines);
		$this->currentLineNumber ++;
		return $typoScriptLine;
	}

	/**
	 * Parses one line of TypoScript
	 *
	 * @param string $typoScriptLine One line of TypoScript code
	 * @return void
	 */
	protected function parseTypoScriptLine($typoScriptLine) {
		$typoScriptLine = trim($typoScriptLine);

		if ($this->currentBlockCommentState === TRUE) {
			$this->parseComment($typoScriptLine);
		} else {
			if ($typoScriptLine === '') {
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
				throw new \TYPO3\TypoScript\Exception('Syntax error in line ' . $this->currentLineNumber . '. (' . $typoScriptLine . ')', 1180547966);
			}
		}
	}

	/**
	 * Parses a line with comments or a line while parsing is in block comment mode.
	 *
	 * @param string $typoScriptLine One line of TypoScript code
	 * @return void
	 */
	protected function parseComment($typoScriptLine) {
		if (preg_match(self::SPLIT_PATTERN_COMMENTTYPE, $typoScriptLine, $matches, PREG_OFFSET_CAPTURE) === 1) {
			switch ($matches[1][0]) {
				case '/*' :
					$this->currentBlockCommentState = TRUE;
					break;
				case '*/' :
					if ($this->currentBlockCommentState !== TRUE) throw new \TYPO3\TypoScript\Exception('Unexpected closing block comment without matching opening block comment.', 1180615119);
					$this->currentBlockCommentState = FALSE;
					$this->parseTypoScriptLine(substr($typoScriptLine, ($matches[1][1] + 2)));
					break;
				case '#' :
				case '//' :
				default :
					break;
			}
		} elseif ($this->currentBlockCommentState === FALSE) {
			throw new \TYPO3\TypoScript\Exception('No comment type matched although the comment scan regex matched the TypoScript line (' . $typoScriptLine . ').', 1180614895);
		}
	}

	/**
	 * Parses a line which opens or closes a confinement
	 *
	 * @param string $typoScriptLine One line of TypoScript code
	 * @param boolean $isOpeningConfinement Set to TRUE, if an opening confinement is to be parsed and FALSE if it's a closing confinement.
	 * @return void
	 */
	protected function parseConfinementBlock($typoScriptLine, $isOpeningConfinement) {
		if ($isOpeningConfinement) {
			$result = trim(trim(trim($typoScriptLine), '{'));
			array_push($this->currentObjectPathStack, $this->getCurrentObjectPathPrefix() . $result);
		} else {
			if (count($this->currentObjectPathStack) < 1) throw new \TYPO3\TypoScript\Exception('Unexpected closing confinement without matching opening confinement. Check the number of your curly braces.', 1181575973);
			array_pop($this->currentObjectPathStack);
		}
	}

	/**
	 * Parses a parser declaration of the form "declarationtype: declaration".
	 *
	 * @param string $typoScriptLine One line of TypoScript code
	 * @return void
	 */
	protected function parseDeclaration($typoScriptLine) {
		$result = preg_match(self::SPLIT_PATTERN_DECLARATION, $typoScriptLine, $matches);
		if ($result !== 1 || count($matches) != 3) throw new \TYPO3\TypoScript\Exception('Invalid declaration "' . $typoScriptLine . '"', 1180544656);

		switch ($matches[1]) {
			case 'namespace' :
				$this->parseNamespaceDeclaration($matches[2]);
				break;
			case 'include' :
				$this->parseInclude($matches[2]);
				break;
		}
	}

	/**
	 * Parses an object definition.
	 *
	 * @param string $typoScriptLine One line of TypoScript code
	 * @return void
	 */
	protected function parseObjectDefinition($typoScriptLine) {
		$result = preg_match(self::SPLIT_PATTERN_OBJECTDEFINITION, $typoScriptLine, $matches);
		if ($result !== 1) throw new \TYPO3\TypoScript\Exception('Invalid object definition "' . $typoScriptLine . '"', 1180548488);

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
	 */
	protected function parseValueAssignment($objectPath, $value) {
		$objectPathArray = $this->getParsedObjectPath($objectPath);
		$processedValue = $this->getProcessedValue($objectPathArray, $value);

		$this->setValueInObjectTree($objectPathArray, $processedValue);
	}

	/**
	 * Unsets the object, property or variable specified by the object path.
	 *
	 * @param string $objectPath The object path as a string
	 * @return void
	 */
	protected function parseValueUnAssignment($objectPath) {
		$objectPathArray = $this->getParsedObjectPath($objectPath);
		$this->setValueInObjectTree($objectPathArray, NULL);
	}

	/**
	 * Copies the object or value specified by sourcObjectPath and assigns
	 * it to targetObjectPath.
	 *
	 * @param string $sourceObjectPath Specifies the location in the object tree from where the object or value will be taken
	 * @param string $targetObjectPath Specifies the location in the object tree where the copy will be stored
	 * @return void
	 */
	protected function parseValueCopy($sourceObjectPath, $targetObjectPath) {
		$sourceObjectPathArray = $this->getParsedObjectPath($sourceObjectPath);
		$targetObjectPathArray = $this->getParsedObjectPath($targetObjectPath);

		$originalValue = $this->getValueFromObjectTree($sourceObjectPathArray);
		$value = is_object($originalValue) ? clone $originalValue : $originalValue;

		$this->setValueInObjectTree($targetObjectPathArray, $value);
	}

	/**
	 * Assigns a reference of an object or value specified by sourcObjectPath to
	 * the targetObjectPath.
	 *
	 * @param string $sourceObjectPath Specifies the location in the object tree from where the object or value will be taken
	 * @param string $targetObjectPath Specifies the location in the object tree where the reference will be stored
	 * @return void
	 */
	protected function parseValueReference($sourceObjectPath, $targetObjectPath) {
		$sourceObjectPathArray = $this->getParsedObjectPath($sourceObjectPath);
		$targetObjectPathArray = $this->getParsedObjectPath($targetObjectPath);

		$value = $this->getValueFromObjectTree($sourceObjectPathArray);

		$this->setValueInObjectTree($targetObjectPathArray, $value);
	}

	/**
	 * Creates a processor invocation for a property, defined by the method call
	 * and adds it to the position indicated by an index to the processor chain
	 * of the property specified by the object path.
	 *
	 * @param string $indexAndMethodCall The raw string defining the index within the processor chain and the method call. Eg. 3.wrap("<em>", "</em">)
	 * @param string $objectPropertyPath Specifies the object path of the property to process
	 * @return void
	 */
	protected function parseValueProcessing($indexAndMethodCall, $objectPropertyPath) {
		if (preg_match(self::SPLIT_PATTERN_INDEXANDPROCESSORCALL, $indexAndMethodCall, $matches) > 0) {
			$objectPropertyPathArray = $this->getParsedObjectPath($objectPropertyPath);

			$typoScriptObject = &$this->getValueFromObjectTree($objectPropertyPathArray);
			if (is_array($typoScriptObject) && isset($typoScriptObject['__objectType'])) {
				$propertyName = '__all';
			} else {
				$objectPathArray = array_slice($objectPropertyPathArray, 0, -1);
				$propertyName = implode(array_slice($objectPropertyPathArray, -1, 1));
				$typoScriptObject = &$this->getValueFromObjectTree($objectPathArray);
			}


			// TODO: re-implement some checking logic

			$processorArguments = array();
			if (preg_match_all(self::SPLIT_PATTERN_PROCESSORARGUMENTS, $matches['Arguments'], $matchedArguments) > 0) {
				foreach ($matchedArguments['ArgumentValue'] as $argumentIndex => $matchedArgumentValue) {
					$matchedArgumentName = $matchedArguments['ArgumentName'][$argumentIndex];
					$processorArguments[$matchedArgumentName] = $this->getProcessedValue($objectPropertyPathArray, $matchedArgumentValue);
				}
			}

			$processorArguments['__processorClassName'] = $this->getProcessorObjectName($matches['ProcessorSignature']);

			$typoScriptObject['__processors'][$propertyName][$matches['Index']] = $processorArguments;
			ksort($typoScriptObject['__processors'][$propertyName]);
		} else {
			throw new \TYPO3\TypoScript\Exception('Invalid processing instruction "' . $indexAndMethodCall . '"', 1182705997);
		}
	}

	/**
	 * Parses a namespace declaration and stores the result in the namespace registry.
	 *
	 * @param string $namespaceDeclaration The namespace declaration, for example "cms = TYPO3\TYPO3\TypoScript"
	 * @return void
	 */
	protected function parseNamespaceDeclaration($namespaceDeclaration) {
		$result = preg_match(self::SPLIT_PATTERN_NAMESPACEDECLARATION, $namespaceDeclaration, $matches);
		if ($result !== 1 || count($matches) !== 3) throw new \TYPO3\TypoScript\Exception('Invalid namespace declaration "' . $namespaceDeclaration . '"', 1180547190);

		$namespaceIdentifier = $matches[1];
		$objectNamePrefix = $matches[2];
		$this->namespaces[$namespaceIdentifier] = $objectNamePrefix;
	}

	/**
	 * Parse an include file. Currently, we start a new parser object; but we could as well re-use
	 * the given one.
	 *
	 * @param string $include The include, for example " FooBar" or " resource://...."
	 */
	protected function parseInclude($include) {
		$include = trim($include);
		$parser = new Parser();
		if (strpos($include, 'resource://') === 0) {
			// String starts with resource path, so we load the file directly
			$this->objectTree = $parser->parse(file_get_contents($include), $include, $this->objectTree);
		} elseif ($this->contextPathAndFilename !== NULL) {
			$include = dirname($this->contextPathAndFilename) . '/' . $include;
			$this->objectTree = $parser->parse(file_get_contents($include), $include, $this->objectTree);
		} else {
			throw new \TYPO3\TypoScript\Exception('Relative file inclusions are only possible if a context path and filename has been passed as second argument to parse()', 1329806940);
		}
	}

	/**
	 * Parses the given object-and-method-name string and then returns a new processor invocation
	 * object calling the specified processor with the given arguments.
	 *
	 * @param string $processorSignature Either just a method name (then the default namespace is used) or a full object/method name as in "TYPO3\Package\Object->methodName"
	 * @param array $processorArguments An array of arguments which are passed to the processor method, in the same order as expected by the processor method.
	 * @return string the processor object name
	 */
	protected function getProcessorObjectName($processorSignature) {
		$matchedObjectAndMethodName = array();
		preg_match(self::SPLIT_PATTERN_NAMESPACEANDPROCESSORNAME, $processorSignature, $matchedObjectAndMethodName);

		if (isset($matchedObjectAndMethodName['NamespaceReference']) && strlen($matchedObjectAndMethodName['NamespaceReference']) > 0) {
			$namespaceReference = $matchedObjectAndMethodName['NamespaceReference'];
			if (!isset($this->namespaces[$namespaceReference]) || strlen($this->namespaces[$namespaceReference]) === 0) throw new \TYPO3\TypoScript\Exception('Referring to undefined namespace "' . $namespaceReference . '" in processor invocation.', 1278451837);
			$processorNamespace = $this->namespaces[$namespaceReference];
		} else {
			$processorNamespace = $this->namespaces['default'];
		}
		$processorNamespace .= '\Processors';
		$processorObjectName = $processorNamespace . '\\' . ucfirst($matchedObjectAndMethodName['ProcessorName']) . 'Processor';

		if (!$this->objectManager->isRegistered($processorObjectName)) {
			throw new \TYPO3\TypoScript\Exception('Unknown processor object "' . $processorObjectName . '"', 1181903856);
		}

		return $processorObjectName;
	}

	/**
	 * Parse an object path specified as a string and returns an array.
	 *
	 * @param string $objectPath The object path to parse
	 * @return array An object path array
	 */
	protected function getParsedObjectPath($objectPath) {
		if (preg_match(self::SCAN_PATTERN_OBJECTPATH, $objectPath) === 1) {
			if ($objectPath[0] == '.') {
				$objectPath = $this->getCurrentObjectPathPrefix() . substr($objectPath, 1);
			}

			$objectPathArray = array();
			foreach (preg_split(self::SPLIT_PATTERN_OBJECTPATH, $objectPath) as $objectPathSegment) {
				if (preg_match(self::SCAN_PATTERN_OBJECTPATHSEGMENT_IS_PROTOTYPE, $objectPathSegment)) {
					$objectPathArray[] = '__prototypes';
					$objectPathArray[] = substr($objectPathSegment, 10, -1);
				} else {
					$objectPathArray[] = $objectPathSegment;
				}
			}
		} else {
			throw new \TYPO3\TypoScript\Exception('Syntax error: Invalid object path "' . $objectPath . '".', 1180603499);
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
	 */
	protected function getProcessedValue(array $objectPathArray, $unparsedValue) {
		if (preg_match(self::SPLIT_PATTERN_VALUENUMBER, $unparsedValue, $matches) === 1) {
			$processedValue = intval($unparsedValue);
		} elseif (preg_match(self::SPLIT_PATTERN_VALUEFLOATNUMBER, $unparsedValue, $matches) === 1) {
			$processedValue = floatval($unparsedValue);
		} elseif (preg_match(\TYPO3\Eel\Package::EelExpressionRecognizer, $unparsedValue, $matches) === 1) {
			$processedValue = array(
				'__eelExpression' => $matches[1]
			);
		} elseif (preg_match(self::SPLIT_PATTERN_VALUELITERAL, $unparsedValue, $matches) === 1) {
			$processedValue = stripslashes(isset($matches[2]) ? $matches[2] : $matches[1]);
		} elseif (preg_match(self::SPLIT_PATTERN_VALUEMULTILINELITERAL, $unparsedValue, $matches) === 1) {
			$processedValue = stripslashes(isset($matches['SingleQuoteValue']) ? $matches['SingleQuoteValue'] : $matches['DoubleQuoteValue']);
			$closingQuoteChar = isset($matches['SingleQuoteChar']) ? $matches['SingleQuoteChar'] : $matches['DoubleQuoteChar'];
			$regexp = '/(?P<Value>(?:\\\\.|[^\\\\' . $closingQuoteChar . '])*)(?P<QuoteChar>' . $closingQuoteChar . '?)/';
			while(($typoScriptLine = $this->getNextTypoScriptLine()) !== FALSE) {
				preg_match($regexp, $typoScriptLine, $matches);
				$processedValue .= "\n" . stripslashes($matches['Value']);
				if (!empty($matches['QuoteChar'])) {
					break;
				}
			}
		} elseif (preg_match(self::SCAN_PATTERN_VALUEOBJECTTYPE, $unparsedValue, $matches) === 1) {
			$typoScriptObjectType = $matches[0];
			if (!empty($matches['namespace'])) {
				$namespace = $matches['namespace'];
			} else {
				$namespace = 'TYPO3.TypoScript'; // Default Namespace
			}
			$unqualifiedType = $matches['unqualifiedType'];
			$processedValue = array(
				'__objectType' => $namespace . ':' . $unqualifiedType
			);
		} else {
			throw new \TYPO3\TypoScript\Exception('Syntax error: Invalid value "' . $unparsedValue . '" in value assignment.', 1180604192);
		}
		return $processedValue;
	}

	/**
	 * Assigns a value to a node or a property in the object tree, specified by the object path array.
	 *
	 * @param array $objectPathArray The object path, specifying the node / property to set
	 * @param mixed $value The value to assign
	 * @param array $objectTree The current (sub-) tree, used internally - don't specify!
	 * @return void
	 */
	protected function setValueInObjectTree(array $objectPathArray, $value, $objectTree = NULL) {
		if ($objectTree === NULL) {
			$objectTree = &$this->objectTree;
		}

		$currentKey = array_shift($objectPathArray);
		if ((integer)$currentKey > 0) {
			$currentKey = (integer)$currentKey;
		}

		if (count($objectPathArray) > 1) {
			$this->setChildNodeToEmptyArrayIfNeccessary($objectTree, $currentKey);

			$objectTree[$currentKey] = $this->setValueInObjectTree($objectPathArray, $value, $objectTree[$currentKey]);
		} elseif (count($objectPathArray) === 1) {
			$this->setChildNodeToEmptyArrayIfNeccessary($objectTree, $currentKey);
			$propertyName = array_shift($objectPathArray);

			if ($propertyName === NULL && $value === NULL) {
				unset($objectTree[$currentKey]);
			} else {
				if (!isset($objectTree[$currentKey])) {
					$objectTree[$currentKey] = array();
				}
				\TYPO3\FLOW3\Reflection\ObjectAccess::setProperty($objectTree[$currentKey], $propertyName, $value);
			}
		} else {
			if ($value === NULL && (is_array($objectTree) || $objectTree instanceof \ArrayAccess)) {
				unset($objectTree[$currentKey]);
			} else {
				\TYPO3\FLOW3\Reflection\ObjectAccess::setProperty($objectTree, $currentKey, $value);
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
	 */
	protected function &getValueFromObjectTree(array $objectPathArray, &$objectTree = NULL) {
		if (is_null($objectTree)) $objectTree = &$this->objectTree;

		if (count($objectPathArray) > 0) {
			$currentKey = array_shift($objectPathArray);
			if ((integer)$currentKey > 0) $currentKey = intval($currentKey);
			if (!isset($objectTree[$currentKey])) {
				$objectTree[$currentKey] = array();
			}
			$value = &$this->getValueFromObjectTree($objectPathArray, $objectTree[$currentKey]);
		} else {
			$value = &$objectTree;
		}
		return $value;
	}

	/**
	 * Sets the child node of $objectTree specified by $childNodeKey to an empty array
	 * if the childNodeKey is not the offset of a Content Array and the child node is
	 * not already an array or Content Array.
	 *
	 * @param mixed &$objectTree An object tree or sub part of the object tree which contains the child node
	 * @param string $childNodeKey Key in the objectTree which identifies the child node
	 * @return void
	 * @see setValueInObjectTree()
	 */
	protected function setChildNodeToEmptyArrayIfNeccessary(&$objectTree, $childNodeKey) {
		if (!is_object($objectTree) && !is_integer($childNodeKey) && !isset($objectTree[$childNodeKey])) {
			$objectTree[$childNodeKey] = array();
		}
	}

	/**
	 * Returns the first part of an object path from the current object path stack
	 * which can be used to prefix a relative object path.
	 *
	 * @return string A part of an object path, ready to use as a prefix
	 */
	protected function getCurrentObjectPathPrefix() {
		return (count($this->currentObjectPathStack) > 0) ? $this->currentObjectPathStack[count($this->currentObjectPathStack) - 1] . '.' : '';
	}
}
?>