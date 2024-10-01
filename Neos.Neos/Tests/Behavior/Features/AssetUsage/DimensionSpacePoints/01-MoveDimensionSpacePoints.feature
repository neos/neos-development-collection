@contentrepository @adapters=DoctrineDBAL
@flowEntities
Feature: Move DimensionSpacePoints

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

    Then the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "user-cs-id"     |

    And I am in workspace "user-workspace"

    Then the command SetNodeProperties is executed with payload:
      | Key                       | Value                      |
      | workspaceName             | "user-workspace"           |
      | nodeAggregateId           | "sir-david-nodenborough"   |
      | originDimensionSpacePoint | {"language": "de"}         |
      | propertyValues            | {"asset": "Asset:asset-2"} |

    And I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId            | propertyName | workspaceName  | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough     | asset        | live           | {"language": "de"}        |
      | asset-2 | sir-david-nodenborough     | asset        | user-workspace | {"language": "de"}        |
      | asset-2 | nody-mc-nodeface           | assets       | live           | {"language": "de"}        |
      | asset-3 | sir-nodeward-nodington-iii | text         | live           | {"language": "fr"}        |

    And I am in workspace "live"


  Scenario: Rename a dimension value in live workspace
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values          | Generalizations    |
      | language   | de_DE,gsw,fr,en | gsw->de_DE->en, fr |

    And I run the following node migration for workspace "live", creating target workspace "migration-cs" on contentStreamId "migration-cs", with publishing on success:
    """yaml
    migration:
      -
        transformations:
          -
            type: 'MoveDimensionSpacePoint'
            settings:
              from: {"language":"de"}
              to: {"language":"de_DE"}
    """

    And I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId            | propertyName | workspaceName  | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough     | asset        | live           | {"language": "de_DE"}     |
      | asset-2 | nody-mc-nodeface           | assets       | live           | {"language": "de_DE"}     |
      | asset-2 | sir-david-nodenborough     | asset        | user-workspace | {"language": "de"}        |
      | asset-3 | sir-nodeward-nodington-iii | text         | live           | {"language": "fr"}        |


  Scenario: Rename a dimension value in user workspace
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values          | Generalizations    |
      | language   | de_DE,gsw,fr,en | gsw->de_DE->en, fr |

    And I run the following node migration for workspace "user-workspace", creating target workspace "migration-cs" on contentStreamId "migration-cs", with publishing on success:
    """yaml
    migration:
      -
        transformations:
          -
            type: 'MoveDimensionSpacePoint'
            settings:
              from: {"language":"de"}
              to: {"language":"de_DE"}
    """

    And I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId            | propertyName | workspaceName  | originDimensionSpacePoint |
      | asset-1 | sir-david-nodenborough     | asset        | live           | {"language": "de"}        |
      | asset-2 | sir-david-nodenborough     | asset        | user-workspace | {"language": "de_DE"}     |
      | asset-2 | nody-mc-nodeface           | assets       | live           | {"language": "de"}        |
      | asset-3 | sir-nodeward-nodington-iii | text         | live           | {"language": "fr"}        |


  Scenario: Adding a dimension in live workspace
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values       | Generalizations |
      | language   | de,gsw,fr,en | gsw->de->en, fr |
      | market     | DE, FR       | DE, FR          |

    And I run the following node migration for workspace "live", creating target workspace "migration-cs" on contentStreamId "migration-cs", with publishing on success:
    """yaml
    migration:
      -
        transformations:
          -
            type: 'MoveDimensionSpacePoint'
            settings:
              from: {"language":"de"}
              to: {"language":"de", "market": "DE"}
          -
            type: 'MoveDimensionSpacePoint'
            settings:
              from: {"language":"fr"}
              to: {"language":"fr", "market": "FR"}
    """

    And I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId            | propertyName | workspaceName  | originDimensionSpacePoint         |
      | asset-1 | sir-david-nodenborough     | asset        | live           | {"language":"de", "market": "DE"} |
      | asset-2 | nody-mc-nodeface           | assets       | live           | {"language":"de", "market": "DE"} |
      | asset-2 | sir-david-nodenborough     | asset        | user-workspace | {"language": "de"}                |
      | asset-3 | sir-nodeward-nodington-iii | text         | live           | {"language":"fr", "market": "FR"} |


  Scenario: Adding a dimension in user workspace
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values       | Generalizations |
      | language   | de,gsw,fr,en | gsw->de->en, fr |
      | market     | DE, FR       | DE, FR          |

    And I run the following node migration for workspace "user-workspace", creating target workspace "migration-cs" on contentStreamId "migration-cs", with publishing on success:
    """yaml
    migration:
      -
        transformations:
          -
            type: 'MoveDimensionSpacePoint'
            settings:
              from: {"language":"de"}
              to: {"language":"de", "market": "DE"}
          -
            type: 'MoveDimensionSpacePoint'
            settings:
              from: {"language":"fr"}
              to: {"language":"fr", "market": "FR"}
    """

    And I expect the AssetUsageService to have the following AssetUsages:
      | assetId | nodeAggregateId            | propertyName | workspaceName  | originDimensionSpacePoint         |
      | asset-1 | sir-david-nodenborough     | asset        | live           | {"language": "de"}                |
      | asset-2 | sir-david-nodenborough     | asset        | user-workspace | {"language":"de", "market": "DE"} |
      | asset-2 | nody-mc-nodeface           | assets       | live           | {"language": "de"}                |
      | asset-3 | sir-nodeward-nodington-iii | text         | live           | {"language": "fr"}                |
