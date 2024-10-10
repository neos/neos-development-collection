@contentrepository @adapters=DoctrineDBAL
@flowEntities
Feature: Publish nodes partially without dimensions

  Background:
    Given using no content dimensions
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
    And I am in dimension space point {}
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    When an asset exists with id "asset-1"
    And an asset exists with id "asset-2"
    And an asset exists with id "asset-3"

    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeName | parentNodeAggregateId  | nodeTypeName                                           | initialPropertyValues      |
      | sir-david-nodenborough | node     | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"asset": "Asset:asset-1"} |

  Scenario: Discards nodes partially from user workspace
    Given the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "user-cs-id"     |
    And I am in workspace "user-workspace"
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    And I am in dimension space point {}

    Then the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId             | nodeName   | parentNodeAggregateId      | nodeTypeName                                           | initialPropertyValues                                    |
      | nody-mc-nodeface            | child-node | sir-david-nodenborough     | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"assets": ["Asset:asset-2"]}                            |
      | sir-nodeward-nodington-iii  | esquire    | lady-eleonode-rootford     | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"text": "Link to asset://asset-3."}                     |
      | sir-nodeward-nodington-iiii | bakura     | sir-nodeward-nodington-iii | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"asset": "Asset:asset-2", "text": "Text Without Asset"} |

    And I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId             | propertyName | workspaceName  | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough      | asset        | live           | {}                        |
      | asset-2 | nody-mc-nodeface            | assets       | user-workspace | {}                        |
      | asset-3 | sir-nodeward-nodington-iii  | text         | user-workspace | {}                        |
      | asset-2 | sir-nodeward-nodington-iiii | asset        | user-workspace | {}                        |

    When the command DiscardIndividualNodesFromWorkspace is executed with payload:
      | Key                | Value                                                                                                              |
      | workspaceName      | "user-workspace"                                                                                                   |
      | nodesToDiscard     | [{"workspaceName": "user-workspace", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-nodeward-nodington-iiii"}] |
      | newContentStreamId | "user-cs-identifier-new"                                                                                           |

    Then I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId            | propertyName | workspaceName  | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough     | asset        | live           | {}                        |
      | asset-2 | nody-mc-nodeface           | assets       | user-workspace | {}                        |
      | asset-3 | sir-nodeward-nodington-iii | text         | user-workspace | {}                        |

  Scenario: Discards multiple nodes partially from user workspace
    Given the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "user-cs-id"     |
    And I am in workspace "user-workspace"
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    And I am in dimension space point {}

    Then the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId             | nodeName   | parentNodeAggregateId      | nodeTypeName                                           | initialPropertyValues                                    |
      | nody-mc-nodeface            | child-node | sir-david-nodenborough     | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"assets": ["Asset:asset-2"]}                            |
      | sir-nodeward-nodington-iii  | esquire    | lady-eleonode-rootford     | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"text": "Link to asset://asset-3."}                     |
      | sir-nodeward-nodington-iiii | bakura     | sir-nodeward-nodington-iii | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"asset": "Asset:asset-2", "text": "Text Without Asset"} |

    And I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId             | propertyName | workspaceName  | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough      | asset        | live           | {}                        |
      | asset-2 | nody-mc-nodeface            | assets       | user-workspace | {}                        |
      | asset-3 | sir-nodeward-nodington-iii  | text         | user-workspace | {}                        |
      | asset-2 | sir-nodeward-nodington-iiii | asset        | user-workspace | {}                        |

    When the command DiscardIndividualNodesFromWorkspace is executed with payload:
      | Key                | Value                                                                                                                                                                                                                              |
      | workspaceName      | "user-workspace"                                                                                                                                                                                                                   |
      | nodesToDiscard     | [{"workspaceName": "user-workspace", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-nodeward-nodington-iii"},{"workspaceName": "user-workspace", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-nodeward-nodington-iiii"}] |
      | newContentStreamId | "user-cs-identifier-new"                                                                                                                                                                                                           |

    Then I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId        | propertyName | workspaceName  | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough | asset        | live           | {}                        |
      | asset-2 | nody-mc-nodeface       | assets       | user-workspace | {}                        |

  Scenario: Discards nodes partially from user workspace with a non-live base workspace
    Given the command CreateWorkspace is executed with payload:
      | Key                | Value                    |
      | workspaceName      | "review-workspace"       |
      | baseWorkspaceName  | "live"                   |
      | newContentStreamId | "review-workspace-cs-id" |

    And the command RebaseWorkspace is executed with payload:
      | Key           | Value              |
      | workspaceName | "review-workspace" |

    And I am in workspace "review-workspace"
    Then the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | nodeName   | parentNodeAggregateId  | nodeTypeName                                           | initialPropertyValues         |
      | nody-mc-nodeface | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"assets": ["Asset:asset-2"]} |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value                  |
      | workspaceName      | "user-workspace"       |
      | baseWorkspaceName  | "review-workspace"     |
      | newContentStreamId | "user-workspace-cs-id" |

    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |

    And I am in workspace "user-workspace"

    And I am in dimension space point {}
    Then the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId             | nodeName | parentNodeAggregateId      | nodeTypeName                                           | initialPropertyValues                                    |
      | sir-nodeward-nodington-iii  | esquire  | lady-eleonode-rootford     | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"text": "Link to asset://asset-3."}                     |
      | sir-nodeward-nodington-iiii | bakura   | sir-nodeward-nodington-iii | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"text": "Text Without Asset", "asset": "Asset:asset-1"} |

    And I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId             | propertyName | workspaceName    | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough      | asset        | live             | {}                        |
      | asset-2 | nody-mc-nodeface            | assets       | review-workspace | {}                        |
      | asset-3 | sir-nodeward-nodington-iii  | text         | user-workspace   | {}                        |
      | asset-1 | sir-nodeward-nodington-iiii | asset        | user-workspace   | {}                        |

    When the command DiscardIndividualNodesFromWorkspace is executed with payload:
      | Key                | Value                                                                                                                                                                                                                              |
      | workspaceName      | "user-workspace"                                                                                                                                                                                                                   |
      | nodesToDiscard     | [{"workspaceName": "user-workspace", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-nodeward-nodington-iiii"}] |
      | newContentStreamId | "user-cs-identifier-new"                                                                                                                                                                                                           |

    And I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId        | propertyName | workspaceName    | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough | asset        | live             | {}                        |
      | asset-2 | nody-mc-nodeface       | assets       | review-workspace | {}                        |
      | asset-3 | sir-nodeward-nodington-iii  | text         | user-workspace   | {}                        |

  Scenario: Discards multiple nodes partially from user workspace with a non-live base workspace
    Given the command CreateWorkspace is executed with payload:
      | Key                | Value                    |
      | workspaceName      | "review-workspace"       |
      | baseWorkspaceName  | "live"                   |
      | newContentStreamId | "review-workspace-cs-id" |

    And the command RebaseWorkspace is executed with payload:
      | Key           | Value              |
      | workspaceName | "review-workspace" |

    And I am in workspace "review-workspace"
    Then the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | nodeName   | parentNodeAggregateId  | nodeTypeName                                           | initialPropertyValues         |
      | nody-mc-nodeface | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"assets": ["Asset:asset-2"]} |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value                  |
      | workspaceName      | "user-workspace"       |
      | baseWorkspaceName  | "review-workspace"     |
      | newContentStreamId | "user-workspace-cs-id" |

    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |

    And I am in workspace "user-workspace"

    And I am in dimension space point {}
    Then the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId             | nodeName | parentNodeAggregateId      | nodeTypeName                                           | initialPropertyValues                                    |
      | sir-nodeward-nodington-iii  | esquire  | lady-eleonode-rootford     | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"text": "Link to asset://asset-3."}                     |
      | sir-nodeward-nodington-iiii | bakura   | sir-nodeward-nodington-iii | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"text": "Text Without Asset", "asset": "Asset:asset-1"} |

    And I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId             | propertyName | workspaceName    | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough      | asset        | live             | {}                        |
      | asset-2 | nody-mc-nodeface            | assets       | review-workspace | {}                        |
      | asset-3 | sir-nodeward-nodington-iii  | text         | user-workspace   | {}                        |
      | asset-1 | sir-nodeward-nodington-iiii | asset        | user-workspace   | {}                        |

    When the command DiscardIndividualNodesFromWorkspace is executed with payload:
      | Key                | Value                                                                                                                                                                                                                              |
      | workspaceName      | "user-workspace"                                                                                                                                                                                                                   |
      | nodesToDiscard     | [{"workspaceName": "user-workspace", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-nodeward-nodington-iii"},{"workspaceName": "user-workspace", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-nodeward-nodington-iiii"}] |
      | newContentStreamId | "user-cs-identifier-new"                                                                                                                                                                                                           |

    And I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId        | propertyName | workspaceName    | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough | asset        | live             | {}                        |
      | asset-2 | nody-mc-nodeface       | assets       | review-workspace | {}                        |

