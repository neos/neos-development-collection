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
  # DISCARDING
  ################
  Scenario: It is possible to discard a single node; and only the others are live.
    # discard "sir-nodeward-nodington-iii" only
    When the command DiscardIndividualNodesFromWorkspace is executed with payload:
      | Key                | Value                                                                                                        |
      | workspaceName      | "user-test"                                                                                                  |
      | nodesToDiscard     | ["sir-nodeward-nodington-iii"] |
      | newContentStreamId | "user-cs-identifier-new"                                                                                     |
    Then I expect the content stream "user-cs-identifier" to not exist

    Then I expect exactly 2 events to be published on stream with prefix "Workspace:user-test"
    And event at index 1 is of type "WorkspaceWasDiscarded" with payload:
      | Key                     | Expected                 |
      | workspaceName           | "user-test"              |
      | newContentStreamId      | "user-cs-identifier-new" |
      | previousContentStreamId | "user-cs-identifier"     |
      | partial                 | true                     |

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

  Scenario: Discard no node is not allowed
    When the command DiscardIndividualNodesFromWorkspace is executed with payload and exceptions are caught:
      | Key                | Value                    |
      | workspaceName      | "user-test"              |
      | nodesToDiscard     | []                       |
      | newContentStreamId | "user-cs-identifier-new" |
    Then the last command should have thrown an exception of type "InvalidArgumentException" with code 1737448741

  Scenario: Discard non existing nodes or unchanged nodes is skipped (via exception)
    # unchanged or non existing nodes
    When the command DiscardIndividualNodesFromWorkspace is executed with payload and exceptions are caught:
      | Key                             | Value                                                                                                                                  |
      | workspaceName                   | "user-test"                                                                                                                            |
      | nodesToDiscard                  | ["non-existing-node", "sir-unchanged"] |
      | newContentStreamId              | "user-cs-identifier-new-two"                                                                                                           |

    Then the last command should have thrown an exception of type "WorkspaceCommandSkipped" with code 1737477674 and message:
    """
    No nodes matched in workspace "user-test" the filter non-existing-node,sir-unchanged.
    """

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

  Scenario: It is possible to discard all nodes
    When the command DiscardIndividualNodesFromWorkspace is executed with payload:
      | Key                | Value                                                                                                                                                                                                                                                                                                                  |
      | workspaceName      | "user-test"                                                                                                                                                                                                                                                                                                            |
      | nodesToDiscard     | ["sir-david-nodenborough", "nody-mc-nodeface", "sir-nodeward-nodington-iii"] |
      | newContentStreamId | "user-cs-identifier-new"                                                                                                                                                                                                                                                                                               |

    Then I expect exactly 2 events to be published on stream with prefix "Workspace:user-test"
    And event at index 1 is of type "WorkspaceWasDiscarded" with payload:
      | Key                     | Expected                 |
      | workspaceName           | "user-test"              |
      | newContentStreamId      | "user-cs-identifier-new" |
      | previousContentStreamId | "user-cs-identifier"     |
      | partial                 | false                    |

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
      | nodesToDiscard | ["sir-nodeward-nodington-iii"] |

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

