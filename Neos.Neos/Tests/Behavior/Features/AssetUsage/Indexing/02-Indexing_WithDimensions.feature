@contentrepository @adapters=DoctrineDBAL
@flowEntities
Feature: Build index for existing nodes with dimensions

  Scenario:
    Given using the following content dimensions:
      | Identifier | Values    | Generalizations |
      | language   | de,gsw,fr | gsw->de, fr     |
      | market     | EU, DE    | EU, DE->EU      |
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
    And I am in dimension space point {"language": "de", "market": "DE"}
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

    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | nodeName   | parentNodeAggregateId  | nodeTypeName                                           | initialPropertyValues                |
      | sir-david-nodenborough     | node       | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"asset": "Asset:asset-1"}           |
      | nody-mc-nodeface           | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"assets": ["Asset:asset-2"]}        |
      | sir-nodeward-nodington-ii  | curador    | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"text": "Text Without Asset"}       |
      | sir-nodeward-nodington-iii | esquire    | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"text": "Link to asset://asset-3."} |

    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                              |
      | nodeAggregateId | "sir-david-nodenborough"           |
      | sourceOrigin    | {"language":"de", "market": "DE"}  |
      | targetOrigin    | {"language":"gsw", "market": "EU"} |

    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |

    When I am in workspace "user-workspace"
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId           | nodeName | parentNodeAggregateId      | nodeTypeName                                           | initialPropertyValues          |
      | sir-nodeward-nodington-iv | bakura   | sir-nodeward-nodington-iii | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"text": "Text Without Asset"} |
      | sir-nodeward-nodington-v  | quatilde | sir-nodeward-nodington-iii | Neos.ContentRepository.Testing:NodeWithAssetProperties | {"assets": ["Asset:asset-2"]}  |

    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                               |
      | workspaceName             | "user-workspace"                    |
      | nodeAggregateId           | "sir-david-nodenborough"            |
      | originDimensionSpacePoint | {"language": "gsw", "market": "EU"} |
      | propertyValues            | {"asset": "Asset:asset-2"}          |

    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                              |
      | nodeAggregateId | "nody-mc-nodeface"                 |
      | sourceOrigin    | {"language":"de", "market": "DE"}  |
      | targetOrigin    | {"language":"gsw", "market": "EU"} |

    Then I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId            | propertyName | workspaceName  | originDimensionSpacePoint           |
      | asset-1 | sir-david-nodenborough     | asset        | live           | {"language": "de", "market": "DE"}  |
      | asset-1 | sir-david-nodenborough     | asset        | live           | {"language": "gsw", "market": "EU"} |
      | asset-2 | nody-mc-nodeface           | assets       | live           | {"language": "de", "market": "DE"}  |
      | asset-3 | sir-nodeward-nodington-iii | text         | live           | {"language": "de", "market": "DE"}  |
      | asset-2 | sir-nodeward-nodington-v   | assets       | user-workspace | {"language": "de", "market": "DE"}  |
      | asset-2 | sir-david-nodenborough     | asset        | user-workspace | {"language": "gsw", "market": "EU"} |
      | asset-2 | nody-mc-nodeface           | assets       | user-workspace | {"language": "gsw", "market": "EU"} |

    When I run the AssetUsageIndexingProcessor with rootNodeTypeName "Neos.ContentRepository:Root"

    Then I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId            | propertyName | workspaceName  | originDimensionSpacePoint           |
      | asset-1 | sir-david-nodenborough     | asset        | live           | {"language": "de", "market": "DE"}  |
      | asset-1 | sir-david-nodenborough     | asset        | live           | {"language": "gsw", "market": "EU"} |
      | asset-2 | nody-mc-nodeface           | assets       | live           | {"language": "de", "market": "DE"}  |
      | asset-3 | sir-nodeward-nodington-iii | text         | live           | {"language": "de", "market": "DE"}  |
      | asset-2 | sir-nodeward-nodington-v   | assets       | user-workspace | {"language": "de", "market": "DE"}  |
      | asset-2 | sir-david-nodenborough     | asset        | user-workspace | {"language": "gsw", "market": "EU"} |
      | asset-2 | nody-mc-nodeface           | assets       | user-workspace | {"language": "gsw", "market": "EU"} |
