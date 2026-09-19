<?php
namespace Neos\Flow\Core\Migrations;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Fusion\Migrations\FusionMigrationTrait;

/**
 * Adjust EEL in Fusion code to the new Neos 9 API
 *
 * The context variables ${node}, documentNode and site continue to exist in Fusion but there are changes to their API in Fusion.
 *
 * While most of the FlowQueries work as before, there are some adjustments that come with the new concepts that are introduced in Neos 9.
 *
 * The most important changes are:
 *
 * - Accessing properties of the node context via node.context.is no longer supported.
 *   And modifying the node context via flowQuery q(node).context() is only partially supported.
 *   The rendering mode (node.context.inBackend) is now moved to a separate variable that is independent of the node context.
 * - Internal properties like _hidden and _name are no longer in use.
 * - Cache Entry Identifiers are now a dedicated object and not any value.
 *
 * There are some adjustments with a caveat as they don't reflect the 8.3 behaviour 100%.
 *
 * A few examples are:
 *
 * - `node.nodeType` always returned a NodeType and when removed the `Neos.Neos:FallbackNode`. In Neos 9.0 there exists no magic for the `Neos.Neos:FallbackNode` and thus the helper `Neos.Node.nodeType(node)` returns "NULL".
 * - `node.context.currentRenderingMode` always returns the rendering mode based on the logged-in user - so when viewing the page logged in in the frontend the mode is still 'inPlaces' as in the backend -> the new `renderingMode` reports "frontend" as expected for this case
 * - the "live" rendering mode was renamed to "frontend" so the unlikely case of `node.context.currentRenderingMode == "live"` fails when migrated to `renderingMode.name`
 * - `node.context.currentSite` is rewritten to `Neos.Site.findBySiteNode(site)` which makes the assumption of "site" being present and that "currentSite" is actually the current and was not tampered with
 * - unlike `node.identifier` the `node.aggregateId` is now a value object in fusion, it is string-able and can be output directly but no direct strict comparison must be done `node.aggregateId == "some-id"` will not work. It has to be cast to string `String.toString(node.aggregateId)`.
 *
 */
class Version20251005080230 extends AbstractMigration
{
    use FusionMigrationTrait;

    public function getIdentifier(): string
    {
        return 'Neos.Neos-20251005080230';
    }

    final public function fusionFlowQueryNodePropertyToWarningComment(string $property, string $warningMessage): void
    {
        $property = preg_quote($property);

        $this->addCommentsIfRegexMatches(
            "/\.property\(('|\")$property('|\")\)/",
            $warningMessage
        );
    }

    final public function fusionNodePropertyPathToWarningComment(string $propertyPath, string $warningMessage): void
    {
        $propertyPath = preg_quote($propertyPath);

        $this->addCommentsIfRegexMatches(
            "/(node|site|documentNode)\.$propertyPath/",
            $warningMessage
        );
    }

