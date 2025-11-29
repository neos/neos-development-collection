prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    #
    # pass down props
    #
    attributes = ${node.nodeAggregateIdentifier || documentNode.nodeAggregateIdentifier}
    renderer = afx`
      <input
        name={node.nodeAggregateIdentifier}
        value={someOtherVariable.nodeAggregateIdentifier}
        {...node.nodeAggregateIdentifier}
      />
    `
  }
}
-----
// TODO Neos.Neos-20251005080230 Line 13: You may need to rewrite "VARIABLE.nodeAggregateIdentifier" to VARIABLE.aggregateId. We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    #
    # pass down props
    #
    attributes = ${node.aggregateId || documentNode.aggregateId}
    renderer = afx`
      <input
        name={node.aggregateId}
        value={someOtherVariable.nodeAggregateIdentifier}
        {...node.aggregateId}
      />
    `
  }
}
