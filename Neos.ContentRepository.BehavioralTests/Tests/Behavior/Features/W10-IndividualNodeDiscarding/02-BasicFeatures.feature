@contentrepository @adapters=DoctrineDBAL
Feature: Discard individual nodes (basics)

  Publishing an individual node works
  Node structure is as follows:
  - rn-identifier (root node)
  -- sir-david-nodenborough (name=text1) <== modifications!
  --- nody-mc-nodeface (name=text2) <== modifications!
  -- sir-nodeward-nodington-iii (name=image) <== modifications!


  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
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
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                               |
      | workspaceName               | "live"                                              |
      | contentStreamId             | "cs-identifier"                                     |
      | nodeAggregateId             | "sir-david-nodenborough"                            |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content"            |
      | originDimensionSpacePoint   | {}                                                  |
      | coveredDimensionSpacePoints | [{}]                                                |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                            |
      | initialPropertyValues       | {"text": {"type": "string", "value": "Initial t1"}} |
      | nodeAggregateClassification | "regular"                                           |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                               |
      | workspaceName               | "live"                                              |
      | contentStreamId             | "cs-identifier"                                     |
      | nodeAggregateId             | "nody-mc-nodeface"                                  |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content"            |
      | originDimensionSpacePoint   | {}                                                  |
      | coveredDimensionSpacePoints | [{}]                                                |
      | parentNodeAggregateId       | "sir-david-nodenborough"                            |
      | initialPropertyValues       | {"text": {"type": "string", "value": "Initial t2"}} |
      | nodeAggregateClassification | "regular"                                           |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                   |
      | workspaceName               | "live"                                                  |
      | contentStreamId             | "cs-identifier"                                         |
      | nodeAggregateId             | "sir-nodeward-nodington-iii"                            |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Image"                  |
      | originDimensionSpacePoint   | {}                                                      |
      | coveredDimensionSpacePoints | [{}]                                                    |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                |
      | initialPropertyValues       | {"image": {"type": "string", "value": "Initial image"}} |
      | nodeAggregateClassification | "regular"                                               |

    # Create user workspace
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |
    # modify nodes in user WS
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                    |
      | workspaceName             | "user-test"              |
      | nodeAggregateId           | "sir-david-nodenborough" |
      | originDimensionSpacePoint | {}                       |
      | propertyValues            | {"text": "Modified t1"}  |
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                   |
      | workspaceName             | "user-test"             |
      | nodeAggregateId           | "nody-mc-nodeface"      |
      | originDimensionSpacePoint | {}                      |
      | propertyValues            | {"text": "Modified t2"} |
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | workspaceName             | "user-test"                  |
      | nodeAggregateId           | "sir-nodeward-nodington-iii" |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"image": "Modified image"}  |

  ################
  # DISCARDING
  ################
  Scenario: It is possible to discard a single node; and only the others are live.
    # discard "sir-nodeward-nodington-iii" only
    When the command DiscardIndividualNodesFromWorkspace is executed with payload:
      | Key                | Value                                                                                                        |
      | workspaceName      | "user-test"                                                                                                  |
      | nodesToDiscard     | [{"workspaceName": "user-test", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-nodeward-nodington-iii"}] |
      | newContentStreamId | "user-cs-identifier-new"                                                                                     |


    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node user-cs-identifier-new;sir-david-nodenborough;{}
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "Modified t1" |
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier-new;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "Modified t2" |
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node user-cs-identifier-new;sir-nodeward-nodington-iii;{}
    And I expect this node to have the following properties:
      | Key   | Value           |
      | image | "Initial image" |

  Scenario: It is possible to discard no node
    When the command DiscardIndividualNodesFromWorkspace is executed with payload:
      | Key                | Value                    |
      | workspaceName      | "user-test"              |
      | nodesToDiscard     | []                       |
      | newContentStreamId | "user-cs-identifier-new" |

    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node user-cs-identifier-new;sir-david-nodenborough;{}
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "Modified t1" |
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier-new;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "Modified t2" |
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node user-cs-identifier-new;sir-nodeward-nodington-iii;{}
    And I expect this node to have the following properties:
      | Key   | Value            |
      | image | "Modified image" |

  Scenario: It is possible to discard all nodes
    When the command DiscardIndividualNodesFromWorkspace is executed with payload:
      | Key                | Value                                                                                                                                                                                                                                                                                                                  |
      | workspaceName      | "user-test"                                                                                                                                                                                                                                                                                                            |
      | nodesToDiscard     | [{"workspaceName": "user-test", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-david-nodenborough"}, {"workspaceName": "user-test", "dimensionSpacePoint": {}, "nodeAggregateId": "nody-mc-nodeface"}, {"workspaceName": "user-test", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-nodeward-nodington-iii"}] |
      | newContentStreamId | "user-cs-identifier-new"                                                                                                                                                                                                                                                                                               |

    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node user-cs-identifier-new;sir-david-nodenborough;{}
    And I expect this node to have the following properties:
      | Key  | Value        |
      | text | "Initial t1" |
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier-new;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value        |
      | text | "Initial t2" |
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node user-cs-identifier-new;sir-nodeward-nodington-iii;{}
    And I expect this node to have the following properties:
      | Key   | Value           |
      | image | "Initial image" |

  Scenario: When discarding a node, the live workspace does not change.
    # discard "sir-nodeward-nodington-iii"
    When the command DiscardIndividualNodesFromWorkspace is executed with payload:
      | Key            | Value                                                                                                        |
      | workspaceName  | "user-test"                                                                                                  |
      | nodesToDiscard | [{"workspaceName": "user-test", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-nodeward-nodington-iii"}] |

    # live WS does not change because of a discard
    When I am in workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to have the following properties:
      | Key  | Value        |
      | text | "Initial t1" |
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value        |
      | text | "Initial t2" |
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}
    And I expect this node to have the following properties:
      | Key   | Value           |
      | image | "Initial image" |