    public function up(): void
    {
        /**
         * Neos\ContentRepository\Domain\Model\NodeInterface
         */
        // getName
        $this->fusionFlowQueryNodePropertyToWarningComment('_name', 'Line %LINE: !! You very likely need to rewrite "q(VARIABLE).property("_name")" to "VARIABLE.nodeName". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        // getLabel
        // Rewrite "node.label" and "q(node).property('_label')" to "Neos.Node.label(node)"
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.label/', 'Neos.Node.label($1)');
        $this->addCommentsIfRegexMatches('/(?<!props)\.label\b(?!\()/', 'Line %LINE: You very likely need to rewrite "VARIABLE.label" to "Neos.Node.label(VARIABLE)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        $this->replaceEelExpression('/q\(([^)]+)\)\.property\\([\'"]_label[\'"]\\)/', 'Neos.Node.label($1)');
        // getProperties -> PropertyCollectionInterface
        // getPropertyNames
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.propertyNames/', 'Array.keys($1.properties)');
        $this->addCommentsIfRegexMatches('/\.propertyNames/', 'Line %LINE: !! You very likely need to rewrite "VARIABLE.propertyNames" to "Array.keys(VARIABLE.properties)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        $this->fusionFlowQueryNodePropertyToWarningComment('_propertyNames', 'Line %LINE: !! You very likely need to rewrite "q(VARIABLE).property("_propertyNames")" to "Array.keys(VARIABLE.properties)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        // getContentObject -> DEPRECATED / NON-FUNCTIONAL
        // getNodeType: NodeType
        // Rewrite "node.nodeType" and "q(node).property('_nodeType')" to "Neos.Node.nodeType(node)"
        // Fusion: node.nodeType -> Neos.Node.nodeType(node)
        // Fusion: node.nodeType.name -> node.nodeTypeName
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.nodeType\.name/', '$1.nodeTypeName');
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.nodeType\b/', 'Neos.Node.nodeType($1)');
        $this->addCommentsIfRegexMatches('/\.nodeType\b(?!\()/', 'Line %LINE: You very likely need to rewrite "VARIABLE.nodeType" to "Neos.Node.nodeType(VARIABLE)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        $this->addCommentsIfRegexMatches('/\.nodeType.name/', 'Line %LINE: You may need to rewrite "VARIABLE.nodeType.name" to "VARIABLE.nodeTypeName". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        $this->replaceEelExpression('/q\(([^)]+)\)\.property\\([\'"]_nodeType\.name[\'"]\\)/', '$1.nodeTypeName');
        $this->replaceEelExpression('/q\(([^)]+)\)\.property\\([\'"]_nodeType(\.[^\'"]*)?[\'"]\\)/', 'Neos.Node.nodeType($1)$2');
        // isHidden
        // Rewrite node.hidden and q(node).property("_hidden") to Neos.Node.isDisabled(node)
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.hidden\b(?!\.|\()/', 'Neos.Node.isDisabled($1)');
        $this->addCommentsIfRegexMatches('/\.hidden\b(?!\.|\()/', 'Line %LINE: You may need to rewrite "VARIABLE.hidden" to Neos.Node.isDisabled(VARIABLE). We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        $this->replaceEelExpression('/q\(([^)]+)\)\.property\([\'"]_hidden[\'"]\)/', 'Neos.Node.isDisabled($1)');
        $this->fusionFlowQueryNodePropertyToWarningComment('_hidden', 'Line %LINE: You may need to rewrite "q(VARIABLE).property(\'_hidden\')" to Neos.Node.isDisabled(VARIABLE). We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        // getHiddenBeforeDateTime
        // Rewrite node.hiddenBeforeDateTime to q(node).property("enableAfterDateTime")'
        $this->replaceEelExpression('/(node|documentNode)\.hiddenBeforeDateTime/', 'q($1).property("enableAfterDateTime")');
        $this->replaceEelExpression('/.property\(["\']_hiddenBeforeDateTime["\']\)/', '.property("enableAfterDateTime")');
        $this->addCommentsIfRegexMatches('/\.hiddenBeforeDateTime/', 'Line %LINE: You may need to rewrite "VARIABLE.hiddenBeforeDateTime" to q(VARIABLE).property("enableAfterDateTime"). We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        // getHiddenAfterDateTime
        // Rewrite node.hiddenAfterDateTime to q(node).property("disableAfterDateTime")
        $this->replaceEelExpression('/(node|documentNode)\.hiddenAfterDateTime/', 'q($1).property("disableAfterDateTime")');
        $this->replaceEelExpression('/.property\(["\']_hiddenAfterDateTime["\']\)/', '.property("disableAfterDateTime")');
        $this->addCommentsIfRegexMatches('/\.hiddenAfterDateTime/', 'Line %LINE: You may need to rewrite "VARIABLE.hiddenAfterDateTime" to q(VARIABLE).property("disableAfterDateTime"). We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        // isHiddenInIndex
        // Fusion: .hiddenInIndex -> node.properties._hiddenInIndex
        // Rewrite node.hiddenInIndex and q(node).property("_hiddenInIndex") to node.property('hiddenInMenu')
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.hiddenInIndex\b(?!\.|\()/', '$1.property(\'hiddenInMenu\')');
        $this->addCommentsIfRegexMatches('/\.hiddenInIndex\b(?!\.|\()/', 'Line %LINE: You may need to rewrite "VARIABLE.hiddenInIndex" to VARIABLE.property(\'hiddenInMenu\'). We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        $this->replaceEelExpression('/\.property\([\'"]_hiddenInIndex[\'"]\)/', '.property(\'hiddenInMenu\')');
        $this->fusionFlowQueryNodePropertyToWarningComment('_hiddenInIndex', 'Line %LINE: !! You very likely need to rewrite "q(VARIABLE).property("_hiddenInIndex")" to "VARIABLE.property(\'hiddenInMenu\')". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        // getAccessRoles DEPRECATED
        // getPath
        // Rewrite node.path and q(node).property("_path") to Neos.Node.path(node)
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.path\b(?!\.|\()/', 'Neos.Node.path($1)');
        $this->addCommentsIfRegexMatches('/\.path\b(?!\.|\()/', 'Line %LINE: You may need to rewrite "VARIABLE.path" to Neos.Node.path(VARIABLE). We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        $this->replaceEelExpression('/q\(([^)]+)\)\.property\([\'"]_path[\'"]\)/', 'Neos.Node.path($1)');
        $this->fusionFlowQueryNodePropertyToWarningComment('_path', 'Line %LINE: !! You very likely need to rewrite "q(VARIABLE).property("_path")" to "Neos.Node.path(VARIABLE)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        // getContextPath
        // Rewrite node.contextPath to Neos.Node.serializedNodeAddress(node)
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.contextPath\b(?!\.|\()/', 'Neos.Node.serializedNodeAddress($1)');
        $this->addCommentsIfRegexMatches('/\.contextPath\b(?!\.|\()/', 'Line %LINE: !! You very likely need to rewrite "VARIABLE.contextPath" to "Neos.Node.serializedNodeAddress(VARIABLE)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        $this->replaceEelExpression('/q\(([^)]+)\)\.property\([\'"]_contextPath[\'"]\)/', 'Neos.Node.serializedNodeAddress($1)');
        $this->fusionFlowQueryNodePropertyToWarningComment('_contextPath', 'Line %LINE: !! You very likely need to rewrite "q(VARIABLE).property("_contextPath")" to "Neos.Node.serializedNodeAddress(VARIABLE)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        // getDepth
        // Rewrite node.depth and q(node).property("_depth") to Neos.Node.depth(node)
        $this->replaceEelExpression('/(?<!\.)(site|node|documentNode)\.depth\b(?!\.|\()/', 'Neos.Node.depth($1)');
        $this->addCommentsIfRegexMatches('/\.depth\b(?!\.|\()/', 'Line %LINE: You may need to rewrite "VARIABLE.depth" to Neos.Node.depth(VARIABLE). We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        $this->replaceEelExpression('/q\(([^)]+)\)\.property\([\'"]_depth[\'"]\)/', 'Neos.Node.depth($1)');
        $this->fusionFlowQueryNodePropertyToWarningComment('_depth', 'Line %LINE: !! You very likely need to rewrite "q(VARIABLE).property("_depth")" to "Neos.Node.depth(VARIABLE)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        // getWorkspace
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.workspace\.name/', '$1.workspaceName');
        $this->replaceEelExpression('/q\(([^)]+)\)\.property\\([\'"]_workspace\.name[\'"]\\)/', '$1.workspaceName');
        $this->addCommentsIfRegexMatches('/(?<!context)\.workspace\b(?!\()/', 'Line %LINE: You very likely need to rewrite "VARIABLE.workspace" as the "workspace" of nodes is not accessible this way and the object contains less information which is split up to the WorkspaceMetadata. If you really need the workspace in fusion you need to create a dedicated helper yourself which should ideally do ALL the complex logic in php directly and return the computed result.');
        $this->fusionFlowQueryNodePropertyToWarningComment('_workspace', 'Line %LINE: You very likely need to rewrite "VARIABLE.workspace" as the "workspace" of nodes is not accessible this way and the object contains less information which is split up to the WorkspaceMetadata. If you really need the workspace in fusion you need to create a dedicated helper yourself which should ideally do ALL the complex logic in php directly and return the computed result.');
        // getIdentifier
        // Rewrite "node.identifier" and "q(node).property('_identifier')" to "node.aggregateId"
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.identifier/', '$1.aggregateId');
        $this->addCommentsIfRegexMatches('/\.identifier/', 'Line %LINE: You may need to rewrite "VARIABLE.identifier" to "VARIABLE.aggregateId". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        $this->replaceEelExpression('/q\(([^)]+)\)\.property\([\'"]_identifier[\'"]\)/', '$1.aggregateId');
        $this->fusionFlowQueryNodePropertyToWarningComment('_identifier', 'Line %LINE: !! You very likely need to rewrite "q(VARIABLE).property("_identifier")" to "VARIABLE.aggregateId". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        // getIndex
        $this->fusionFlowQueryNodePropertyToWarningComment('_index', 'Line %LINE: !! You very likely need to rewrite "q(VARIABLE).property("_index")". You can fetch all siblings and inspect the ordering.');
        // getParent -> Node
        // Rewrite node.parent to q(node).parent().get(0)
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.parent/', 'q($1).parent().get(0)');
        $this->addCommentsIfRegexMatches('/\.parent($|[^a-z(])/i', 'Line %LINE: You may need to rewrite "VARIABLE.parent" to "q(VARIABLE).parent().get(0)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        $this->fusionFlowQueryNodePropertyToWarningComment('_parent', 'Line %LINE: !! You very likely need to rewrite "q(VARIABLE).property("_parent")" to "q(VARIABLE).parent().get(0)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        // getParentPath - deprecated
        // getPrimaryChildNode() - deprecated
        // isRemoved()
        $this->fusionNodePropertyPathToWarningComment('removed', 'Line %LINE: !! node.removed - the new CR does not return removed nodes unless the visibility constraints are loosed manually.');
        // isVisible()
        $this->fusionNodePropertyPathToWarningComment('visible', 'Line %LINE: !! node.visible was removed in the new CR. Please use Neos.Node.isDisabled(VARIABLE) instead in combination with the Neos.TimeableNodeVisibility package to enable support for timed content.');
        // isAccessible() - deprecated
        // hasAccessRestrictions() - deprecated
        // getNodeData() - internal
        $this->fusionNodePropertyPathToWarningComment('nodeData', 'Line %LINE: !! node.nodeDate is internal and was removed in the new CR please operate on the node instead.');
        // getContext()
        $this->addCommentsIfRegexMatches('/\.context\b(?![(.])/', 'Line %LINE: !! node.context is removed in Neos 9.0 and cannot be passed around. In Neos 9.0 you likely want to pass the NodeAddress, the Node around or Subgraph around');
        $this->fusionNodePropertyPathToWarningComment('dimensions', 'Line %LINE: !! node.dimensions is removed in Neos 9.0. You can get node DimensionSpacePoints via node.dimensionSpacePoints now or use the `Neos.Dimension.*` helper.');
        // isAutoCreated()
        // Rewrite node.autoCreated to node.classification.tethered
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.autoCreated/', '$1.classification.tethered');
        $this->addCommentsIfRegexMatches('/\.autoCreated/', 'Line %LINE: !! You very likely need to rewrite "VARIABLE.autoCreated" to "VARIABLE.classification.tethered". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        $this->fusionFlowQueryNodePropertyToWarningComment('_autoCreated', 'Line %LINE: !! You very likely need to rewrite "q(VARIABLE).property("_autoCreated")" to "VARIABLE.classification.tethered". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');

        // getOtherNodeVariants()
        $this->addCommentsIfRegexMatches('/\.otherNodeVariants/', 'Line %LINE: !! "node.otherNodeVariants" was removed. Please use a custom EEL Helper and leverage the ContentGraph and NodeAggregate to work cross dimensional.');

        /**
         * Neos\ContentRepository\Domain\Projection\Content\NodeInterface
         */
        // isRoot() - the root node is usually never available in fusion
        // isTethered()
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.tethered/', '$1.classification.tethered');
        $this->addCommentsIfRegexMatches('/(?<!classification)\.tethered/', 'Line %LINE: !! You very likely need to rewrite "VARIABLE.tethered" to "VARIABLE.classification.tethered". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        $this->fusionFlowQueryNodePropertyToWarningComment('_tethered', 'Line %LINE: !! You very likely need to rewrite "q(VARIABLE).property("_tethered")" to "VARIABLE.classification.tethered". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        // getContentStreamIdentifier() -> threw exception in <= Neos 8.0 - so nobody could have used this
        // getNodeAggregateIdentifier()
        // Rewrite node.nodeAggregateIdentifier to node.aggregateId
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.nodeAggregateIdentifier/', '$1.aggregateId');
        $this->addCommentsIfRegexMatches('/\.nodeAggregateIdentifier/', 'Line %LINE: You may need to rewrite "VARIABLE.nodeAggregateIdentifier" to VARIABLE.aggregateId. We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        // getNodeTypeName() compatible with property access
        // getNodeName()
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.nodeName/', '$1.name');
        $this->addCommentsIfRegexMatches('/\.nodeName/', 'Line %LINE: !! You very likely need to rewrite "VARIABLE.nodeName" to "VARIABLE.name". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        $this->fusionFlowQueryNodePropertyToWarningComment('_nodeName', 'Line %LINE: !! You very likely need to rewrite "q(VARIABLE).property("_nodeName")" to "VARIABLE.name". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        // getOriginDimensionSpacePoint() -> threw exception in <= Neos 8.0 - so nobody could have used this

        /**
         * Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface
         */
        // no methods for fusion access

        /**
         * Context
         */
        // Context::getWorkspaceName()
        // Rewrite "node.context.workspaceName" to "node.workspaceName"
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.context\.(workspaceName|workspace\.name)\b/', '$1.workspaceName');
        $this->addCommentsIfRegexMatches('/\.context\.workspaceName/', 'Line %LINE: You very likely need to rewrite "VARIABLE.context.workspaceName" to "VARIABLE.workspaceName". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        // Context::getRootNode()
        $this->fusionNodePropertyPathToWarningComment('context.rootNode', 'Line %LINE: !! node.context.rootNode is removed in Neos 9.0.');
        // getCurrentDateTime(): DateTime|DateTimeInterface
        $this->fusionNodePropertyPathToWarningComment('context.currentDateTime', 'Line %LINE: !! node.context.currentDateTime is removed in Neos 9.0.');
        // getDimensions(): array
        $this->fusionNodePropertyPathToWarningComment('context.dimensions', 'Line %LINE: !! node.context.dimensions is removed in Neos 9.0. You can get node DimensionSpacePoints via node.dimensionSpacePoints now or use the `Neos.Dimension.*` helper.');
        // getProperties(): array
        $this->fusionNodePropertyPathToWarningComment('context.properties', 'Line %LINE: !! node.context.properties is removed in Neos 9.0.');
        // getTargetDimensions(): array
        $this->fusionNodePropertyPathToWarningComment('context.targetDimensions', 'Line %LINE: !! node.context.targetDimensions is removed in Neos 9.0.');
        // getTargetDimensionValues(): array
        $this->fusionNodePropertyPathToWarningComment('context.targetDimensionValues', 'Line %LINE: !! node.context.targetDimensionValues is removed in Neos 9.0.');
        // getWorkspace([createWorkspaceIfNecessary: bool = true]): Workspace
        // Add comment to "node.context.workspace"
        $this->addCommentsIfRegexMatches('/\.context\.workspace(\.\w)?\b/', 'Line %LINE: You very likely need to rewrite "VARIABLE.context.workspace" as the "context" of nodes has been removed without a direct replacement in Neos 9. If you really need the workspace in fusion you need to create a dedicated helper yourself which should ideally do ALL the complex logic in php directly and return the computed result.');
        // isInaccessibleContentShown(): bool
        $this->fusionNodePropertyPathToWarningComment('context.isInaccessibleContentShown', 'Line %LINE: !! node.context.isInaccessibleContentShown is removed in Neos 9.0.');
        // isInvisibleContentShown(): bool
        $this->fusionNodePropertyPathToWarningComment('context.isInvisibleContentShown', 'Line %LINE: !! node.context.isInvisibleContentShown is removed in Neos 9.0.');
        // isRemovedContentShown(): bool
        $this->fusionNodePropertyPathToWarningComment('context.isRemovedContentShown', 'Line %LINE: !! node.context.isRemovedContentShown is removed in Neos 9.0.');

        /**
         * ContentContext
         */
        // ContentContext::getCurrentSite
        // Rewrite node.context.currentSite to Neos.Site.findBySiteNode(site)
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.context\.currentSite\b/', 'Neos.Site.findBySiteNode(site)');
        $this->addCommentsIfRegexMatches('/\.context\.currentSite\b/', 'Line %LINE: You very likely need to rewrite "VARIABLE.context.currentSite" to "Neos.Site.findBySiteNode(site)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        // ContentContext::getCurrentDomain
        $this->fusionNodePropertyPathToWarningComment('context.currentDomain', 'Line %LINE: !! node.context.currentDomain is removed in Neos 9.0.');
        // ContentContext::getCurrentSiteNode
        $this->fusionNodePropertyPathToWarningComment('context.currentSiteNode', 'Line %LINE: !! node.context.currentSiteNode is removed in Neos 9.0. Check if you can\'t simply use ${site}.');
        // ContentContext::isLive
        // Rewrite "node.context.live" to "!renderingMode.isEdit"
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.context\.live/', '!renderingMode.isEdit');
        $this->addCommentsIfRegexMatches('/\.context\.live/', 'Line %LINE: You very likely need to rewrite "VARIABLE.context.live" to "!renderingMode.isEdit". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        // ContentContext::isInBackend
        // Rewrite "node.context.inBackend" to "renderingMode.isEdit"
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.context\.inBackend/', 'renderingMode.isEdit');
        $this->addCommentsIfRegexMatches('/\.context\.inBackend/', 'Line %LINE: You very likely need to rewrite "VARIABLE.context.inBackend" to "renderingMode.isEdit". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        // ContentContext::getCurrentRenderingMode... -> renderingMode...
        // Rewrite node.context.currentRenderingMode... to renderingMode...
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.context\.currentRenderingMode\.(name|title|fusionPath|options)/', 'renderingMode.$2');
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.context\.currentRenderingMode\.edit/', 'renderingMode.isEdit');
        $this->replaceEelExpression('/(?<!\.)(node|documentNode|site)\.context\.currentRenderingMode\.preview/', 'renderingMode.isPreview');
        $this->addCommentsIfRegexMatches('/\.context\.currentRenderingMode/', 'Line %LINE: You very likely need to rewrite "VARIABLE.context.currentRenderingMode..." to "renderingMode...". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.');
        /**
         * CacheLifetimeOperation and caching
         */
        // Add comment if .cacheLifetime() is used.
        $this->addCommentsIfRegexMatches('/\.cacheLifetime()/', 'Line %LINE: You may need to remove ".cacheLifetime()" as this FlowQuery Operation has been removed. This is not needed anymore as the concept of timeable node visibility has changed. See https://github.com/neos/timeable-node-visibility');
        // Rewrite node to Neos.Caching.entryIdentifierForNode(...) in @cache.entryIdentifier segments
        $this->replaceEelExpressionInsideFusionPath('/^(?<!Neos\.Caching\.entryIdentifierForNode\()(node|documentNode|site)$/', 'Neos.Caching.entryIdentifierForNode($1)', '__meta/cache/entryIdentifier');

        /**
         * FlowQuery Operation context()
         */
        // Add comments for q(node).context({targetDimensions|currentDateTime|removedContentShown|inaccessibleContentShown: ...})
        $this->addCommentsIfRegexMatches('/context\(\s*\{(.*)[\'"](targetDimensions|currentDateTime|removedContentShown|inaccessibleContentShown)[\'"](.*)\}\s*\)/', 'Line %LINE: The "context()" FlowQuery operation has changed and does not support the following properties anymore: targetDimensions,currentDateTime,removedContentShown,inaccessibleContentShown.');

        // Add comments for legacy underscore access in sort() operation with nodes
        $this->addCommentsIfRegexMatches('/sort\(\s*[\'"]_.*[\'"]/', 'Line %LINE: The "sort()" FlowQuery operation for nodes does no longer support underscore properties. In case it was sorted by _creationDateTime, _lastModificationDateTime or _lastPublicationDateTime in neos 9.0 the new sortByTimestamp(created|lastModified|originalCreated|originalLastModified) flowquery can be used.');

        /**
         * Neos.Neos-FusionObject changes
         */
        $this->renameOnlyFusionPrototypeInstantiations('Neos.Neos:PrimaryContent', 'Neos.Neos:ContentCollection', '"Neos.Neos:PrimaryContent" has been removed without a complete replacement. We replaced all usages with "Neos.Neos:ContentCollection" but not the prototype definition. Please check the replacements and if you have overridden the "Neos.Neos:PrimaryContent" prototype and rewrite it for your needs.');
    }
}
