@contentrepository @adapters=DoctrineDBAL
@flowEntities
Feature: Publish nodes partially with dimensions

  Background:
    Given using the following content dimensions:
      | Identifier | Values       | Generalizations |
      | language   | de,gsw,fr,en | gsw->de->en, fr |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:NodeWithAssetProperties':
      properties:
        text:
          type: string
        asset:
          type: Neos\Media\Domain\Model\Asset
        assets:
          type: array<Neos\Media\Domain\Model\Asset>
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |

    And I am in workspace "live"
    And I am in dimension space point {"language": "de"}
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    When an asset exists with id "asset-1"
    And an asset exists with id "asset-2"
    And an asset exists with id "asset-3"

  Scenario: Publish nodes partially from user workspace to live
    Given the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "user-cs-id"     |
    And I am in workspace "user-workspace"
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |

    Then I am in dimension space point {"language": "de"}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeName   | parentNodeAggregateId  | nodeTypeName                                           | initialPropertyValues         |
      | sir-david-nodenborough | node       | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"asset": "Asset:asset-1"}    |
      | nody-mc-nodeface       | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"assets": ["Asset:asset-2"]} |

    Then I am in dimension space point {"language": "fr"}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId             | nodeName | parentNodeAggregateId  | nodeTypeName                                           | initialPropertyValues                |
      | sir-nodeward-nodington-iii  | esquire  | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"text": "Link to asset://asset-3."} |
      | sir-nodeward-nodington-iiii | bakura   | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"text": "Text Without Asset"}       |

    And I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId            | propertyName | workspaceName  | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough     | asset        | user-workspace | {"language": "de"}        |
      | asset-2 | nody-mc-nodeface           | assets       | user-workspace | {"language": "de"}        |
      | asset-3 | sir-nodeward-nodington-iii | text         | user-workspace | {"language": "fr"}        |

    When the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                                                                                                                         |
      | nodesToPublish                  | [{"workspaceName": "user-workspace", "dimensionSpacePoint": {"language": "de"}, "nodeAggregateId": "sir-david-nodenborough"}] |
      | contentStreamIdForRemainingPart | "user-cs-identifier-remaining"                                                                                                |

    Then I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId            | propertyName | workspaceName  | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough     | asset        | live           | {"language": "de"}        |
      | asset-2 | nody-mc-nodeface           | assets       | user-workspace | {"language": "de"}        |
      | asset-3 | sir-nodeward-nodington-iii | text         | user-workspace | {"language": "fr"}        |

  Scenario: Publish nodes partially from user workspace to a non live workspace
    Given the command CreateWorkspace is executed with payload:
      | Key                | Value                    |
      | workspaceName      | "review-workspace"       |
      | baseWorkspaceName  | "live"                   |
      | newContentStreamId | "review-workspace-cs-id" |

    And the command RebaseWorkspace is executed with payload:
      | Key           | Value              |
      | workspaceName | "review-workspace" |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value                  |
      | workspaceName      | "user-workspace"       |
      | baseWorkspaceName  | "review-workspace"     |
      | newContentStreamId | "user-workspace-cs-id" |

    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |

    And I am in workspace "user-workspace"

    Then I am in dimension space point {"language": "de"}
    And  the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeName   | parentNodeAggregateId  | nodeTypeName                                           | initialPropertyValues         |
      | sir-david-nodenborough | node       | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"asset": "Asset:asset-1"}    |
      | nody-mc-nodeface       | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"assets": ["Asset:asset-2"]} |

    Then I am in dimension space point {"language": "gsw"}
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | nodeAggregateId | "nody-mc-nodeface" |
      | sourceOrigin    | {"language":"de"}  |
      | targetOrigin    | {"language":"gsw"} |
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                                          |
      | nodeAggregateId           | "nody-mc-nodeface"                             |
      | originDimensionSpacePoint | {"language":"gsw"}                             |
      | propertyValues            | {"assets": ["Asset:asset-2", "Asset:asset-1"]} |

    And I am in dimension space point {"language": "fr"}
    Then the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId             | nodeName | parentNodeAggregateId  | nodeTypeName                                           | initialPropertyValues                |
      | sir-nodeward-nodington-iii  | esquire  | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"text": "Link to asset://asset-3."} |
      | sir-nodeward-nodington-iiii | bakura   | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"text": "Text Without Asset"}       |

    And I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId            | propertyName | workspaceName  | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough     | asset        | user-workspace | {"language": "de"}        |
      | asset-2 | nody-mc-nodeface           | assets       | user-workspace | {"language": "de"}        |
      | asset-1 | nody-mc-nodeface           | assets       | user-workspace | {"language": "gsw"}       |
      | asset-2 | nody-mc-nodeface           | assets       | user-workspace | {"language": "gsw"}       |
      | asset-3 | sir-nodeward-nodington-iii | text         | user-workspace | {"language": "fr"}        |

    When the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                                                                                                                         |
      | nodesToPublish                  | [{"workspaceName": "user-workspace", "dimensionSpacePoint": {"language": "de"}, "nodeAggregateId": "sir-david-nodenborough"}] |
      | contentStreamIdForRemainingPart | "user-cs-identifier-remaining"                                                                                                |

    Then I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId            | propertyName | workspaceName    | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough     | asset        | review-workspace | {"language": "de"}        |
      | asset-2 | nody-mc-nodeface           | assets       | user-workspace   | {"language": "de"}        |
      | asset-1 | nody-mc-nodeface           | assets       | user-workspace   | {"language": "gsw"}       |
      | asset-2 | nody-mc-nodeface           | assets       | user-workspace   | {"language": "gsw"}       |
      | asset-3 | sir-nodeward-nodington-iii | text         | user-workspace   | {"language": "fr"}        |

  Scenario: Publish nodes partially from user workspace to live with new generalization
    Given the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "user-cs-id"     |
    And I am in workspace "user-workspace"
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |

    Then I am in dimension space point {"language": "de"}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId             | nodeName   | parentNodeAggregateId  | nodeTypeName                                           | initialPropertyValues                |
      | sir-david-nodenborough      | node       | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"asset": "Asset:asset-1"}           |
      | nody-mc-nodeface            | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"assets": ["Asset:asset-2"]}        |
      | sir-nodeward-nodington-iii  | esquire    | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"text": "Link to asset://asset-3."} |
      | sir-nodeward-nodington-iiii | bakura     | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"text": "Text Without Asset"}       |

    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"language":"de"}        |
      | targetOrigin    | {"language":"en"}        |

    And I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId            | propertyName | workspaceName  | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough     | asset        | user-workspace | {"language": "de"}        |
      | asset-1 | sir-david-nodenborough     | asset        | user-workspace | {"language": "en"}        |
      | asset-2 | nody-mc-nodeface           | assets       | user-workspace | {"language": "de"}        |
      | asset-3 | sir-nodeward-nodington-iii | text         | user-workspace | {"language": "de"}        |

    When the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                                                                                                                                                                                                                                                     |
      | nodesToPublish                  | [{"workspaceName": "user-workspace", "dimensionSpacePoint": {"language": "de"}, "nodeAggregateId": "sir-david-nodenborough"},{"workspaceName": "user-workspace", "dimensionSpacePoint": {"language": "en"}, "nodeAggregateId": "sir-david-nodenborough"}] |
      | contentStreamIdForRemainingPart | "user-cs-identifier-remaining"                                                                                                                                                                                                                            |

    And I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId            | propertyName | workspaceName  | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough     | asset        | live           | {"language": "de"}        |
      | asset-1 | sir-david-nodenborough     | asset        | live           | {"language": "en"}        |
      | asset-2 | nody-mc-nodeface           | assets       | user-workspace | {"language": "de"}        |
      | asset-3 | sir-nodeward-nodington-iii | text         | user-workspace | {"language": "de"}        |
