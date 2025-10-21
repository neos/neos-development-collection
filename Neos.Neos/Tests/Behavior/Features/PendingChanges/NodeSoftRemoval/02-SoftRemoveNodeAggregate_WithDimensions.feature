@contentrepository @adapters=DoctrineDBAL
@flowEntities
Feature: Soft remove node aggregate with node

  Background:
    Given using the following content dimensions:
      | Identifier | Values    | Generalizations |
      | language   | de,gsw,fr | gsw->de, fr     |
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root': {}
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Site':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Document':
      properties:
        title:
          type: string
        uriPathSegment:
          type: string
    'Neos.Neos:Test.HomePage':
      superTypes:
        'Neos.Neos:Site': true
    'Neos.Neos:Test.DocumentType':
      superTypes:
        'Neos.Neos:Document': true
      childNodes:
        main:
          type: 'Neos.Neos:ContentCollection'
    'Neos.Neos:Content': {}
    'Neos.Neos:ContentCollection': {}
    'Neos.Neos:Test.ContentType':
      superTypes:
        'Neos.Neos:Content': true
      properties:
        text:
          type: string
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
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value             |
      | nodeAggregateId | "root"            |
      | nodeTypeName    | "Neos.Neos:Sites" |

    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId           | parentNodeAggregateId  | nodeTypeName                | initialPropertyValues                                       | tetheredDescendantNodeAggregateIds |
      | site                      | root                   | Neos.Neos:Test.HomePage     | {}                                                          |                                    |
      | sir-david-nodenborough    | site                   | Neos.Neos:Test.DocumentType | {}                                                          | { "main": "main-1"}                |
      | davids-child              | main-1                 | Neos.Neos:Test.ContentType  | {}                                                          |                                    |
      | nody-mc-nodeface          | sir-david-nodenborough | Neos.Neos:Test.DocumentType | {"title": "This is a text about Nody Mc Nodeface"}          | { "main": "main-2"}                |
      | sir-nodeward-nodington-iv | site                   | Neos.Neos:Test.DocumentType | {"title": "This is a text about Sir Nodeward Nodington IV"} | { "main": "main-3"}                |
      | other-removal             | main-3                 | Neos.Neos:Test.ContentType  | {}                                                          |                                    |
      | site-two                  | root                   | Neos.Neos:Test.HomePage     | {}                                                          |                                    |

    And the command CreateNodeVariant is executed with payload:
      | Key             | Value             |
      | nodeAggregateId | "site"            |
      | sourceOrigin    | {"language":"de"} |
      | targetOrigin    | {"language":"fr"} |

    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"language":"de"}        |
      | targetOrigin    | {"language":"gsw"}       |

    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"language":"de"}        |
      | targetOrigin    | {"language":"fr"}        |

    Then the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "user-cs-id"     |
    And I am in workspace "user-workspace"
    And I am in dimension space point {"language": "de"}

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "other-removal"      |
      | workspaceName                | "user-workspace"     |
      | coveredDimensionSpacePoint   | {"language": "de"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "removed"            |

  Scenario: Soft remove nodes in live workspace
    Given the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "live"                   |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"language": "de"}       |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "removed"                |

    Then I expect to have no changes in workspace "live"
    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId | created | changed | moved | deleted | originDimensionSpacePoint |
      | other-removal   | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | other-removal   | 0       | 0       | 0     | 1       | {"language": "gsw"}       |

  Scenario: Soft remove node aggregate in user-workspace with "allSpecializations"
    Given the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "user-workspace"     |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"language": "de"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "removed"            |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId  | created | changed | moved | deleted | originDimensionSpacePoint |
      # we mark the deletion across all affected dimensions https://github.com/neos/neos-development-collection/issues/5507
      | nody-mc-nodeface | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | nody-mc-nodeface | 0       | 0       | 0     | 1       | {"language": "gsw"}       |
      | other-removal    | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | other-removal    | 0       | 0       | 0     | 1       | {"language": "gsw"}       |
    And I expect to have no changes in workspace "live"

    When I publish the 1 changes in document "nody-mc-nodeface" from workspace "user-workspace" to "live"
    Then I expect that the following node events have been published
      | type             | event payload                                                                                                 |
      | SubtreeWasTagged | {"nodeAggregateId":"nody-mc-nodeface","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |
    Then I expect that the following node events are kept as remainder
      | type             | event payload                                                                                              |
      | SubtreeWasTagged | {"nodeAggregateId":"other-removal","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |

  Scenario: Soft remove and un-remove node aggregate in user-workspace
    Given the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "user-workspace"     |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"language": "de"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "removed"            |

    Given the command UntagSubtree is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "user-workspace"     |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"language": "de"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "removed"            |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId  | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface | 0       | 1       | 0     | 0       | {"language": "de"}        |
      | nody-mc-nodeface | 0       | 1       | 0     | 0       | {"language": "gsw"}       |
      | other-removal    | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | other-removal    | 0       | 0       | 0     | 1       | {"language": "gsw"}       |
    Then I expect to have no changes in workspace "live"

    When I publish the 1 changes in document "nody-mc-nodeface" from workspace "user-workspace" to "live"
    Then I expect that the following node events have been published
      | type               | event payload                                                                                                 |
      | SubtreeWasTagged   | {"nodeAggregateId":"nody-mc-nodeface","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |
      | SubtreeWasUntagged | {"nodeAggregateId":"nody-mc-nodeface","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |
    Then I expect that the following node events are kept as remainder
      | type             | event payload                                                                                              |
      | SubtreeWasTagged | {"nodeAggregateId":"other-removal","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |

  Scenario: Soft remove node aggregate with children in user workspace with "allVariants"
    Given the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "user-workspace"         |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"language": "de"}       |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "removed"                |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId        | created | changed | moved | deleted | originDimensionSpacePoint |
      | sir-david-nodenborough | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | sir-david-nodenborough | 0       | 0       | 0     | 1       | {"language": "gsw"}       |
      | sir-david-nodenborough | 0       | 0       | 0     | 1       | {"language": "fr"}        |
      | other-removal          | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | other-removal          | 0       | 0       | 0     | 1       | {"language": "gsw"}       |

    And I expect to have no changes in workspace "live"

    When I publish the 1 changes in document "sir-david-nodenborough" from workspace "user-workspace" to "live"
    Then I expect that the following node events have been published
      | type             | event payload                                                                                                                          |
      | SubtreeWasTagged | {"nodeAggregateId":"sir-david-nodenborough","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}, {"language":"fr"}]} |
    Then I expect that the following node events are kept as remainder
      | type             | event payload                                                                                              |
      | SubtreeWasTagged | {"nodeAggregateId":"other-removal","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |

  Scenario: Soft remove node aggregate in user workspace which was already modified with "allSpecializations"
    Given the command SetNodeProperties is executed with payload:
      | Key                       | Value                    |
      | workspaceName             | "user-workspace"         |
      | nodeAggregateId           | "sir-david-nodenborough" |
      | originDimensionSpacePoint | {"language": "de"}       |
      | propertyValues            | {"title": "Other text"}  |
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                    |
      | workspaceName             | "user-workspace"         |
      | nodeAggregateId           | "sir-david-nodenborough" |
      | originDimensionSpacePoint | {"language": "fr"}       |
      | propertyValues            | {"title": "Other text"}  |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "user-workspace"         |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"language": "de"}       |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "removed"                |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId        | created | changed | moved | deleted | originDimensionSpacePoint |
      | sir-david-nodenborough | 0       | 1       | 0     | 1       | {"language": "de"}        |
      | sir-david-nodenborough | 0       | 0       | 0     | 1       | {"language": "gsw"}       |
      | sir-david-nodenborough | 0       | 1       | 0     | 0       | {"language": "fr"}        |
      | other-removal          | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | other-removal          | 0       | 0       | 0     | 1       | {"language": "gsw"}       |
    And I expect to have no changes in workspace "live"

    When I publish the 1 changes in document "sir-david-nodenborough" from workspace "user-workspace" to "live"
    Then I expect that the following node events have been published
      | type                  | event payload                                                                                                       |
      | NodePropertiesWereSet | {"nodeAggregateId":"sir-david-nodenborough","affectedDimensionSpacePoints":[{"language":"de"}]}                     |
      | NodePropertiesWereSet | {"nodeAggregateId":"sir-david-nodenborough","affectedDimensionSpacePoints":[{"language":"fr"}]}                     |
      | SubtreeWasTagged      | {"nodeAggregateId":"sir-david-nodenborough","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |

    Then I expect that the following node events are kept as remainder
      | type             | event payload                                                                                              |
      | SubtreeWasTagged | {"nodeAggregateId":"other-removal","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |

  Scenario: Soft remove node aggregate in user workspace which was already modified with "allVariants"
    Given the command SetNodeProperties is executed with payload:
      | Key                       | Value                    |
      | workspaceName             | "user-workspace"         |
      | nodeAggregateId           | "sir-david-nodenborough" |
      | originDimensionSpacePoint | {"language": "de"}       |
      | propertyValues            | {"title": "Other text"}  |
    And    the command SetNodeProperties is executed with payload:
      | Key                       | Value                    |
      | workspaceName             | "user-workspace"         |
      | nodeAggregateId           | "sir-david-nodenborough" |
      | originDimensionSpacePoint | {"language": "fr"}       |
      | propertyValues            | {"title": "Other text"}  |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "user-workspace"         |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"language": "de"}       |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "removed"                |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId        | created | changed | moved | deleted | originDimensionSpacePoint |
      | sir-david-nodenborough | 0       | 1       | 0     | 1       | {"language": "de"}        |
      | sir-david-nodenborough | 0       | 0       | 0     | 1       | {"language": "gsw"}       |
      | sir-david-nodenborough | 0       | 1       | 0     | 1       | {"language": "fr"}        |
      | other-removal          | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | other-removal          | 0       | 0       | 0     | 1       | {"language": "gsw"}       |

    And I expect to have no changes in workspace "live"

    When I publish the 1 changes in document "sir-david-nodenborough" from workspace "user-workspace" to "live"
    Then I expect that the following node events have been published
      | type                  | event payload                                                                                                                          |
      | NodePropertiesWereSet | {"nodeAggregateId":"sir-david-nodenborough","affectedDimensionSpacePoints":[{"language":"de"}]}                                        |
      | NodePropertiesWereSet | {"nodeAggregateId":"sir-david-nodenborough","affectedDimensionSpacePoints":[{"language":"fr"}]}                                        |
      | SubtreeWasTagged      | {"nodeAggregateId":"sir-david-nodenborough","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}, {"language":"fr"}]} |

    Then I expect that the following node events are kept as remainder
      | type             | event payload                                                                                              |
      | SubtreeWasTagged | {"nodeAggregateId":"other-removal","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |

  Scenario: Soft remove child node aggregate in user workspace via "allSpecializations"
    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "user-workspace"     |
      | nodeAggregateId              | "davids-child"       |
      | coveredDimensionSpacePoint   | {"language": "de"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "removed"            |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId | created | changed | moved | deleted | originDimensionSpacePoint |
      | davids-child    | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | davids-child    | 0       | 0       | 0     | 1       | {"language": "gsw"}       |
      | other-removal   | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | other-removal   | 0       | 0       | 0     | 1       | {"language": "gsw"}       |

    And I expect to have no changes in workspace "live"

    When I publish the 1 changes in document "sir-david-nodenborough" from workspace "user-workspace" to "live"
    Then I expect that the following node events have been published
      | type             | event payload                                                                                             |
      | SubtreeWasTagged | {"nodeAggregateId":"davids-child","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |

    Then I expect that the following node events are kept as remainder
      | type             | event payload                                                                                              |
      | SubtreeWasTagged | {"nodeAggregateId":"other-removal","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |

  Scenario: Soft remove node aggregate in user workspace with modified children contents via "allSpecializations"
    Given the command SetNodeProperties is executed with payload:
      | Key                       | Value                  |
      | workspaceName             | "user-workspace"       |
      | nodeAggregateId           | "davids-child"         |
      | originDimensionSpacePoint | {"language": "de"}     |
      | propertyValues            | {"text": "Other text"} |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "user-workspace"         |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"language": "de"}       |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "removed"                |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId        | created | changed | moved | deleted | originDimensionSpacePoint |
      | sir-david-nodenborough | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | sir-david-nodenborough | 0       | 0       | 0     | 1       | {"language": "gsw"}       |
      | davids-child           | 0       | 1       | 0     | 0       | {"language": "de"}        |
      | other-removal          | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | other-removal          | 0       | 0       | 0     | 1       | {"language": "gsw"}       |

    And I expect to have no changes in workspace "live"

    When I publish the 2 changes in document "sir-david-nodenborough" from workspace "user-workspace" to "live"
    Then I expect that the following node events have been published
      | type                  | event payload                                                                                                       |
      | NodePropertiesWereSet | {"nodeAggregateId":"davids-child","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]}           |
      | SubtreeWasTagged      | {"nodeAggregateId":"sir-david-nodenborough","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |

    Then I expect that the following node events are kept as remainder
      | type             | event payload                                                                                              |
      | SubtreeWasTagged | {"nodeAggregateId":"other-removal","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |

  Scenario: Soft remove node aggregate in user workspace with modified child documents via "allSpecializations"
    Given the command SetNodeProperties is executed with payload:
      | Key                       | Value                 |
      | workspaceName             | "user-workspace"      |
      | nodeAggregateId           | "nody-mc-nodeface"    |
      | originDimensionSpacePoint | {"language": "de"}    |
      | propertyValues            | {"title": "New text"} |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "user-workspace"         |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"language": "de"}       |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "removed"                |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId        | created | changed | moved | deleted | originDimensionSpacePoint |
      | sir-david-nodenborough | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | sir-david-nodenborough | 0       | 0       | 0     | 1       | {"language": "gsw"}       |
      | nody-mc-nodeface       | 0       | 1       | 0     | 0       | {"language": "de"}        |
      | other-removal          | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | other-removal          | 0       | 0       | 0     | 1       | {"language": "gsw"}       |

    And I expect to have no changes in workspace "live"

    When I publish the 1 changes in document "sir-david-nodenborough" from workspace "user-workspace" to "live"
    Then I expect that the following node events have been published
      | type             | event payload                                                                                                       |
      | SubtreeWasTagged | {"nodeAggregateId":"sir-david-nodenborough","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |

    Then I expect that the following node events are kept as remainder
      | type                  | event payload                                                                                                 |
      | SubtreeWasTagged      | {"nodeAggregateId":"other-removal","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]}    |
      | NodePropertiesWereSet | {"nodeAggregateId":"nody-mc-nodeface","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |

  Scenario: Soft remove node aggregate in user workspace with modified child documents via "allSpecializations" and publish site
    # other change to site 2 that must be ignored
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "user-workspace"         |
      | nodeAggregateId              | "site-two" |
      | coveredDimensionSpacePoint   | {"language": "de"}       |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "removed"                |

    Given the command SetNodeProperties is executed with payload:
      | Key                       | Value                 |
      | workspaceName             | "user-workspace"      |
      | nodeAggregateId           | "nody-mc-nodeface"    |
      | originDimensionSpacePoint | {"language": "de"}    |
      | propertyValues            | {"title": "New text"} |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "user-workspace"         |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"language": "de"}       |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "removed"                |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId        | created | changed | moved | deleted | originDimensionSpacePoint |
      | sir-david-nodenborough | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | sir-david-nodenborough | 0       | 0       | 0     | 1       | {"language": "gsw"}       |
      | nody-mc-nodeface       | 0       | 1       | 0     | 0       | {"language": "de"}        |
      | other-removal          | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | other-removal          | 0       | 0       | 0     | 1       | {"language": "gsw"}       |
      | site-two               | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | site-two               | 0       | 0       | 0     | 1       | {"language": "gsw"}       |

    And I expect to have no changes in workspace "live"

    When I publish the 3 changes in site "site" from workspace "user-workspace" to "live"
    Then I expect that the following node events have been published
      | type                  | event payload                                                                                                       |
      | SubtreeWasTagged      | {"nodeAggregateId":"other-removal","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]}          |
      | NodePropertiesWereSet | {"nodeAggregateId":"nody-mc-nodeface","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]}       |
      | SubtreeWasTagged      | {"nodeAggregateId":"sir-david-nodenborough","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |

    Then I expect that the following node events are kept as remainder
      | type                  | event payload                                                                                                 |
      | SubtreeWasTagged      | {"nodeAggregateId":"site-two","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |

  Scenario: Soft remove newly created child node aggregate in user workspace via "allSpecializations"
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                   | Value                        |
      | nodeAggregateId       | "david-datter"               |
      | nodeTypeName          | "Neos.Neos:Test.ContentType" |
      | parentNodeAggregateId | "main-1"                     |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "user-workspace"     |
      | nodeAggregateId              | "david-datter"       |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "removed"            |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId | created | changed | moved | deleted | originDimensionSpacePoint |
      | david-datter    | 1       | 1       | 0     | 1       | {"language": "de"}        |
      # we mark the deletion across all affected dimensions, but not the creation! https://github.com/neos/neos-development-collection/issues/5507
      | david-datter    | 0       | 0       | 0     | 1       | {"language": "gsw"}       |
      | other-removal   | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | other-removal   | 0       | 0       | 0     | 1       | {"language": "gsw"}       |

    And I expect to have no changes in workspace "live"

    When I publish the 1 changes in document "sir-david-nodenborough" from workspace "user-workspace" to "live"
    Then I expect that the following node events have been published
      | type                            | event payload                                                                                             |
      | NodeAggregateWithNodeWasCreated | {"nodeAggregateId":"david-datter","originDimensionSpacePoint":{"language":"de"}}                          |
      | SubtreeWasTagged                | {"nodeAggregateId":"david-datter","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |

    Then I expect that the following node events are kept as remainder
      | type             | event payload                                                                                              |
      | SubtreeWasTagged | {"nodeAggregateId":"other-removal","affectedDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |
