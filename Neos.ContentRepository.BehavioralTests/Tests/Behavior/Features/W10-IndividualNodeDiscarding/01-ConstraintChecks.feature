@contentrepository @adapters=DoctrineDBAL
Feature: Workspace discarding - complex chained functionality

  Background:
    Given using the following content dimensions:
      | Identifier | Values          | Generalizations |
      | language   | ltz, de, en, fr | ltz->de->en     |

    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:ContentCollection':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:Content': true

    'Neos.ContentRepository.Testing:Document':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:ContentCollection'

    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {"language": "de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeTypeName                            | parentNodeAggregateId  | nodeName   | tetheredDescendantNodeAggregateIds |
      | sir-david-nodenborough | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | document   | {"tethered": "nodewyn-tetherton"}  |
      | nody-mc-nodeface       | Neos.ContentRepository.Testing:Content  | nodewyn-tetherton      | grandchild | {}                                 |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value        |
      | workspaceName      | "user-ws"    |
      | baseWorkspaceName  | "live"       |
      | newContentStreamId | "user-cs-id" |

  Scenario: Vary to generalization, then delete the origin and discard parts of the result so that an exception is thrown. Ensure that the workspace recovers from this
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | workspaceName   | "user-ws"                |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"language": "de"}       |
      | targetOrigin    | {"language": "en"}       |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | workspaceName   | "user-ws"          |
      | nodeAggregateId | "nody-mc-nodeface" |
      | sourceOrigin    | {"language": "de"} |
      | targetOrigin    | {"language": "en"} |

    And the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "user-ws"                |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"language": "en"}       |
      | nodeVariantSelectionStrategy | "allSpecializations"     |

    When the command DiscardIndividualNodesFromWorkspace is executed with payload and exceptions are caught:
      | Key                | Value                                                                                                                                                                                                                                        |
      | workspaceName      | "user-ws"                                                                                                                                                                                                                                    |
      | nodesToDiscard     | [{"workspaceName": "user-ws", "dimensionSpacePoint": {"language": "en"}, "nodeAggregateId": "sir-david-nodenborough"}, {"workspaceName": "user-ws", "dimensionSpacePoint": {"language": "en"}, "nodeAggregateId": "sir-david-nodenborough"}] |
      | newContentStreamId | "user-cs-id-rebased"                                                                                                                                                                                                                         |
    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint"

    When the command DiscardWorkspace is executed with payload:
      | Key                | Value                          |
      | workspaceName      | "user-ws"                      |
      | newContentStreamId | "user-cs-id-yet-again-rebased" |
    When I am in workspace "user-ws" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-id-yet-again-rebased;nody-mc-nodeface;{"language": "de"}
