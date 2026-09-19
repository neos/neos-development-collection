prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

    renderer = Neos.Fusion:Component {

        #
        # pass down props
        #
        attributes = ${node.nodeName || documentNode.nodeName || site.nodeName}

        renderer = afx`
        <input
                type="checkbox"
                name={node.nodeName}
                value={someOtherVariable.nodeName}
                checked={props.checked}
                {...node.nodeName}
        />
        `
    }
}
-----
// TODO Neos.Neos-20251005080230 Line 15: !! You very likely need to rewrite "VARIABLE.nodeName" to "VARIABLE.name". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

    renderer = Neos.Fusion:Component {

        #
        # pass down props
        #
        attributes = ${node.name || documentNode.name || site.name}

        renderer = afx`
        <input
                type="checkbox"
                name={node.name}
                value={someOtherVariable.nodeName}
                checked={props.checked}
                {...node.name}
        />
        `
    }
}
