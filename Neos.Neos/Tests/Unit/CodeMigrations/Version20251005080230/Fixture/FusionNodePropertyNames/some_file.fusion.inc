prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

    renderer = Neos.Fusion:Component {

        #
        # pass down props
        #
        attributes = ${node.propertyNames || documentNode.propertyNames || site.propertyNames}

        renderer = afx`
        <input
                type="checkbox"
                name={node.propertyNames}
                value={someOtherVariable.propertyNames}
                checked={props.checked}
                {...node.propertyNames}
        />
        `
    }
}
-----
// TODO Neos.Neos-20251005080230 Line 15: !! You very likely need to rewrite "VARIABLE.propertyNames" to "Array.keys(VARIABLE.properties)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

    renderer = Neos.Fusion:Component {

        #
        # pass down props
        #
        attributes = ${Array.keys(node.properties) || Array.keys(documentNode.properties) || Array.keys(site.properties)}

        renderer = afx`
        <input
                type="checkbox"
                name={Array.keys(node.properties)}
                value={someOtherVariable.propertyNames}
                checked={props.checked}
                {...Array.keys(node.properties)}
        />
        `
    }
}
