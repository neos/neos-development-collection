@contentrepository @adapters=DoctrineDBAL
Feature: Workspace publication - complex chained functionality

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
      | nodeAggregateId        | nodeTypeName                            | parentNodeAggregateId  | tetheredDescendantNodeAggregateIds | properties |
      | sir-david-nodenborough | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | {"tethered": "nodewyn-tetherton"}  |            |
      | sir-nodebelig          | Neos.ContentRepository.Testing:Content  | lady-eleonode-rootford |                                    |            |
      | nobody-node            | Neos.ContentRepository.Testing:Content  | lady-eleonode-rootford |                                    |            |
      | nody-mc-nodeface       | Neos.ContentRepository.Testing:Content  | nodewyn-tetherton      |                                    |            |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value        |
      | workspaceName      | "user-ws"    |
      | baseWorkspaceName  | "live"       |
      | newContentStreamId | "user-cs-id" |

  Scenario: Deleted nodes cannot be edited
    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value              |
      | workspaceName                | "live"             |
      | nodeAggregateId              | "sir-nodebelig"    |
      | coveredDimensionSpacePoint   | {"language": "de"} |
      | nodeVariantSelectionStrategy | "allVariants"      |

    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value              |
      | workspaceName                | "live"             |
      | nodeAggregateId              | "nobody-node"      |
      | coveredDimensionSpacePoint   | {"language": "de"} |
      | nodeVariantSelectionStrategy | "allVariants"      |

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                     |
      | workspaceName             | "user-ws"                 |
      | nodeAggregateId           | "sir-nodebelig"           |
      | originDimensionSpacePoint | {"language": "de"}        |
      | propertyValues            | {"text": "Modified text"} |

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                     |
      | workspaceName             | "user-ws"                 |
      | nodeAggregateId           | "nobody-node"             |
      | originDimensionSpacePoint | {"language": "de"}        |
      | propertyValues            | {"text": "Modified text"} |

    Then workspace user-ws has status OUTDATED

    When the command PublishIndividualNodesFromWorkspace is executed with payload and exceptions are caught:
      | Key                | Value                                                                             |
      | workspaceName      | "user-ws"                                                                         |
      | nodesToPublish     | ["sir-nodebelig"] |
      | newContentStreamId | "user-cs-id-rebased"                                                              |
    Then the last command should have thrown the WorkspaceRebaseFailed exception with:
      | SequenceNumber | Event                 | Exception                          |
      | 13             | NodePropertiesWereSet | NodeAggregateCurrentlyDoesNotExist |
      | 14             | NodePropertiesWereSet | NodeAggregateCurrentlyDoesNotExist |

  Scenario: Vary to generalization, then publish only the child node so that an exception is thrown. Ensure that the workspace recovers from this
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

    Then workspace user-ws has status UP_TO_DATE

    When the command PublishIndividualNodesFromWorkspace is executed with payload and exceptions are caught:
      | Key                | Value                                                                                                            |
      | workspaceName      | "user-ws"                                                                                                        |
      | nodesToPublish     | ["nody-mc-nodeface"] |
      | newContentStreamId | "user-cs-id-rebased"                                                                                             |
    Then the last command should have thrown the PartialWorkspaceRebaseFailed exception with:
      | SequenceNumber | Event                               | Exception                                             |
      | 13             | NodeGeneralizationVariantWasCreated | NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint |

    When the command PublishWorkspace is executed with payload:
      | Key                | Value                          |
      | workspaceName      | "user-ws"                      |
      | newContentStreamId | "user-cs-id-yet-again-rebased" |
    When I am in workspace "user-ws" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-id-yet-again-rebased;nody-mc-nodeface;{"language": "de"}

   Scenario: Publish a deletion and try to keep a move node from its descendants
     see issue: https://github.com/neos/neos-development-collection/issues/5364

     When the command MoveNodeAggregate is executed with payload:
       | Key                          | Value              |
       | workspaceName                | "user-ws"          |
       | nodeAggregateId              | "nody-mc-nodeface" |
       | dimensionSpacePoint          | {"language": "de"} |
       | newParentNodeAggregateId     | "sir-nodebelig"    |
       | relationDistributionStrategy | "gatherAll"        |

     When the command RemoveNodeAggregate is executed with payload:
       | Key                          | Value                    |
       | workspaceName                | "user-ws"                |
       | nodeAggregateId              | "sir-david-nodenborough" |
       | coveredDimensionSpacePoint   | {"language": "de"}       |
       | nodeVariantSelectionStrategy | "allVariants"            |

     Then workspace user-ws has status UP_TO_DATE

     When the command PublishIndividualNodesFromWorkspace is executed with payload and exceptions are caught:
       | Key                | Value                                                                                      |
       | workspaceName      | "user-ws"                                                                                  |
       | nodesToPublish     | ["sir-david-nodenborough"] |
       | newContentStreamId | "user-cs-id-rebased"                                                                       |
     Then the last command should have thrown the PartialWorkspaceRebaseFailed exception with:
       | SequenceNumber | Event                 | Exception                          |
       | 11             | NodeAggregateWasMoved | NodeAggregateCurrentlyDoesNotExist |
