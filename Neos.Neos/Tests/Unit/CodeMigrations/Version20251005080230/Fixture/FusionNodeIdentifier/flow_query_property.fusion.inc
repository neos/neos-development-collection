prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    #
    # pass down props
    #
    attributes = ${q(node).property("_identifier") || q(documentNode).property("_identifier")}
    renderer = afx`
      <input
        name={q(node).property('_identifier')}
        value={q(someOtherVariable).property("_identifier")}
        {...q(node).property("_identifier")}
      />
    `
  }
}
-----
prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    #
    # pass down props
    #
    attributes = ${node.aggregateId || documentNode.aggregateId}
    renderer = afx`
      <input
        name={node.aggregateId}
        value={someOtherVariable.aggregateId}
        {...node.aggregateId}
      />
    `
  }
}
