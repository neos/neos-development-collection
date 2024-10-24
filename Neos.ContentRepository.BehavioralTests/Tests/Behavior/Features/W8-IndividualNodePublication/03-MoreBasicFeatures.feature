@contentrepository @adapters=DoctrineDBAL
Feature: Publishing individual nodes (basics)

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
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                               |
      | workspaceName               | "live"                                              |
      | nodeAggregateId             | "sir-david-nodenborough"                            |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content"            |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                            |
      | initialPropertyValues       | {"text": "Initial t1"}                              |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                               |
      | contentStreamId             | "cs-identifier"                                     |
      | nodeAggregateId             | "nody-mc-nodeface"                                  |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content"            |
      | parentNodeAggregateId       | "sir-david-nodenborough"                            |
      | initialPropertyValues       | {"text": "Initial t2"}                              |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                   |
      | workspaceName               | "live"                                                  |
      | nodeAggregateId             | "sir-nodeward-nodington-iii"                            |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Image"                  |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                |
      | initialPropertyValues       | {"image": "Initial image"}                              |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                   |
      | workspaceName               | "live"                                                  |
      | nodeAggregateId             | "sir-unchanged"                                         |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Image"                  |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                |

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
  # PUBLISHING
  ################
  Scenario: It is possible to publish a single node; and only this one is live.
    # publish "sir-nodeward-nodington-iii" only
    When the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                                                                                                        |
      | workspaceName                   | "user-test"                                                                                                  |
      | nodesToPublish                  | [{"workspaceName": "user-test", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-nodeward-nodington-iii"}] |
      | contentStreamIdForRemainingPart | "user-cs-identifier-remaining"                                                                               |
    Then I expect the content stream "user-cs-identifier" to not exist

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
      | Key   | Value            |
      | image | "Modified image" |

    When I am in workspace "user-test" and dimension space point {}
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

  Scenario: Publish no node, non existing ones or unchanged nodes is a no-op
    # no node
    When the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                          |
      | workspaceName                   | "user-test"                    |
      | nodesToPublish                  | []                             |
      | contentStreamIdForRemainingPart | "user-cs-identifier-remaining" |
    Then I expect the content stream "user-cs-identifier-remaining" to not exist

    # unchanged or non existing nodes
    When the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                                                                                                                                  |
      | workspaceName                   | "user-test"                                                                                                                            |
      | nodesToPublish                  | [{"dimensionSpacePoint": {}, "nodeAggregateId": "non-existing-node"}, {"dimensionSpacePoint": {}, "nodeAggregateId": "sir-unchanged"}] |
      | contentStreamIdForRemainingPart | "user-cs-identifier-remaining-two"                                                                                                     |

    When I am in workspace "live" and dimension space point {}
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

    # all nodes are still on the original user cs
    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node user-cs-identifier;sir-david-nodenborough;{}
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "Modified t1" |
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "Modified t2" |
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node user-cs-identifier;sir-nodeward-nodington-iii;{}
    And I expect this node to have the following properties:
      | Key   | Value            |
      | image | "Modified image" |

    # assert that content stream is still open by writing to it:
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | workspaceName             | "user-test"                  |
      | nodeAggregateId           | "sir-nodeward-nodington-iii" |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"image": "Bla bli blub"}    |

  Scenario: Tag the same node in live and in the user workspace so that a rebase will omit the user change
    When the command TagSubtree is executed with payload:
      | Key                          | Value                     |
      | workspaceName                | "live"                    |
      | nodeAggregateId              | "sir-unchanged"           |
      | nodeVariantSelectionStrategy | "allVariants"             |
      | tag                          | "tag1"                    |
    When the command TagSubtree is executed with payload:
      | Key                          | Value                     |
      | workspaceName                | "user-test"               |
      | nodeAggregateId              | "sir-unchanged"           |
      | nodeVariantSelectionStrategy | "allVariants"             |
      | tag                          | "tag1"                    |
    When the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                                                             |
      | workspaceName                   | "user-test"                                                       |
      | nodesToPublish                  | [{"dimensionSpacePoint": {}, "nodeAggregateId": "sir-unchanged"}] |
      | contentStreamIdForRemainingPart | "user-cs-identifier-remaining"                                    |

    When I am in workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "sir-unchanged" to lead to node cs-identifier;sir-unchanged;{}
    And I expect this node to be exactly explicitly tagged "tag1"

    # the node is still in the original user cs
    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-unchanged" to lead to node user-cs-identifier;sir-unchanged;{}
    And I expect this node to be exactly explicitly tagged "tag1"

    # assert that content stream is still open by writing to it:
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | workspaceName             | "user-test"                  |
      | nodeAggregateId           | "sir-nodeward-nodington-iii" |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"image": "Bla bli blub"}    |

  Scenario: It is possible to publish all nodes
    When the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                                                                                                                                                                                                                                                                                                                  |
      | workspaceName                   | "user-test"                                                                                                                                                                                                                                                                                                            |
      | nodesToPublish                  | [{"workspaceName": "user-test", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-david-nodenborough"}, {"workspaceName": "user-test", "dimensionSpacePoint": {}, "nodeAggregateId": "nody-mc-nodeface"}, {"workspaceName": "user-test", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-nodeward-nodington-iii"}] |
      | contentStreamIdForRemainingPart | "user-cs-identifier-remaining"                                                                                                                                                                                                                                                                                         |

    When I am in workspace "live" and dimension space point {}
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

    When I am in workspace "user-test" and dimension space point {}
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

  Scenario: Publish individual nodes commits exactly the expected events on each stream
    When the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                                                                                                        |
      | workspaceName                   | "user-test"                                                                                                  |
      | nodesToPublish                  | [{"dimensionSpacePoint": {}, "nodeAggregateId": "sir-nodeward-nodington-iii"}, {"dimensionSpacePoint": {}, "nodeAggregateId": "sir-david-nodenborough"}] |
      | contentStreamIdForRemainingPart | "user-cs-identifier-remaining"                                                                               |

    Then I expect exactly 8 events to be published on stream "ContentStream:cs-identifier"
    And event at index 6 is of type "NodePropertiesWereSet" with payload:
      | Key                           | Expected                                                |
      | contentStreamId               | "cs-identifier"                                         |
      | nodeAggregateId               | "sir-david-nodenborough"                                |
    And event at index 7 is of type "NodePropertiesWereSet" with payload:
      | Key                           | Expected                                                |
      | contentStreamId               | "cs-identifier"                                         |
      | nodeAggregateId               | "sir-nodeward-nodington-iii"                            |

    Then I expect exactly 4 events to be published on stream "ContentStream:user-cs-identifier-remaining"
    And event at index 0 is of type "ContentStreamWasForked" with payload:
      | Key                           | Expected                                                |
      | newContentStreamId            | "user-cs-identifier-remaining"                          |
    And event at index 1 is of type "ContentStreamWasClosed" with payload:
      | Key                           | Expected                                                |
      | contentStreamId               | "user-cs-identifier-remaining"                          |
    And event at index 2 is of type "NodePropertiesWereSet" with payload:
      | Key                           | Expected                                                |
      | contentStreamId               | "user-cs-identifier-remaining"                          |
      | nodeAggregateId               | "nody-mc-nodeface"                                      |
    And event at index 3 is of type "ContentStreamWasReopened" with payload:
      | Key                           | Expected                                                |
      | contentStreamId               | "user-cs-identifier-remaining"                          |
