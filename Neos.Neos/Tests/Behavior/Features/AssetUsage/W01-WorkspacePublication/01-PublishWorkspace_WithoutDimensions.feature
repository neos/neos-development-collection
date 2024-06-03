@contentrepository @adapters=DoctrineDBAL
@flowEntities
Feature: Publish nodes without dimensions

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
    And the command CreateWorkspace is executed with payload:
      | Key                | Value        |
      | workspaceName      | "user-test"  |
      | baseWorkspaceName  | "live"       |
      | newContentStreamId | "user-cs-id" |

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

  Scenario: Publish nodes from user workspace to live
    Given I am in workspace "user-test"
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value       |
      | workspaceName | "user-test" |
    And I am in dimension space point {}

    Then the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | nodeName   | parentNodeAggregateId  | nodeTypeName                                           | initialPropertyValues                |
      | sir-david-nodenborough     | node       | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"asset": "Asset:asset-1"}           |
      | nody-mc-nodeface           | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"assets": ["Asset:asset-2"]}        |
      | sir-nodeward-nodington-iii | esquire    | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"text": "Link to asset://asset-3."} |

    And I expect the AssetUsageProjection to have the following AssetUsages:
      | assetId | nodeAggregateId            | propertyName | workspaceName | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough     | asset        | user-test     | {}                        |
      | asset-2 | nody-mc-nodeface           | assets       | user-test     | {}                        |
      | asset-3 | sir-nodeward-nodington-iii | text         | user-test     | {}                        |

    And the command PublishWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-test"      |
      | newContentStreamId | "new-user-cs-id" |

    And I expect the AssetUsageProjection to have the following AssetUsages:
      | assetId | nodeAggregateId            | propertyName | workspaceName | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough     | asset        | live          | {}                        |
      | asset-2 | nody-mc-nodeface           | assets       | live          | {}                        |
      | asset-3 | sir-nodeward-nodington-iii | text         | live          | {}                        |