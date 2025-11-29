prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

    renderer = Neos.Fusion:Component {

        #
        # pass down props
        #
        attributes = ${node.autoCreated || documentNode.autoCreated || site.autoCreated}

        renderer = afx`
        <input
                type="checkbox"
                name={node.autoCreated}
                value={someOtherVariable.autoCreated}
                checked={props.checked}
                {...node.autoCreated}
        />
        `
    }
}
-----
// TODO Neos.Neos-20251005080230 Line 15: !! You very likely need to rewrite "VARIABLE.autoCreated" to "VARIABLE.classification.tethered". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

    renderer = Neos.Fusion:Component {

        #
        # pass down props
        #
        attributes = ${node.classification.tethered || documentNode.classification.tethered || site.classification.tethered}

        renderer = afx`
        <input
                type="checkbox"
                name={node.classification.tethered}
                value={someOtherVariable.autoCreated}
                checked={props.checked}
                {...node.classification.tethered}
        />
        `
    }
}
