@fixtures
Feature: Change node aggregate type

  As a user of the CR I want to change the type of a node aggregate.

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values  | Generalizations |
      | language   | de      | de, gsw | gsw->de         |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:ParentNodeType': []
    'Neos.ContentRepository.Testing:ChildOfNodeTypeA': []
    'Neos.ContentRepository.Testing:ChildOfNodeTypeB': []
    'Neos.ContentRepository.Testing:NodeTypeA':
      childNodes:
        childOfTypeA: 'Neos.ContentRepository.Testing:ChildOfNodeTypeA'
      properties:
        text:
          type: string
          defaultValue: 'text'
    'Neos.ContentRepository.Testing:NodeTypeB':
      childNodes:
        childOfTypeB: 'Neos.ContentRepository.Testing:ChildOfNodeTypeB'
      properties:
        otherText:
          type: string
          defaultValue: 'otherText'
    """
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                    |
      | contentStreamIdentifier       | "cs-identifier"                          |
      | nodeAggregateIdentifier       | "lady-eleonode-nodesworth"               |
      | nodeTypeName                  | "Neos.ContentRepository:Root"            |
      | visibleInDimensionSpacePoints | [{"language": "de"},{"language": "gsw"}] |
      | initiatingUserIdentifier      | "system"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                           |
      | contentStreamIdentifier       | "cs-identifier"                                 |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:ParentNodeType" |
      | originDimensionSpacePoint     | {"language":"de"}                               |
      | visibleInDimensionSpacePoints | [{"language": "de"},{"language": "gsw"}]        |
      | parentNodeAggregateIdentifier | "lady-eleonode-nodesworth"                      |
      | nodeName                      | "parent"                                        |
      | initialPropertyValues         | {}                                              |
    And the graph projection is fully up to date

  #Scenario: Try to change the node aggregate type on a non-existing content stream
  #  When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
  #    | Key                     | Value                                             |
  #    | contentStreamIdentifier | "non-existing"                                    |
  #    | nodeAggregateIdentifier | "sir-david-nodenborough"                          |
  #    | newNodeTypeName         | "Neos.ContentRepository.Testing:ChildOfNodeTypeA" |
  #  Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  #Scenario: Try to change the type on a non-existing node aggregate
  #  When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
  #    | Key                     | Value                                             |
  #    | contentStreamIdentifier | "cs-identifier"                                   |
  #    | nodeAggregateIdentifier | "nody-mc-nodeface"                                |
  #    | newNodeTypeName         | "Neos.ContentRepository.Testing:ChildOfNodeTypeA" |
  #  Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to change a node aggregate to a non existing type
    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                     | Value                                      |
      | contentStreamIdentifier | "cs-identifier"                            |
      | nodeAggregateIdentifier | "sir-david-nodenborough"                   |
      | newNodeTypeName         | "Neos.ContentRepository.Testing:Undefined" |
    Then the last command should have thrown an exception of type "NodeTypeNotFound"

  Scenario: Try to change to a node type disallowed by the parent node
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:ParentNodeType':
      constraints:
        nodeTypes:
          '*': TRUE
          'Neos.ContentRepository.Testing:NodeTypeB': FALSE
    """
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                      |
      | contentStreamIdentifier       | "cs-identifier"                            |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                         |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeTypeA" |
      | originDimensionSpacePoint     | {"language":"de"}                          |
      | visibleInDimensionSpacePoints | [{"language": "de"},{"language": "gsw"}]   |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                   |
    And the graph projection is fully up to date
    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                     | Value                                      |
      | contentStreamIdentifier | "cs-identifier"                            |
      | nodeAggregateIdentifier | "nody-mc-nodeface"                         |
      | newNodeTypeName         | "Neos.ContentRepository.Testing:NodeTypeB" |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to change to a node type that is not allowed by the grand parent aggregate inside an autocreated parent aggregate
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:AutoCreated': []
    'Neos.ContentRepository.Testing:ParentNodeType':
      childNodes:
        autocreated:
          type: 'Neos.ContentRepository.Testing:AutoCreated'
          constraints:
            nodeTypes:
              '*': TRUE
              'Neos.ContentRepository.Testing:NodeTypeB': FALSE
    """
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                        |
      | contentStreamIdentifier       | "cs-identifier"                              |
      | nodeAggregateIdentifier       | "autocreated-child"                          |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:AutoCreated" |
      | originDimensionSpacePoint     | {"language": "de"}                           |
      | visibleInDimensionSpacePoints | [{"language": "de"},{"language": "gsw"}]     |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                     |
      | nodeName                      | "autocreated"                                |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                      |
      | contentStreamIdentifier       | "cs-identifier"                            |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                         |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeTypeA" |
      | originDimensionSpacePoint     | {"language":"de"}                          |
      | visibleInDimensionSpacePoints | [{"language": "de"},{"language": "gsw"}]   |
      | parentNodeAggregateIdentifier | "autocreated-child"                        |
    And the graph projection is fully up to date

    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                     | Value                                      |
      | contentStreamIdentifier | "cs-identifier"                            |
      | nodeAggregateIdentifier | "nody-mc-nodeface"                         |
      | newNodeTypeName         | "Neos.ContentRepository.Testing:NodeTypeB" |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to change the node type of an auto created child node to anything other than defined:
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:AutoCreated': []
    'Neos.ContentRepository.Testing:ParentNodeType':
      childNodes:
        autocreated:
          type: 'Neos.ContentRepository.Testing:AutoCreated'
    """
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                        |
      | contentStreamIdentifier       | "cs-identifier"                              |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                           |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:AutoCreated" |
      | originDimensionSpacePoint     | {"language":"de"}                            |
      | visibleInDimensionSpacePoints | [{"language": "de"},{"language": "gsw"}]     |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                     |
      | nodeName                      | "autocreated"                                |
    And the graph projection is fully up to date

    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                     | Value                                           |
      | contentStreamIdentifier | "cs-identifier"                                 |
      | nodeAggregateIdentifier | "nody-mc-nodeface"                              |
      | newNodeTypeName         | "Neos.ContentRepository.Testing:ParentNodeType" |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to change to a node type that disallows already present children without a conflict resolution strategy
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:ParentNodeTypeB':
      constraints:
        nodeTypes:
          '*': TRUE
          'Neos.ContentRepository.Testing:NodeTypeA': FALSE
    """
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                      |
      | contentStreamIdentifier       | "cs-identifier"                            |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                         |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeTypeA" |
      | originDimensionSpacePoint     | {"language":"de"}                          |
      | visibleInDimensionSpacePoints | [{"language": "de"},{"language": "gsw"}]   |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                     |
    And the graph projection is fully up to date
    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                     | Value                                            |
      | contentStreamIdentifier | "cs-identifier"                                  |
      | nodeAggregateIdentifier | "sir-david-nodenborough"                         |
      | newNodeTypeName         | "Neos.ContentRepository.Testing:ParentNodeTypeB" |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to change to a node type that disallows already present grandchildren without a conflict resolution strategy
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:AutoCreated': []
    'Neos.ContentRepository.Testing:ParentNodeType':
      childNodes:
        autocreated:
          type: 'Neos.ContentRepository.Testing:AutoCreated'
          constraints:
            nodeTypes:
              '*': TRUE
              'Neos.ContentRepository.Testing:NodeTypeB': FALSE
    'Neos.ContentRepository.Testing:ParentNodeTypeB':
      childNodes:
        autocreated:
          type: 'Neos.ContentRepository.Testing:AutoCreated'
          constraints:
            nodeTypes:
              '*': TRUE
              'Neos.ContentRepository.Testing:NodeTypeA': FALSE
    """
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                        |
      | contentStreamIdentifier       | "cs-identifier"                              |
      | nodeAggregateIdentifier       | "autocreated-child"                          |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:AutoCreated" |
      | originDimensionSpacePoint     | {"language":"de"}                            |
      | visibleInDimensionSpacePoints | [{"language": "de"},{"language": "gsw"}]     |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                     |
      | nodeName                      | "autocreated"                                |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                      |
      | contentStreamIdentifier       | "cs-identifier"                            |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                         |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeTypeA" |
      | originDimensionSpacePoint     | {"language":"de"}                          |
      | visibleInDimensionSpacePoints | [{"language": "de"},{"language": "gsw"}]   |
      | parentNodeAggregateIdentifier | "autocreated-child"                        |
    And the graph projection is fully up to date
    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                     | Value                                            |
      | contentStreamIdentifier | "cs-identifier"                                  |
      | nodeAggregateIdentifier | "sir-david-nodenborough"                         |
      | newNodeTypeName         | "Neos.ContentRepository.Testing:ParentNodeTypeB" |
    Then the last command should have thrown an exception of type "NodeConstraintException"

