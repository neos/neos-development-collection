@fixtures
Feature: Publishing individual nodes (basics)

  Publishing an individual node works
  Node structure is as follows:
  - rn-identifier (root node)
  -- sir-david-nodenborough (name=text1) <== modifications!
  --- nody-mc-nodeface (name=text2) <== modifications!
  -- sir-nodeward-nodington-iii (name=image) <== modifications!


  Background:
    Given I have no content dimensions
    And the command CreateRootWorkspace is executed with payload:
      | Key                     | Value           |
      | workspaceName           | "live"          |
      | contentStreamIdentifier | "cs-identifier" |
    And I have the following NodeTypes configuration:
    """
    Neos.ContentRepository:Root: {}
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    'Neos.ContentRepository.Testing:Image':
      properties:
        image:
          type: string
    """
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                         |
      | contentStreamIdentifier       | "cs-identifier"               |
      | nodeAggregateIdentifier       | "lady-eleonode-rootford"      |
      | nodeTypeName                  | "Neos.ContentRepository:Root" |
      | visibleInDimensionSpacePoints | [{}]                          |
      | initiatingUserIdentifier      | "system"                      |
      | nodeAggregateClassification   | "root"                        |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                               |
      | contentStreamIdentifier       | "cs-identifier"                                     |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                            |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"            |
      | originDimensionSpacePoint     | {}                                                  |
      | visibleInDimensionSpacePoints | [{}]                                                |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                            |
      | initialPropertyValues         | {"text": {"type": "string", "value": "Initial t1"}} |
      | nodeAggregateClassification   | "regular"                                           |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                               |
      | contentStreamIdentifier       | "cs-identifier"                                     |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"            |
      | originDimensionSpacePoint     | {}                                                  |
      | visibleInDimensionSpacePoints | [{}]                                                |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                            |
      | initialPropertyValues         | {"text": {"type": "string", "value": "Initial t2"}} |
      | nodeAggregateClassification   | "regular"                                           |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                  |
      | contentStreamIdentifier       | "cs-identifier"                                        |
      | nodeAggregateIdentifier       | "sir-nodeward-nodington-iii"                           |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Image"                 |
      | originDimensionSpacePoint     | {}                                                     |
      | visibleInDimensionSpacePoints | [{}]                                                   |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                               |
      | initialPropertyValues         | {"image": {"type": "image", "value": "Initial image"}} |
      | nodeAggregateClassification   | "regular"                                              |
    And the graph projection is fully up to date

    # Create user workspace
    And the command CreateWorkspace is executed with payload:
      | Key                     | Value                |
      | workspaceName           | "user-test"          |
      | baseWorkspaceName       | "live"               |
      | contentStreamIdentifier | "user-cs-identifier" |
    And the graph projection is fully up to date
    # modify nodes in user WS
    And the command "SetNodeProperty" is executed with payload:
      | Key                       | Value                                   |
      | contentStreamIdentifier   | "user-cs-identifier"                    |
      | nodeAggregateIdentifier   | "sir-david-nodenborough"                |
      | originDimensionSpacePoint | {}                                      |
      | propertyName              | "text"                                  |
      | value                     | {"value":"Modified t1","type":"string"} |
    And the command "SetNodeProperty" is executed with payload:
      | Key                       | Value                                   |
      | contentStreamIdentifier   | "user-cs-identifier"                    |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"                      |
      | originDimensionSpacePoint | {}                                      |
      | propertyName              | "text"                                  |
      | value                     | {"value":"Modified t2","type":"string"} |
    And the command "SetNodeProperty" is executed with payload:
      | Key                       | Value                                      |
      | contentStreamIdentifier   | "user-cs-identifier"                       |
      | nodeAggregateIdentifier   | "sir-nodeward-nodington-iii"               |
      | originDimensionSpacePoint | {}                                         |
      | propertyName              | "image"                                    |
      | value                     | {"value":"Modified image","type":"string"} |
    And the graph projection is fully up to date

  Scenario: It is possible to publish a single node; and only this one is live.
    # publish "sir-nodeward-nodington-iii" only
    When the command "PublishIndividualNodesFromWorkspace" is executed with payload:
      | Key           | Value                                                                                                                                   |
      | workspaceName | "user-test"                                                                                                                             |
      | nodeAddresses | [{"contentStreamIdentifier": "user-cs-identifier", "dimensionSpacePoint": {}, "nodeAggregateIdentifier": "sir-nodeward-nodington-iii"}] |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect the Node Aggregate "sir-david-nodenborough" to have the properties:
      | Key  | Value      |
      | text | Initial t1 |
    And I expect the Node Aggregate "nody-mc-nodeface" to have the properties:
      | Key  | Value      |
      | text | Initial t2 |
    And I expect the Node Aggregate "sir-nodeward-nodington-iii" to have the properties:
      | Key   | Value          |
      | image | Modified image |

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect the Node Aggregate "sir-david-nodenborough" to have the properties:
      | Key  | Value       |
      | text | Modified t1 |
    And I expect the Node Aggregate "nody-mc-nodeface" to have the properties:
      | Key  | Value       |
      | text | Modified t2 |
    And I expect the Node Aggregate "sir-nodeward-nodington-iii" to have the properties:
      | Key   | Value          |
      | image | Modified image |


  Scenario: It is possible to publish no node
    When the command "PublishIndividualNodesFromWorkspace" is executed with payload:
      | Key           | Value       |
      | workspaceName | "user-test" |
      | nodeAddresses | []          |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect the Node Aggregate "sir-david-nodenborough" to have the properties:
      | Key  | Value      |
      | text | Initial t1 |
    And I expect the Node Aggregate "nody-mc-nodeface" to have the properties:
      | Key  | Value      |
      | text | Initial t2 |
    And I expect the Node Aggregate "sir-nodeward-nodington-iii" to have the properties:
      | Key   | Value         |
      | image | Initial image |

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect the Node Aggregate "sir-david-nodenborough" to have the properties:
      | Key  | Value       |
      | text | Modified t1 |
    And I expect the Node Aggregate "nody-mc-nodeface" to have the properties:
      | Key  | Value       |
      | text | Modified t2 |
    And I expect the Node Aggregate "sir-nodeward-nodington-iii" to have the properties:
      | Key   | Value          |
      | image | Modified image |

  Scenario: It is possible to publish all nodes
    When the command "PublishIndividualNodesFromWorkspace" is executed with payload:
      | Key           | Value                                                                                                                                                                                                                                                                                                                                                                                                   |
      | workspaceName | "user-test"                                                                                                                                                                                                                                                                                                                                                                                             |
      | nodeAddresses | [{"contentStreamIdentifier": "user-cs-identifier", "dimensionSpacePoint": {}, "nodeAggregateIdentifier": "sir-david-nodenborough"}, {"contentStreamIdentifier": "user-cs-identifier", "dimensionSpacePoint": {}, "nodeAggregateIdentifier": "nody-mc-nodeface"}, {"contentStreamIdentifier": "user-cs-identifier", "dimensionSpacePoint": {}, "nodeAggregateIdentifier": "sir-nodeward-nodington-iii"}] |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect the Node Aggregate "sir-david-nodenborough" to have the properties:
      | Key  | Value       |
      | text | Modified t1 |
    And I expect the Node Aggregate "nody-mc-nodeface" to have the properties:
      | Key  | Value       |
      | text | Modified t2 |
    And I expect the Node Aggregate "sir-nodeward-nodington-iii" to have the properties:
      | Key   | Value          |
      | image | Modified image |

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect the Node Aggregate "sir-david-nodenborough" to have the properties:
      | Key  | Value       |
      | text | Modified t1 |
    And I expect the Node Aggregate "nody-mc-nodeface" to have the properties:
      | Key  | Value       |
      | text | Modified t2 |
    And I expect the Node Aggregate "sir-nodeward-nodington-iii" to have the properties:
      | Key   | Value          |
      | image | Modified image |
