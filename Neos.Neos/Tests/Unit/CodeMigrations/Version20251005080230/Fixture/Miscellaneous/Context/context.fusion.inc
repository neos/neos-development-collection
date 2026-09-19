prototype(Neos.Context:Prototype)  < prototype(Neos.Fusion:Component) {

    workspaceName = ${node.context.workspaceName}
    workspaceNameByWorkspace = ${node.context.workspace.name}
    rootNode = ${node.context.rootNode}

    currentDateTime = ${node.context.currentDateTime}
    dimensions = ${node.context.dimensions}
    properties = ${node.context.properties}

    targetDimensions = ${node.context.targetDimensions}
    targetDimensionValues = ${node.context.targetDimensionValues}

    workspace = ${node.context.workspace}
    workspaceSomeProperty = ${node.context.workspace.someProperty}

    isInaccessibleContentShown = ${node.context.isInaccessibleContentShown}
    isInvisibleContentShown = ${node.context.isInvisibleContentShown}
    isRemovedContentShown = ${node.context.isRemovedContentShown}

}
-----
// TODO Neos.Neos-20251005080230 Line 16: !! node.context.rootNode is removed in Neos 9.0.
// TODO Neos.Neos-20251005080230 Line 18: !! node.context.currentDateTime is removed in Neos 9.0.
// TODO Neos.Neos-20251005080230 Line 19: !! node.context.dimensions is removed in Neos 9.0. You can get node DimensionSpacePoints via node.dimensionSpacePoints now or use the `Neos.Dimension.*` helper.
// TODO Neos.Neos-20251005080230 Line 20: !! node.context.properties is removed in Neos 9.0.
// TODO Neos.Neos-20251005080230 Line 22: !! node.context.targetDimensions is removed in Neos 9.0.
// TODO Neos.Neos-20251005080230 Line 23: !! node.context.targetDimensionValues is removed in Neos 9.0.
// TODO Neos.Neos-20251005080230 Line 25: You very likely need to rewrite "VARIABLE.context.workspace" as the "context" of nodes has been removed without a direct replacement in Neos 9. If you really need the workspace in fusion you need to create a dedicated helper yourself which should ideally do ALL the complex logic in php directly and return the computed result.
// TODO Neos.Neos-20251005080230 Line 26: You very likely need to rewrite "VARIABLE.context.workspace" as the "context" of nodes has been removed without a direct replacement in Neos 9. If you really need the workspace in fusion you need to create a dedicated helper yourself which should ideally do ALL the complex logic in php directly and return the computed result.
// TODO Neos.Neos-20251005080230 Line 28: !! node.context.isInaccessibleContentShown is removed in Neos 9.0.
// TODO Neos.Neos-20251005080230 Line 29: !! node.context.isInvisibleContentShown is removed in Neos 9.0.
// TODO Neos.Neos-20251005080230 Line 30: !! node.context.isRemovedContentShown is removed in Neos 9.0.
prototype(Neos.Context:Prototype)  < prototype(Neos.Fusion:Component) {

    workspaceName = ${node.workspaceName}
    workspaceNameByWorkspace = ${node.workspaceName}
    rootNode = ${node.context.rootNode}

    currentDateTime = ${node.context.currentDateTime}
    dimensions = ${node.context.dimensions}
    properties = ${node.context.properties}

    targetDimensions = ${node.context.targetDimensions}
    targetDimensionValues = ${node.context.targetDimensionValues}

    workspace = ${node.context.workspace}
    workspaceSomeProperty = ${node.context.workspace.someProperty}

    isInaccessibleContentShown = ${node.context.isInaccessibleContentShown}
    isInvisibleContentShown = ${node.context.isInvisibleContentShown}
    isRemovedContentShown = ${node.context.isRemovedContentShown}

}
