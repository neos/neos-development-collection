@contentrepository @adapters=DoctrineDBAL
@flowEntities
Feature: Create node generalization variant

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
    And the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "user-cs-id"     |

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

    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | nodeName   | parentNodeAggregateId  | nodeTypeName                                           | initialPropertyValues                                   |
      | sir-david-nodenborough     | node       | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"asset": "Asset:asset-1"}                              |
      | nody-mc-nodeface           | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"assets": ["Asset:asset-2"], "asset": "Asset:asset-1"} |
      | sir-nodeward-nodington-iii | esquire    | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"text": "Link to asset://asset-3."}                    |

    Then the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |

  Scenario: Create node generalization variant of node with asset in property
    When I am in workspace "user-workspace" and dimension space point {"language":"de"}
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"language":"de"}        |
      | targetOrigin    | {"language":"en"}        |

    Then I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId            | propertyName | workspaceName  | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough     | asset        | live           | {"language": "de"}        |
      | asset-1 | sir-david-nodenborough     | asset        | user-workspace | {"language": "en"}        |
      | asset-1 | nody-mc-nodeface           | asset        | live           | {"language": "de"}        |
      | asset-2 | nody-mc-nodeface           | assets       | live           | {"language": "de"}        |
      | asset-3 | sir-nodeward-nodington-iii | text         | live           | {"language": "de"}        |

    And the command PublishWorkspace is executed with payload:
      | Key                | Value                      |
      | workspaceName      | "user-workspace"           |
      | newContentStreamId | "new-user-workspace-cs-id" |

    Then I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId            | propertyName | workspaceName | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough     | asset        | live          | {"language": "de"}        |
      | asset-1 | sir-david-nodenborough     | asset        | live          | {"language": "en"}        |
      | asset-1 | nody-mc-nodeface           | asset        | live          | {"language": "de"}        |
      | asset-2 | nody-mc-nodeface           | assets       | live          | {"language": "de"}        |
      | asset-3 | sir-nodeward-nodington-iii | text         | live          | {"language": "de"}        |