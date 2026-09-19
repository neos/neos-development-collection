prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

    renderer = Neos.Fusion:Component {

        #
        # pass down props
        #
        attributes = ${node.contextPath || documentNode.contextPath || q(node).property('_contextPath') || q(documentNode).property("_contextPath")}
        foo = ${q(bar).property('_contextPath') || q(bar).property("_contextPath")}
        boo = ${q(nodes).first().property('_contextPath') || q(nodes).first().property("_contextPath")}

        renderer = afx`
        <input
                type="checkbox"
                name={node.contextPath}
                value={someOtherVariable.contextPath}
                checked={props.checked}
                {...node.contextPath}
        />
        `
    }
}
-----
// TODO Neos.Neos-20251005080230 Line 18: !! You very likely need to rewrite "VARIABLE.contextPath" to "Neos.Node.serializedNodeAddress(VARIABLE)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
// TODO Neos.Neos-20251005080230 Line 12: !! You very likely need to rewrite "q(VARIABLE).property("_contextPath")" to "Neos.Node.serializedNodeAddress(VARIABLE)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

    renderer = Neos.Fusion:Component {

        #
        # pass down props
        #
        attributes = ${Neos.Node.serializedNodeAddress(node) || Neos.Node.serializedNodeAddress(documentNode) || Neos.Node.serializedNodeAddress(node) || Neos.Node.serializedNodeAddress(documentNode)}
        foo = ${Neos.Node.serializedNodeAddress(bar) || Neos.Node.serializedNodeAddress(bar)}
        boo = ${q(nodes).first().property('_contextPath') || q(nodes).first().property("_contextPath")}

        renderer = afx`
        <input
                type="checkbox"
                name={Neos.Node.serializedNodeAddress(node)}
                value={someOtherVariable.contextPath}
                checked={props.checked}
                {...Neos.Node.serializedNodeAddress(node)}
        />
        `
    }
}
