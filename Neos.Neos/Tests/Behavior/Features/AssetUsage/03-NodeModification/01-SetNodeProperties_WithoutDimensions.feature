@contentrepository @adapters=DoctrineDBAL
@flowEntities
Feature: Create node aggregate with node without dimensions

  Background: Create node aggregate with initial node
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

    Then the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "user-cs-id"     |

    When an asset exists with id "asset-1"
    And an asset exists with id "asset-2"
    And an asset exists with id "asset-3"

    And I am in workspace "live"
    And I am in dimension space point {}

    And I am user identified by "initiating-user-identifier"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    Then the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId             | nodeName   | parentNodeAggregateId  | nodeTypeName                                           | initialPropertyValues                          |
      | sir-david-nodenborough      | node       | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"asset": "Asset:asset-1"}                     |
      | nody-mc-nodeface            | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"assets": ["Asset:asset-2", "Asset:asset-3"]} |
      | sir-nodeward-nodington-iii  | esquire    | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"text": "Link to asset://asset-3"}            |
      | sir-nodeward-nodington-iiii | bakura     | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"text": "Text Without Asset"}                 |

    And I am in workspace "user-workspace"

    Then the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |

    And I am in dimension space point {}

  Scenario: Set node properties without dimension and publish in user workspace
    Given the command SetNodeProperties is executed with payload:
      | Key                       | Value                      |
      | workspaceName             | "user-workspace"           |
      | nodeAggregateId           | "sir-david-nodenborough"   |
      | originDimensionSpacePoint | {}                         |
      | propertyValues            | {"asset": "Asset:asset-2"} |

    Then I expect the AssetUsageService to have the following AssetUsages:
      | nodeAggregateId            | assetId | propertyName | workspaceName  | originDimensionSpacePoint |
      | sir-david-nodenborough     | asset-1 | asset        | live           | {}                        |
      | sir-david-nodenborough     | asset-2 | asset        | user-workspace | {}                        |
      | nody-mc-nodeface           | asset-2 | assets       | live           | {}                        |
      | nody-mc-nodeface           | asset-3 | assets       | live           | {}                        |
      | sir-nodeward-nodington-iii | asset-3 | text         | live           | {}                        |

  Scenario: Remove an asset from an existing property
    Given the command SetNodeProperties is executed with payload:
      | Key                       | Value                    |
      | workspaceName             | "user-workspace"         |
      | nodeAggregateId           | "sir-david-nodenborough" |
      | originDimensionSpacePoint | {}                       |
      | propertyValues            | {"asset": null}          |

    Then I expect the AssetUsageService to have the following AssetUsages:
      | nodeAggregateId            | assetId | propertyName | workspaceName | originDimensionSpacePoint |
      | sir-david-nodenborough     | asset-1 | asset        | live          | {}                        |
      | nody-mc-nodeface           | asset-2 | assets       | live          | {}                        |
      | nody-mc-nodeface           | asset-3 | assets       | live          | {}                        |
      | sir-nodeward-nodington-iii | asset-3 | text         | live          | {}                        |

  Scenario: Remove an asset from an existing property from the live workspaces
    Given I am in workspace "live"
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                    |
      | workspaceName             | "live"                   |
      | nodeAggregateId           | "sir-david-nodenborough" |
      | originDimensionSpacePoint | {}                       |
      | propertyValues            | {"asset": null}          |

    Then I expect the AssetUsageService to have the following AssetUsages:
      | nodeAggregateId            | assetId | propertyName | workspaceName | originDimensionSpacePoint |
      | nody-mc-nodeface           | asset-2 | assets       | live          | {}                        |
      | nody-mc-nodeface           | asset-3 | assets       | live          | {}                        |
      | sir-nodeward-nodington-iii | asset-3 | text         | live          | {}                        |

  Scenario: Add an asset in a property
    Given I am in workspace "user-workspace"
    Then the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | workspaceName             | "user-workspace"             |
      | nodeAggregateId           | "sir-nodeward-nodington-iii" |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"asset": "Asset:asset-3"}   |

    Then I expect the AssetUsageService to have the following AssetUsages:
      | nodeAggregateId            | assetId | propertyName | workspaceName  | originDimensionSpacePoint |
      | sir-david-nodenborough     | asset-1 | asset        | live           | {}                        |
      | sir-nodeward-nodington-iii | asset-3 | asset        | user-workspace | {}                        |
      | nody-mc-nodeface           | asset-2 | assets       | live           | {}                        |
      | nody-mc-nodeface           | asset-3 | assets       | live           | {}                        |
      | sir-nodeward-nodington-iii | asset-3 | text         | live           | {}                        |

  Scenario: Add new asset property to the assets array
    Given I am in workspace "user-workspace"
    Then the command SetNodeProperties is executed with payload:
      | Key                       | Value                                                           |
      | workspaceName             | "user-workspace"                                                |
      | nodeAggregateId           | "nody-mc-nodeface"                                              |
      | originDimensionSpacePoint | {}                                                              |
      | propertyValues            | {"assets": ["Asset:asset-1", "Asset:asset-2", "Asset:asset-3"]} |

    Then I expect the AssetUsageService to have the following AssetUsages:
      | nodeAggregateId            | assetId | propertyName | workspaceName  | originDimensionSpacePoint |
      | sir-david-nodenborough     | asset-1 | asset        | live           | {}                        |
      | nody-mc-nodeface           | asset-2 | assets       | live           | {}                        |
      | nody-mc-nodeface           | asset-3 | assets       | live           | {}                        |
      | nody-mc-nodeface           | asset-1 | assets       | user-workspace | {}                        |
      | sir-nodeward-nodington-iii | asset-3 | text         | live           | {}                        |

  Scenario: Removes an asset entry from an assets array (no user-workspace entry, as the removal doesn't get tracked intentionally)
    Given I am in workspace "user-workspace"
    Then the command SetNodeProperties is executed with payload:
      | Key                       | Value                         |
      | workspaceName             | "user-workspace"              |
      | nodeAggregateId           | "nody-mc-nodeface"            |
      | originDimensionSpacePoint | {}                            |
      | propertyValues            | {"assets": ["Asset:asset-3"]} |

    Then I expect the AssetUsageService to have the following AssetUsages:
      | nodeAggregateId            | assetId | propertyName | workspaceName  | originDimensionSpacePoint |
      | sir-david-nodenborough     | asset-1 | asset        | live           | {}                        |
      | nody-mc-nodeface           | asset-2 | assets       | live           | {}                        |
      | nody-mc-nodeface           | asset-3 | assets       | live           | {}                        |
      | sir-nodeward-nodington-iii | asset-3 | text         | live           | {}                        |