#  Scenario: Change node type
#    And the Event NodeAggregateWithNodeWasCreated was published with payload:
#      | Key                                      | Value                                                                                |
#      | contentStreamIdentifier                  | "cs-identifier"                                                                      |
#      | nodeAggregateIdentifier                  | "nodea-agg-identifier"                                                               |
#      | nodeTypeName                             | "Neos.ContentRepository.Testing:NodeTypeA"                                           |
#      | dimensionSpacePoint                      | {"language":"de"}                                                                    |
#      | visibleInDimensionSpacePoints            | {"points":[{"coordinates":{"language": "de"}}, {"coordinates":{"language": "gsw"}}]} |
#      | nodeIdentifier                           | "nodea-identifier-de"                                                                |
#      | parentNodeIdentifier                     | "parent-identifier"                                                                  |
#      | nodeName                                 | "nodea"                                                                              |
#      | propertyDefaultValuesAndTypes.text.value | "text"                                                                               |
#      | propertyDefaultValuesAndTypes.text.type  | "string"                                                                             |
#    And the event NodeSpecializationWasCreated was published with payload:
#      | Key                      | Value                                           |
#      | contentStreamIdentifier  | "cs-identifier"                                 |
#      | nodeIdentifier           | "nodea-identifier-de"                           |
#      | specializationIdentifier | "nodea-identifier-de"                           |
#      | specializationLocation   | {"market":"CH", "language":"gsw"}               |
#      | specializationVisibility | {"points":[{"coordinates":{"language":"gsw"}}]} |
#      node type change must be changed
#      missing autocreated child nodes of target type must be created
#      extra autocreated child nodes of source type must be removed if delete strategy is to be applied
#      missing default property values of target type must be set
#      extra properties of source target type must be removed (TBD)
#      all of this must cascade through all subgraphs
