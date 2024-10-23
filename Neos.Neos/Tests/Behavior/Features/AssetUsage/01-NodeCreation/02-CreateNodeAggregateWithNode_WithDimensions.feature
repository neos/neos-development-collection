@contentrepository @adapters=DoctrineDBAL
@flowEntities
Feature: Create node aggregate with node with dimensions

  Background:
    Given using the following content dimensions:
      | Identifier | Values    | Generalizations |
      | language   | de,gsw,fr | gsw->de, fr     |
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

    When the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "user-cs-id"     |
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    And I am in dimension space point {"language": "de"}

  Scenario: Nodes on live workspace have been created
    Given I am in workspace "live"

    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeName   | parentNodeAggregateId  | nodeTypeName                                           | initialPropertyValues         |
      | sir-david-nodenborough | node       | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"asset": "Asset:asset-1"}    |
      | nody-mc-nodeface       | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"assets": ["Asset:asset-2"]} |

    Then I am in dimension space point {"language": "fr"}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | nodeName | parentNodeAggregateId  | nodeTypeName                                           | initialPropertyValues      |
      | sir-nodeward-nodington-iii | esquire  | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"asset": "Asset:asset-1"} |

    Then I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId            | propertyName | workspaceName | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough     | asset        | live          | {"language":"de"}         |
      | asset-2 | nody-mc-nodeface           | assets       | live          | {"language":"de"}         |
      | asset-1 | sir-nodeward-nodington-iii | asset        | live          | {"language":"fr"}         |

  Scenario: Nodes on user workspace have been created
    Given I am in workspace "user-workspace"

    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId             | nodeName   | parentNodeAggregateId  | nodeTypeName                                           | initialPropertyValues          |
      | sir-david-nodenborough      | node       | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"asset": "Asset:asset-1"}     |
      | nody-mc-nodeface            | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"assets": ["Asset:asset-2"]}  |
      | sir-nodeward-nodington-iiii | bakura     | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"text": "Text Without Asset"} |

    Then I am in dimension space point {"language": "fr"}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | nodeName | parentNodeAggregateId  | nodeTypeName                                           | initialPropertyValues      |
      | sir-nodeward-nodington-iii | esquire  | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"asset": "Asset:asset-1"} |

    Then I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId            | propertyName | workspaceName  | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough     | asset        | user-workspace | {"language":"de"}         |
      | asset-2 | nody-mc-nodeface           | assets       | user-workspace | {"language":"de"}         |
      | asset-1 | sir-nodeward-nodington-iii | asset        | user-workspace | {"language":"fr"}         |

