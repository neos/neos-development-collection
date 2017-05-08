<?php
namespace Neos\Fusion\Core;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\Package;
use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Exception;
use Neos\Utility\Arrays;
use Neos\Fusion;

/**
 * The Fusion Parser
 *
 * @api
 */
class Parser implements ParserInterface
{
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
		(?:                       # first part of a TS path
			@?[a-zA-Z0-9:_\-]+              # Unquoted key
			|"(?:\\\"|[^"])+"               # Double quoted key, supporting more characters like underscore and at sign
			|\'(?:\\\\\'|[^\'])+\'          # Single quoted key, supporting more characters like underscore and at sign
			|prototype\([a-zA-Z0-9.:]+\)    # Prototype definition
		)
		(?:                                 # followed by multiple .<tsPathPart> sections:
			\.
			(?:
				@?[a-zA-Z0-9:_\-]+              # Unquoted key
				|"(?:\\\"|[^"])+"               # Double quoted key, supporting more characters like underscore and at sign
				|\'(?:\\\\\'|[^\'])+\'          # Single quoted key, supporting more characters like underscore and at sign
				|prototype\([a-zA-Z0-9.:]+\)    # Prototype definition
			)
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
		^\s*                             # beginning of line; with numerous whitespace
		(?:
			[a-zA-Z0-9.():@_\-]+         # Unquoted key
			|"(?:\\\"|[^"])+"            # Double quoted key, supporting more characters like underscore and at sign
			|\'(?:\\\\\'|[^\'])+\'       # Single quoted key, supporting more characters like underscore and at sign
		)+
		\s*
		(=|<|>)
	/x';
    const SCAN_PATTERN_OBJECTPATH = '/
		^
			\.?
			(?:
				@?[a-zA-Z0-9:_\-]+              # Unquoted key
				|"(?:\\\"|[^"])+"               # Double quoted key, supporting more characters like underscore and at sign
				|\'(?:\\\\\'|[^\'])+\'          # Single quoted key, supporting more characters like underscore and at sign
				|prototype\([a-zA-Z0-9.:]+\)    # Prototype definition
			)
			(?:
				\.
				(?:
					@?[a-zA-Z0-9:_\-]+              # Unquoted key
					|"(?:\\\"|[^"])+"               # Double quoted key, supporting more characters like underscore and at sign
					|\'(?:\\\\\'|[^\'])+\'          # Single quoted key, supporting more characters like underscore and at sign
					|prototype\([a-zA-Z0-9.:]+\)    # Prototype definition
				)
			)*
		$
	/x';

    /**
     * Split an object path like "foo.bar.baz.quux" or "foo.prototype(Neos.Fusion:Something).bar.baz"
     * at the dots (but not the dots inside the prototype definition prototype(...))
     */
    const SPLIT_PATTERN_OBJECTPATH = '/
		\.                         # we split at dot characters...
		(?!                        # which are not inside prototype(...). Thus, the dot does NOT match IF it is followed by:
			[^(]*                  # - any character except (
			\)                     # - the character )
		)
	/x';

    /**
     * Analyze an object path segment like "foo" or "prototype(Neos.Fusion:Something)"
     * and detect the latter
     */
    const SCAN_PATTERN_OBJECTPATHSEGMENT_IS_PROTOTYPE = '/
		^
			prototype\([a-zA-Z0-9:.]+\)
		$
	/x';

    const SPLIT_PATTERN_COMMENTTYPE = '/.*?(#|\/\/|\/\*|\*\/).*/';  // we need to be "non-greedy" here, since we need the first comment type that matches
    const SPLIT_PATTERN_DECLARATION = '/(?P<declarationType>[a-zA-Z]+[a-zA-Z0-9]*)\s*:\s*(["\']{0,1})(?P<declaration>.*)\\2/';
    const SPLIT_PATTERN_NAMESPACEDECLARATION = '/\s*(?P<alias>[a-zA-Z]+[a-zA-Z0-9]*)\s*=\s*(?P<packageKey>[a-zA-Z0-9\.]+)\s*$/';
    const SPLIT_PATTERN_OBJECTDEFINITION = '/
		^\s*                      # beginning of line; with numerous whitespace
		(?P<ObjectPath>           # begin ObjectPath

			\.?
			(?:
				@?[a-zA-Z0-9:_\-]+              # Unquoted key
				|"(?:\\\"|[^"])+"               # Double quoted key, supporting more characters like underscore and at sign
				|\'(?:\\\\\'|[^\'])+\'          # Single quoted key, supporting more characters like underscore and at sign
				|prototype\([a-zA-Z0-9.:]+\)    # Prototype definition
			)
			(?:
				\.
				(?:
					@?[a-zA-Z0-9:_\-]+              # Unquoted key
					|"(?:\\\"|[^"])+"               # Double quoted key, supporting more characters like underscore and at sign
					|\'(?:\\\\\'|[^\'])+\'          # Single quoted key, supporting more characters like underscore and at sign
					|prototype\([a-zA-Z0-9.:]+\)    # Prototype definition
				)
			)*
		)
		\s*
		(?P<Operator>             # the operators which are supported
			=|<|>
		)
		\s*
		(?P<Value>                # the remaining line inside the value
			.*?
		)
		\s*
		(?P<OpeningConfinement>
			(?<![${])\{           # optionally followed by an opening confinement
		)?
		\s*$
	/x';
    const SPLIT_PATTERN_VALUENUMBER = '/^\s*-?\d+\s*$/';
    const SPLIT_PATTERN_VALUEFLOATNUMBER = '/^\s*-?\d+(\.\d+)?\s*$/';
    const SPLIT_PATTERN_VALUELITERAL = '/^"((?:\\\\.|[^\\\\"])*)"|\'((?:\\\\.|[^\\\\\'])*)\'$/';
    const SPLIT_PATTERN_VALUEMULTILINELITERAL = '/
		^(
			(?P<DoubleQuoteChar>")
			(?P<DoubleQuoteValue>
				(?:\\\\.
				|
				[^\\\\"])*
			)
			|
			(?P<SingleQuoteChar>\')
			(?P<SingleQuoteValue>
				(?:\\\\.
				|
				[^\\\\\'])*
			)
		)$/x';
    const SPLIT_PATTERN_VALUEBOOLEAN = '/^\s*(TRUE|FALSE|true|false)\s*$/';
    const SPLIT_PATTERN_VALUENULL = '/^\s*(NULL|null)\s*$/';

    const SCAN_PATTERN_VALUEOBJECTTYPE = '/
		^\s*                      # beginning of line; with numerous whitespace
		(?:                       # non-capturing submatch containing the namespace followed by ":" (optional)
			(?P<namespace>
				[a-zA-Z0-9.]+     # namespace alias (cms, …) or fully qualified namespace (Neos.Neos, …)
			)
			:                     # : as delimiter
		)?
		(?P<unqualifiedType>
			[a-zA-Z0-9.]+         # the unqualified type
		)
		\s*$
	/x';

    /**
     * Reserved parse tree keys for internal usage.
     *
     * @var array
     */
    public static $reservedParseTreeKeys = array('__meta', '__prototypes', '__prototypeObjectName', '__prototypeChain', '__value', '__objectType', '__eelExpression');

    /**
     * @Flow\Inject
     * @var \Neos\Flow\ObjectManagement\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * The Fusion object tree, created by this parser.
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
    protected $currentBlockCommentState = false;

    /**
     * An optional context path which is used as a prefix for inclusion of further
     * Fusion files
     * @var string
     */
    protected $contextPathAndFilename = null;

    /**
     * Namespaces used for resolution of Fusion object names. These namespaces
     * are a mapping from a user defined key (alias) to a package key (the namespace).
     * By convention, the namespace should be a package key, but other strings would
     * be possible, too. Note that, in order to resolve an object type, a prototype
     * with that namespace and name must be defined elsewhere.
     *
     * These namespaces are _not_ used for resolution of processor class names.
     * @var array
     */
    protected $objectTypeNamespaces = array(
        'default' => 'Neos.Fusion'
    );

    /**
     * Parses the given Fusion source code and returns an object tree
     * as the result.
     *
     * @param string $sourceCode The Fusion source code to parse
     * @param string $contextPathAndFilename An optional path and filename to use as a prefix for inclusion of further Fusion files
     * @param array $objectTreeUntilNow Used internally for keeping track of the built object tree
     * @param boolean $buildPrototypeHierarchy Merge prototype configurations or not. Will be FALSE for includes to only do that once at the end.
     * @return array A Fusion object tree, generated from the source code
     * @throws Fusion\Exception
     * @api
     */
    public function parse($sourceCode, $contextPathAndFilename = null, array $objectTreeUntilNow = array(), $buildPrototypeHierarchy = true)
    {
        if (!is_string($sourceCode)) {
            throw new Fusion\Exception('Cannot parse Fusion - $sourceCode must be of type string!', 1180203775);
        }
        $this->initialize();
        $this->objectTree = $objectTreeUntilNow;
        $this->contextPathAndFilename = $contextPathAndFilename;
        $sourceCode = str_replace("\r\n", "\n", $sourceCode);
        $this->currentSourceCodeLines = explode(chr(10), $sourceCode);
        while (($typoScriptLine = $this->getNextTypoScriptLine()) !== false) {
            $this->parseTypoScriptLine($typoScriptLine);
        }

        if ($buildPrototypeHierarchy) {
            $this->buildPrototypeHierarchy();
        }
        return $this->objectTree;
    }

    /**
     * Sets the given alias to the specified namespace.
     *
     * The namespaces defined through this setter or through a "namespace" declaration
     * in one of the Fusions are used to resolve a fully qualified Fusion
     * object name while parsing Fusion code.
     *
     * The alias is the handle by wich the namespace can be referred to.
     * The namespace is, by convention, a package key which must correspond to a
     * namespace used in the prototype definitions for Fusion object types.
     *
     * The special alias "default" is used as a fallback for resolution of unqualified
     * Fusion object types.
     *
     * @param string $alias An alias for the given namespace, for example "neos"
     * @param string $namespace The namespace, for example "Neos.Neos"
     * @return void
     * @throws Fusion\Exception
     * @api
     */
    public function setObjectTypeNamespace($alias, $namespace)
    {
        if (!is_string($alias)) {
            throw new Fusion\Exception('The alias of a namespace must be valid string!', 1180600696);
        }
        if (!is_string($namespace)) {
            throw new Fusion\Exception('The namespace must be of type string!', 1180600697);
        }
        $this->objectTypeNamespaces[$alias] = $namespace;
    }

    /**
     * Initializes the Fusion parser
     *
     * @return void
     */
    protected function initialize()
    {
        $this->currentLineNumber = 1;
        $this->currentObjectPathStack = array();
        $this->currentSourceCodeLines = array();
        $this->currentBlockCommentState = false;
        $this->objectTree = array();
    }

    /**
     * Get the next, unparsed line of Fusion from this->currentSourceCodeLines and increase the pointer
     *
     * @return string next line of typoscript to parse
     */
    protected function getNextTypoScriptLine()
    {
        $typoScriptLine = current($this->currentSourceCodeLines);
        next($this->currentSourceCodeLines);
        $this->currentLineNumber++;
        return $typoScriptLine;
    }

    /**
     * Parses one line of Fusion
     *
     * @param string $typoScriptLine One line of Fusion code
     * @return void
     * @throws Fusion\Exception
     */
    protected function parseTypoScriptLine($typoScriptLine)
    {
        $typoScriptLine = trim($typoScriptLine);

        if ($this->currentBlockCommentState === true) {
            $this->parseComment($typoScriptLine);
        } else {
            if ($typoScriptLine === '') {
                return;
            } elseif (preg_match(self::SCAN_PATTERN_COMMENT, $typoScriptLine)) {
                $this->parseComment($typoScriptLine);
            } elseif (preg_match(self::SCAN_PATTERN_OPENINGCONFINEMENT, $typoScriptLine)) {
                $this->parseConfinementBlock($typoScriptLine, true);
            } elseif (preg_match(self::SCAN_PATTERN_CLOSINGCONFINEMENT, $typoScriptLine)) {
                $this->parseConfinementBlock($typoScriptLine, false);
            } elseif (preg_match(self::SCAN_PATTERN_DECLARATION, $typoScriptLine)) {
                $this->parseDeclaration($typoScriptLine);
            } elseif (preg_match(self::SCAN_PATTERN_OBJECTDEFINITION, $typoScriptLine)) {
                $this->parseObjectDefinition($typoScriptLine);
            } else {
                throw new Fusion\Exception('Syntax error in line ' . $this->currentLineNumber . '. (' . $typoScriptLine . ')', 1180547966);
            }
        }
    }

    /**
     * Parses a line with comments or a line while parsing is in block comment mode.
     *
     * @param string $typoScriptLine One line of Fusion code
     * @return void
     * @throws Fusion\Exception
     */
    protected function parseComment($typoScriptLine)
    {
        if (preg_match(self::SPLIT_PATTERN_COMMENTTYPE, $typoScriptLine, $matches, PREG_OFFSET_CAPTURE) === 1) {
            switch ($matches[1][0]) {
                case '/*':
                    $this->currentBlockCommentState = true;
                    break;
                case '*/':
                    if ($this->currentBlockCommentState !== true) {
                        throw new Fusion\Exception('Unexpected closing block comment without matching opening block comment.', 1180615119);
                    }
                    $this->currentBlockCommentState = false;
                    $this->parseTypoScriptLine(substr($typoScriptLine, ($matches[1][1] + 2)));
                    break;
                case '#':
                case '//':
                default:
                    break;
            }
        } elseif ($this->currentBlockCommentState === false) {
            throw new Fusion\Exception('No comment type matched although the comment scan regex matched the Fusion line (' . $typoScriptLine . ').', 1180614895);
        }
    }

    /**
     * Parses a line which opens or closes a confinement
     *
     * @param string $typoScriptLine One line of Fusion code
     * @param boolean $isOpeningConfinement Set to TRUE, if an opening confinement is to be parsed and FALSE if it's a closing confinement.
     * @return void
     * @throws Fusion\Exception
     */
    protected function parseConfinementBlock($typoScriptLine, $isOpeningConfinement)
    {
        if ($isOpeningConfinement) {
            $result = trim(trim(trim($typoScriptLine), '{'));
            array_push($this->currentObjectPathStack, $this->getCurrentObjectPathPrefix() . $result);
        } else {
            if (count($this->currentObjectPathStack) < 1) {
                throw new Fusion\Exception('Unexpected closing confinement without matching opening confinement. Check the number of your curly braces.', 1181575973);
            }
            array_pop($this->currentObjectPathStack);
        }
    }

    /**
     * Parses a parser declaration of the form "declarationtype: declaration".
     *
     * @param string $typoScriptLine One line of Fusion code
     * @return void
     * @throws Fusion\Exception
     */
    protected function parseDeclaration($typoScriptLine)
    {
        $result = preg_match(self::SPLIT_PATTERN_DECLARATION, $typoScriptLine, $matches);
        if ($result !== 1 || !(isset($matches['declarationType']) && isset($matches['declaration']))) {
            throw new Fusion\Exception('Invalid declaration "' . $typoScriptLine . '"', 1180544656);
        }

        switch ($matches['declarationType']) {
            case 'namespace':
                $this->parseNamespaceDeclaration($matches['declaration']);
                break;
            case 'include':
                $this->parseInclude($matches['declaration']);
                break;
        }
    }

    /**
     * Parses an object definition.
     *
     * @param string $typoScriptLine One line of Fusion code
     * @return void
     * @throws Fusion\Exception
     */
    protected function parseObjectDefinition($typoScriptLine)
    {
        $result = preg_match(self::SPLIT_PATTERN_OBJECTDEFINITION, $typoScriptLine, $matches);
        if ($result !== 1) {
            throw new Fusion\Exception('Invalid object definition "' . $typoScriptLine . '"', 1180548488);
        }

        $objectPath = $this->getCurrentObjectPathPrefix() . $matches['ObjectPath'];
        switch ($matches['Operator']) {
            case '=':
                $this->parseValueAssignment($objectPath, $matches['Value']);
                break;
            case '>':
                $this->parseValueUnAssignment($objectPath);
                break;
            case '<':
                $this->parseValueCopy($matches['Value'], $objectPath);
                break;
        }

        if (isset($matches['OpeningConfinement'])) {
            $this->parseConfinementBlock($matches['ObjectPath'], true);
        }
    }

    /**
     * Parses a value operation of the type "assignment".
     *
     * @param string $objectPath The object path as a string
     * @param string $value The unparsed value as a string
     * @return void
     */
    protected function parseValueAssignment($objectPath, $value)
    {
        $processedValue = $this->getProcessedValue($value);
        $this->setValueInObjectTree($this->getParsedObjectPath($objectPath), $processedValue);
    }

    /**
     * Unsets the object, property or variable specified by the object path.
     *
     * @param string $objectPath The object path as a string
     * @return void
     */
    protected function parseValueUnAssignment($objectPath)
    {
        $objectPathArray = $this->getParsedObjectPath($objectPath);
        $this->setValueInObjectTree($objectPathArray, null);
    }

    /**
     * Copies the object or value specified by sourcObjectPath and assigns
     * it to targetObjectPath.
     *
     * @param string $sourceObjectPath Specifies the location in the object tree from where the object or value will be taken
     * @param string $targetObjectPath Specifies the location in the object tree where the copy will be stored
     * @return void
     * @throws Fusion\Exception
     */
    protected function parseValueCopy($sourceObjectPath, $targetObjectPath)
    {
        $sourceObjectPathArray = $this->getParsedObjectPath($sourceObjectPath);
        $targetObjectPathArray = $this->getParsedObjectPath($targetObjectPath);

        $sourceIsPrototypeDefinition = (count($sourceObjectPathArray) >= 2 && $sourceObjectPathArray[count($sourceObjectPathArray) - 2] === '__prototypes');
        $targetIsPrototypeDefinition = (count($targetObjectPathArray) >= 2 && $targetObjectPathArray[count($targetObjectPathArray) - 2] === '__prototypes');

        if ($sourceIsPrototypeDefinition || $targetIsPrototypeDefinition) {
            // either source or target are a prototype definition
            if ($sourceIsPrototypeDefinition && $targetIsPrototypeDefinition && count($sourceObjectPathArray) === 2 && count($targetObjectPathArray) === 2) {
                // both are a prototype definition and the path has length 2: this means
                    // it must be of the form "prototype(Foo) < prototype(Bar)"
                $targetObjectPathArray[] = '__prototypeObjectName';
                $this->setValueInObjectTree($targetObjectPathArray, end($sourceObjectPathArray));
            } elseif ($sourceIsPrototypeDefinition && $targetIsPrototypeDefinition) {
                // Both are prototype definitions, but at least one is nested (f.e. foo.prototype(Bar))
                    // Currently, it is not supported to override the prototypical inheritance in
                    // parts of the TS rendering tree.
                    // Although this might work conceptually, it makes reasoning about the prototypical
                    // inheritance tree a lot more complex; that's why we forbid it right away.
                throw new Fusion\Exception('Tried to parse "' . $targetObjectPath . '" < "' . $sourceObjectPath . '", however one of the sides is nested (e.g. foo.prototype(Bar)). Setting up prototype inheritance is only supported at the top level: prototype(Foo) < prototype(Bar)', 1358418019);
            } else {
                // Either "source" or "target" are no prototypes. We do not support copying a
                    // non-prototype value to a prototype value or vice-versa.
                throw new Fusion\Exception('Tried to parse "' . $targetObjectPath . '" < "' . $sourceObjectPath . '", however one of the sides is no prototype definition of the form prototype(Foo). It is only allowed to build inheritance chains with prototype objects.', 1358418015);
            }
        } else {
            $originalValue = $this->getValueFromObjectTree($sourceObjectPathArray);
            $value = is_object($originalValue) ? clone $originalValue : $originalValue;

            $this->setValueInObjectTree($targetObjectPathArray, $value);
        }
    }

    /**
     * Parses a namespace declaration and stores the result in the namespace registry.
     *
     * @param string $namespaceDeclaration The namespace declaration, for example "neos = Neos.Neos"
     * @return void
     * @throws Fusion\Exception
     */
    protected function parseNamespaceDeclaration($namespaceDeclaration)
    {
        $result = preg_match(self::SPLIT_PATTERN_NAMESPACEDECLARATION, $namespaceDeclaration, $matches);
        if ($result !== 1 || !(isset($matches['alias']) && isset($matches['packageKey']))) {
            throw new Fusion\Exception('Invalid namespace declaration "' . $namespaceDeclaration . '"', 1180547190);
        }

        $namespaceAlias = $matches['alias'];
        $namespacePackageKey = $matches['packageKey'];
        $this->objectTypeNamespaces[$namespaceAlias] = $namespacePackageKey;
    }

    /**
     * Parse an include file. Currently, we start a new parser object; but we could as well re-use
     * the given one.
     *
     * @param string $include The include value, for example " FooBar" or " resource://....". Can also include wildcard mask for Fusion globbing.
     * @return void
     * @throws Fusion\Exception
     */
    protected function parseInclude($include)
    {
        $include = trim($include);
        $parser = new Parser();
        // transfer current namespaces to new parser
        foreach ($this->objectTypeNamespaces as $key => $objectTypeNamespace) {
            $parser->setObjectTypeNamespace($key, $objectTypeNamespace);
        }

        if (strpos($include, 'resource://') !== 0) {
            // Resolve relative paths
            if ($this->contextPathAndFilename !== null) {
                $include = dirname($this->contextPathAndFilename) . '/' . $include;
            } else {
                throw new Fusion\Exception('Relative file inclusions are only possible if a context path and filename has been passed as second argument to parse()', 1329806940);
            }
        }

        // Match recursive wildcard globbing "**/*"
        if (preg_match('#([^\*]*)\*\*/\*#', $include, $matches) === 1) {
            $basePath = $matches['1'];
            if (!is_dir($basePath)) {
                throw new Fusion\Exception(sprintf('The path %s does not point to a directory.', $basePath), 1415033179);
            }
            $recursiveDirectoryIterator = new \RecursiveDirectoryIterator($basePath);
            $iterator = new \RecursiveIteratorIterator($recursiveDirectoryIterator);
        // Match simple wildcard globbing "*"
        } elseif (preg_match('#([^\*]*)\*#', $include, $matches) === 1) {
            $basePath = $matches['1'];
            if (!is_dir($basePath)) {
                throw new Fusion\Exception(sprintf('The path %s does not point to a directory.', $basePath), 1415033180);
            }
            $iterator = new \DirectoryIterator($basePath);
        }
        // If iterator is set it means we're doing globbing
        if (isset($iterator)) {
            foreach ($iterator as $fileInfo) {
                $pathAndFilename = $fileInfo->getPathname();
                // Only work on .fusion files and .ts2 files. The support for .ts2 is deprecated a fallback and will be dropped with 4.0
                if ($fileInfo->getExtension() === 'fusion' || $fileInfo->getExtension() === 'ts2') {
                    // Check if not trying to recursively include the current file via globbing
                    if (stat($pathAndFilename) !== stat($this->contextPathAndFilename)) {
                        if (!is_readable($pathAndFilename)) {
                            throw new Fusion\Exception(sprintf('Could not include Fusion file "%s"', $pathAndFilename), 1347977018);
                        }
                        $this->objectTree = $parser->parse(file_get_contents($pathAndFilename), $pathAndFilename, $this->objectTree, false);
                    }
                }
            }
        } else {
            if (!is_readable($include)) {
                throw new Fusion\Exception(sprintf('Could not include Fusion file "%s"', $include), 1347977017);
            }
            $this->objectTree = $parser->parse(file_get_contents($include), $include, $this->objectTree, false);
        }
    }

    /**
     * Parse an object path specified as a string and returns an array.
     *
     * @param string $objectPath The object path to parse
     * @return array An object path array
     * @throws Fusion\Exception
     */
    protected function getParsedObjectPath($objectPath)
    {
        if (preg_match(self::SCAN_PATTERN_OBJECTPATH, $objectPath) === 1) {
            if ($objectPath[0] === '.') {
                $objectPath = $this->getCurrentObjectPathPrefix() . substr($objectPath, 1);
            }

            $objectPathArray = array();
            foreach (preg_split(self::SPLIT_PATTERN_OBJECTPATH, $objectPath) as $objectPathSegment) {
                if ($objectPathSegment[0] === '@') {
                    $objectPathArray[] = '__meta';
                    $metaProperty = substr($objectPathSegment, 1);
                    if ($metaProperty === 'override') {
                        $metaProperty = 'context';
                    }
                    $objectPathArray[] = $metaProperty;
                } elseif (preg_match(self::SCAN_PATTERN_OBJECTPATHSEGMENT_IS_PROTOTYPE, $objectPathSegment)) {
                    $objectPathArray[] = '__prototypes';

                    $unexpandedObjectType = substr($objectPathSegment, 10, -1);
                    $objectTypeParts = explode(':', $unexpandedObjectType);
                    if (!isset($objectTypeParts[1])) {
                        $fullyQualifiedObjectType = $this->objectTypeNamespaces['default'] . ':' . $objectTypeParts[0];
                    } elseif (isset($this->objectTypeNamespaces[$objectTypeParts[0]])) {
                        $fullyQualifiedObjectType = $this->objectTypeNamespaces[$objectTypeParts[0]] . ':' . $objectTypeParts[1];
                    } else {
                        $fullyQualifiedObjectType = $unexpandedObjectType;
                    }
                    $objectPathArray[] = $fullyQualifiedObjectType;
                } else {
                    $key = $objectPathSegment;
                    if (substr($key, 0, 2) === '__' && in_array($key, self::$reservedParseTreeKeys, true)) {
                        throw new Fusion\Exception(sprintf('Reversed key "%s" used in object path "%s".', $key, $objectPath), 1437065270);
                    }
                    $objectPathArray[] = $this->unquoteString($key);
                }
            }
        } else {
            throw new Fusion\Exception('Syntax error: Invalid object path "' . $objectPath . '".', 1180603499);
        }

        return $objectPathArray;
    }

    /**
     * Parses the given value (which may be a literal, variable or object type) and
     * returns the evaluated result, including variables replaced by their actual value.
     *
     * @param string $unparsedValue The unparsed value
     * @return mixed The processed value
     * @throws Fusion\Exception
     */
    protected function getProcessedValue($unparsedValue)
    {
        if (preg_match(self::SPLIT_PATTERN_VALUENUMBER, $unparsedValue, $matches) === 1) {
            $processedValue = intval($unparsedValue);
        } elseif (preg_match(self::SPLIT_PATTERN_VALUEFLOATNUMBER, $unparsedValue, $matches) === 1) {
            $processedValue = floatval($unparsedValue);
        } elseif (preg_match(Package::EelExpressionRecognizer, $unparsedValue, $matches) === 1) {
            // Single-line Eel Expressions
            $processedValue = array(
                '__eelExpression' => $matches[1],
                '__value' => null,
                '__objectType' => null
            );
        } elseif (preg_match(self::SPLIT_PATTERN_VALUELITERAL, $unparsedValue, $matches) === 1) {
            $processedValue = stripslashes(isset($matches[2]) ? $matches[2] : $matches[1]);
        } elseif (preg_match(self::SPLIT_PATTERN_VALUEMULTILINELITERAL, $unparsedValue, $matches) === 1) {
            $processedValue = stripslashes(isset($matches['SingleQuoteValue']) ? $matches['SingleQuoteValue'] : $matches['DoubleQuoteValue']);
            $closingQuoteChar = isset($matches['SingleQuoteChar']) ? $matches['SingleQuoteChar'] : $matches['DoubleQuoteChar'];
            $regexp = '/(?P<Value>(?:\\\\.|[^\\\\' . $closingQuoteChar . '])*)(?P<QuoteChar>' . $closingQuoteChar . '?)/';
            while (($typoScriptLine = $this->getNextTypoScriptLine()) !== false) {
                preg_match($regexp, $typoScriptLine, $matches);
                $processedValue .= "\n" . stripslashes($matches['Value']);
                if (!empty($matches['QuoteChar'])) {
                    break;
                }
            }
        } elseif (preg_match(self::SPLIT_PATTERN_VALUEBOOLEAN, $unparsedValue, $matches) === 1) {
            $processedValue = (strtolower($matches[1]) === 'true');
        } elseif (preg_match(self::SPLIT_PATTERN_VALUENULL, $unparsedValue, $matches) === 1) {
            $processedValue = null;
        } elseif (preg_match(self::SCAN_PATTERN_VALUEOBJECTTYPE, $unparsedValue, $matches) === 1) {
            if (empty($matches['namespace'])) {
                $objectTypeNamespace = $this->objectTypeNamespaces['default'];
            } else {
                $objectTypeNamespace = (isset($this->objectTypeNamespaces[$matches['namespace']])) ? $this->objectTypeNamespaces[$matches['namespace']] : $matches['namespace'];
            }
            $processedValue = array(
                '__objectType' => $objectTypeNamespace . ':' . $matches['unqualifiedType'],
                '__value' => null,
                '__eelExpression' => null
            );
        } else {
            // Trying to match multiline Eel expressions
            if (strpos($unparsedValue, '${') === 0) {
                $eelExpressionSoFar = $unparsedValue;
                // potential start of multiline Eel Expression; trying to consume next lines...
                while (($line = $this->getNextTypoScriptLine()) !== false) {
                    $eelExpressionSoFar .= chr(10) . $line;

                    if (substr($line, -1) === '}') {
                        // potential end-of-eel-expression marker
                        $matches = array();
                        if (preg_match(Package::EelExpressionRecognizer, $eelExpressionSoFar, $matches) === 1) {
                            // Single-line Eel Expressions
                            $processedValue = array('__eelExpression' => str_replace(chr(10), '', $matches[1]), '__value' => null, '__objectType' => null);
                            break;
                        }
                    }
                }

                if ($line === false) {
                    // if the last line we consumed is FALSE, we have consumed the end of the file.
                    throw new Fusion\Exception('Syntax error: A multi-line Eel expression starting with "' . $unparsedValue . '" was not closed.', 1417616064);
                }
            } else {
                throw new Fusion\Exception('Syntax error: Invalid value "' . $unparsedValue . '" in value assignment.', 1180604192);
            }
        }
        return $processedValue;
    }

    /**
     * Assigns a value to a node or a property in the object tree, specified by the object path array.
     *
     * @param array $objectPathArray The object path, specifying the node / property to set
     * @param mixed $value The value to assign, is a non-array type or an array with __eelExpression etc.
     * @param array $objectTree The current (sub-) tree, used internally - don't specify!
     * @return array The modified object tree
     */
    protected function setValueInObjectTree(array $objectPathArray, $value, &$objectTree = null)
    {
        if ($objectTree === null) {
            $objectTree = &$this->objectTree;
        }

        $currentKey = array_shift($objectPathArray);
        if ((integer)$currentKey > 0) {
            $currentKey = (integer)$currentKey;
        }

        if (empty($objectPathArray)) {
            // last part of the iteration, setting the final value
            if (isset($objectTree[$currentKey]) && $value === null) {
                unset($objectTree[$currentKey]);
            } elseif (isset($objectTree[$currentKey]) && is_array($objectTree[$currentKey])) {
                if (is_array($value)) {
                    $objectTree[$currentKey] = Arrays::arrayMergeRecursiveOverrule($objectTree[$currentKey], $value);
                } else {
                    $objectTree[$currentKey]['__value'] = $value;
                    $objectTree[$currentKey]['__eelExpression'] = null;
                    $objectTree[$currentKey]['__objectType'] = null;
                }
            } else {
                $objectTree[$currentKey] = $value;
            }
        } else {
            // we still need to traverse further down
            if (isset($objectTree[$currentKey]) && !is_array($objectTree[$currentKey])) {
                // the element one-level-down is already defined, but it is NOT an array. So we need to convert the simple type to __value
                $objectTree[$currentKey] = array(
                    '__value' => $objectTree[$currentKey],
                    '__eelExpression' => null,
                    '__objectType' => null
                );
            } elseif (!isset($objectTree[$currentKey])) {
                $objectTree[$currentKey] = array();
            }

            $this->setValueInObjectTree($objectPathArray, $value, $objectTree[$currentKey]);
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
    protected function &getValueFromObjectTree(array $objectPathArray, &$objectTree = null)
    {
        if (is_null($objectTree)) {
            $objectTree = &$this->objectTree;
        }

        if (count($objectPathArray) > 0) {
            $currentKey = array_shift($objectPathArray);
            if ((integer)$currentKey > 0) {
                $currentKey = intval($currentKey);
            }
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
     * Returns the first part of an object path from the current object path stack
     * which can be used to prefix a relative object path.
     *
     * @return string A part of an object path, ready to use as a prefix
     */
    protected function getCurrentObjectPathPrefix()
    {
        $lastElementOfStack = end($this->currentObjectPathStack);
        return ($lastElementOfStack !== false) ? $lastElementOfStack . '.' : '';
    }

    /**
     * Precalculate merged configuration for inherited prototypes.
     *
     * @return void
     * @throws Fusion\Exception
     */
    protected function buildPrototypeHierarchy()
    {
        if (!isset($this->objectTree['__prototypes'])) {
            return;
        }

        foreach ($this->objectTree['__prototypes'] as $prototypeName => $prototypeConfiguration) {
            $prototypeInheritanceHierarchy = array();
            $currentPrototypeName = $prototypeName;
            while (isset($this->objectTree['__prototypes'][$currentPrototypeName]['__prototypeObjectName'])) {
                $currentPrototypeName = $this->objectTree['__prototypes'][$currentPrototypeName]['__prototypeObjectName'];
                array_unshift($prototypeInheritanceHierarchy, $currentPrototypeName);
                if ($prototypeName === $currentPrototypeName) {
                    throw new Fusion\Exception(sprintf('Recursive inheritance found for prototype "%s". Prototype chain: %s', $prototypeName, implode(' < ', array_reverse($prototypeInheritanceHierarchy))), 1492801503);
                }
            }

            if (count($prototypeInheritanceHierarchy)) {
                // prototype chain from most *general* to most *specific* WITHOUT the current node type!
                $this->objectTree['__prototypes'][$prototypeName]['__prototypeChain'] = $prototypeInheritanceHierarchy;
            }
        }
    }

    /**
     * Removes escapings from a given argument string and trims the outermost
     * quotes.
     *
     * This method is meant as a helper for regular expression results.
     *
     * @param string $quotedValue Value to unquote
     * @return string Unquoted value
     */
    protected function unquoteString($quotedValue)
    {
        switch ($quotedValue[0]) {
            case '"':
                $value = str_replace('\\"', '"', preg_replace('/(^"|"$)/', '', $quotedValue));
                break;
            case "'":
                $value = str_replace("\\'", "'", preg_replace('/(^\'|\'$)/', '', $quotedValue));
                break;
            default:
                $value = $quotedValue;
        }
        return str_replace('\\\\', '\\', $value);
    }
}
