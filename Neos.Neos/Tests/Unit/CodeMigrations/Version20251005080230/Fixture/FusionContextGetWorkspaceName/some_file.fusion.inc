prototype(Neos.Context:Prototype)  < prototype(Neos.Fusion:Component) {

    workspaceName = ${node.context.workspaceName}
    workspaceNameByWorkspace = ${node.context.workspace.name}

    attributes = ${node.context.workspaceName || site.context.workspaceName || documentNode.context.workspaceName}


    renderer = afx`
        <Neos.Context:Component
            type="checkbox"
            name={node.context.workspaceName}
            value={someOtherVariable.context.workspaceName}
        />
    `
}
-----
// TODO Neos.Neos-20251005080230 Line 14: You very likely need to rewrite "VARIABLE.context.workspaceName" to "VARIABLE.workspaceName". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Context:Prototype)  < prototype(Neos.Fusion:Component) {

    workspaceName = ${node.workspaceName}
    workspaceNameByWorkspace = ${node.workspaceName}

    attributes = ${node.workspaceName || site.workspaceName || documentNode.workspaceName}


    renderer = afx`
        <Neos.Context:Component
            type="checkbox"
            name={node.workspaceName}
            value={someOtherVariable.context.workspaceName}
        />
    `
}
