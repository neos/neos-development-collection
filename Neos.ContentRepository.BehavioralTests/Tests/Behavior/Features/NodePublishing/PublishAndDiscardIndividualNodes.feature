@contentrepository @adapters=DoctrineDBAL
Feature: Publishing and discard individual nodes (basics)

  Publishing an individual node works
  Node structure is as follows:
  - rn-identifier (root node)
  -- sir-david-nodenborough (name=text1) <== modifications!
  --- nody-mc-nodeface (name=text2) <== modifications!
  -- sir-nodeward-nodington-iii (name=image) <== modifications!


  Background:
    Given I have no content dimensions
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value           |
      | workspaceName              | "live"          |
      | newContentStreamId | "cs-identifier" |
      | initiatingUserId   | "user-id"       |
    And the graph projection is fully up to date
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
      | Key                         | Value                         |
      | contentStreamId     | "cs-identifier"               |
      | nodeAggregateId     | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
      | coveredDimensionSpacePoints | [{}]                          |
      | initiatingUserId    | "system"                      |
      | nodeAggregateClassification | "root"                        |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                               |
      | contentStreamId       | "cs-identifier"                                     |
      | nodeAggregateId       | "sir-david-nodenborough"                            |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"            |
      | originDimensionSpacePoint     | {}                                                  |
      | coveredDimensionSpacePoints   | [{}]                                                |
      | parentNodeAggregateId | "lady-eleonode-rootford"                            |
      | initialPropertyValues         | {"text": {"type": "string", "value": "Initial t1"}} |
      | nodeAggregateClassification   | "regular"                                           |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                               |
      | contentStreamId       | "cs-identifier"                                     |
      | nodeAggregateId       | "nody-mc-nodeface"                                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"            |
      | originDimensionSpacePoint     | {}                                                  |
      | coveredDimensionSpacePoints   | [{}]                                                |
      | parentNodeAggregateId | "sir-david-nodenborough"                            |
      | initialPropertyValues         | {"text": {"type": "string", "value": "Initial t2"}} |
      | nodeAggregateClassification   | "regular"                                           |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                   |
      | contentStreamId       | "cs-identifier"                                         |
      | nodeAggregateId       | "sir-nodeward-nodington-iii"                            |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Image"                  |
      | originDimensionSpacePoint     | {}                                                      |
      | coveredDimensionSpacePoints   | [{}]                                                    |
      | parentNodeAggregateId | "lady-eleonode-rootford"                                |
      | initialPropertyValues         | {"image": {"type": "string", "value": "Initial image"}} |
      | nodeAggregateClassification   | "regular"                                               |
    And the graph projection is fully up to date

    # Create user workspace
    And the command CreateWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "user-test"          |
      | baseWorkspaceName          | "live"               |
      | newContentStreamId | "user-cs-identifier" |
      | initiatingUserId   | "user"               |
    And the graph projection is fully up to date
    # modify nodes in user WS
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | contentStreamId   | "user-cs-identifier"         |
      | nodeAggregateId   | "sir-david-nodenborough"     |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"text": "Modified t1"}      |
      | initiatingUserId  | "initiating-user-identifier" |
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | contentStreamId   | "user-cs-identifier"         |
      | nodeAggregateId   | "nody-mc-nodeface"           |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"text": "Modified t2"}      |
      | initiatingUserId  | "initiating-user-identifier" |
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | contentStreamId   | "user-cs-identifier"         |
      | nodeAggregateId   | "sir-nodeward-nodington-iii" |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"image": "Modified image"}  |
      | initiatingUserId  | "initiating-user-identifier" |
    And the graph projection is fully up to date

  ################
  # PUBLISHING
  ################
  Scenario: It is possible to publish a single node; and only this one is live.
    # publish "sir-nodeward-nodington-iii" only
    When the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                                     | Value                                                                                                                                   |
      | workspaceName                           | "user-test"                                                                                                                             |
      | nodesToPublish                          | [{"contentStreamId": "user-cs-identifier", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-nodeward-nodington-iii"}] |
      | initiatingUserId                | "user"                                                                                                                                  |
      | contentStreamIdForRemainingPart | "user-cs-identifier-remaining"                                                                                                          |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}
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
      | Key   | Value            |
      | image | "Modified image" |

    When I am in the active content stream of workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node user-cs-identifier-remaining;sir-david-nodenborough;{}
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "Modified t1" |
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier-remaining;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "Modified t2" |
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node user-cs-identifier-remaining;sir-nodeward-nodington-iii;{}
    And I expect this node to have the following properties:
      | Key   | Value            |
      | image | "Modified image" |

  Scenario: It is possible to publish no node
    When the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                                     | Value                          |
      | workspaceName                           | "user-test"                    |
      | nodesToPublish                          | []                             |
      | initiatingUserId                | "user"                         |
      | contentStreamIdForRemainingPart | "user-cs-identifier-remaining" |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to have the following properties:
      | Key  | Value        |
      | text | "Initial t1" |
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value        |
      | text | "Initial t2" |
    And I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}
    And I expect this node to have the following properties:
      | Key   | Value           |
      | image | "Initial image" |

    When I am in the active content stream of workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node user-cs-identifier-remaining;sir-david-nodenborough;{}
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "Modified t1" |
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier-remaining;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "Modified t2" |
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node user-cs-identifier-remaining;sir-nodeward-nodington-iii;{}
    And I expect this node to have the following properties:
      | Key   | Value            |
      | image | "Modified image" |

  Scenario: It is possible to publish all nodes
    When the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                                     | Value                                                                                                                                                                                                                                                                                                                                                                                                   |
      | workspaceName                           | "user-test"                                                                                                                                                                                                                                                                                                                                                                                             |
      | nodesToPublish                          | [{"contentStreamId": "user-cs-identifier", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-david-nodenborough"}, {"contentStreamId": "user-cs-identifier", "dimensionSpacePoint": {}, "nodeAggregateId": "nody-mc-nodeface"}, {"contentStreamId": "user-cs-identifier", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-nodeward-nodington-iii"}] |
      | initiatingUserId                | "user"                                                                                                                                                                                                                                                                                                                                                                                                  |
      | contentStreamIdForRemainingPart | "user-cs-identifier-remaining"                                                                                                                                                                                                                                                                                                                                                                          |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "Modified t1" |
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "Modified t2" |
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}
    And I expect this node to have the following properties:
      | Key   | Value            |
      | image | "Modified image" |

    When I am in the active content stream of workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node user-cs-identifier-remaining;sir-david-nodenborough;{}
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "Modified t1" |
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier-remaining;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "Modified t2" |
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node user-cs-identifier-remaining;sir-nodeward-nodington-iii;{}
    And I expect this node to have the following properties:
      | Key   | Value            |
      | image | "Modified image" |


  ################
  # DISCARDING
  ################
  Scenario: It is possible to discard a single node; and only the others are live.
    # discard "sir-nodeward-nodington-iii" only
    When the command DiscardIndividualNodesFromWorkspace is executed with payload:
      | Key                        | Value                                                                                                                                   |
      | workspaceName              | "user-test"                                                                                                                             |
      | nodesToDiscard             | [{"contentStreamId": "user-cs-identifier", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-nodeward-nodington-iii"}] |
      | initiatingUserId   | "user"                                                                                                                                  |
      | newContentStreamId | "user-cs-identifier-new"                                                                                                                |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "user-test" and dimension space point {}
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
      | Key                        | Value                    |
      | workspaceName              | "user-test"              |
      | nodesToDiscard             | []                       |
      | initiatingUserId   | "user"                   |
      | newContentStreamId | "user-cs-identifier-new" |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "user-test" and dimension space point {}
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
      | Key                        | Value                                                                                                                                                                                                                                                                                                                                                                                                   |
      | workspaceName              | "user-test"                                                                                                                                                                                                                                                                                                                                                                                             |
      | nodesToDiscard             | [{"contentStreamId": "user-cs-identifier", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-david-nodenborough"}, {"contentStreamId": "user-cs-identifier", "dimensionSpacePoint": {}, "nodeAggregateId": "nody-mc-nodeface"}, {"contentStreamId": "user-cs-identifier", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-nodeward-nodington-iii"}] |
      | initiatingUserId   | "user"                                                                                                                                                                                                                                                                                                                                                                                                  |
      | newContentStreamId | "user-cs-identifier-new"                                                                                                                                                                                                                                                                                                                                                                                |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "user-test" and dimension space point {}
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
      | Key                      | Value                                                                                                                                   |
      | workspaceName            | "user-test"                                                                                                                             |
      | nodesToDiscard           | [{"contentStreamId": "user-cs-identifier", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-nodeward-nodington-iii"}] |
      | initiatingUserId | "user"                                                                                                                                  |
    And the graph projection is fully up to date

    # live WS does not change because of a discard
    When I am in the active content stream of workspace "live" and dimension space point {}
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

