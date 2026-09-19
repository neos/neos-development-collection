prototype(Neos.Context:Prototype)  < prototype(Neos.Fusion:Component) {

    workspaceNameByWorkspace = ${node.workspace.name}

    attributes = ${node.workspace.name || site.workspace.name || documentNode.workspace.name}

    workspace = ${node.workspace}

    renderer = afx`
        <Neos.Context:Component
            type="checkbox"
            name={node.workspace.name}
            value={someOtherVariable.workspace.name}
        />
    `
}
-----
// TODO Neos.Neos-20251005080230 Line 9: You very likely need to rewrite "VARIABLE.workspace" as the "workspace" of nodes is not accessible this way and the object contains less information which is split up to the WorkspaceMetadata. If you really need the workspace in fusion you need to create a dedicated helper yourself which should ideally do ALL the complex logic in php directly and return the computed result.
// TODO Neos.Neos-20251005080230 Line 15: You very likely need to rewrite "VARIABLE.workspace" as the "workspace" of nodes is not accessible this way and the object contains less information which is split up to the WorkspaceMetadata. If you really need the workspace in fusion you need to create a dedicated helper yourself which should ideally do ALL the complex logic in php directly and return the computed result.
prototype(Neos.Context:Prototype)  < prototype(Neos.Fusion:Component) {

    workspaceNameByWorkspace = ${node.workspaceName}

    attributes = ${node.workspaceName || site.workspaceName || documentNode.workspaceName}

    workspace = ${node.workspace}

    renderer = afx`
        <Neos.Context:Component
            type="checkbox"
            name={node.workspaceName}
            value={someOtherVariable.workspace.name}
        />
    `
}
