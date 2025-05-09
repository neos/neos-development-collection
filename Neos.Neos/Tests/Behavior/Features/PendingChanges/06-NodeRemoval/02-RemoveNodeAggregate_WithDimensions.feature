@contentrepository @adapters=DoctrineDBAL
@flowEntities
Feature: Hard remove node aggregate with node
  Nodes in non-live workspace must be soft removed to be properly publishable via the neos ui

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
      | site-two-document         | root                   | Neos.Neos:Test.DocumentType | {}                                                          | { "main": "main-4"}                |

    And the command CreateNodeVariant is executed with payload:
      | Key             | Value             |
      | nodeAggregateId | "site"            |
      | sourceOrigin    | {"language":"de"} |
      | targetOrigin    | {"language":"fr"} |

    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"language":"de"}        |
      | targetOrigin    | {"language":"fr"}        |

    And the command CreateNodeVariant is executed with payload:
      | Key             | Value             |
      | nodeAggregateId | "davids-child"    |
      | sourceOrigin    | {"language":"de"} |
      | targetOrigin    | {"language":"fr"} |

    Then the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "user-cs-id"     |
    And I am in workspace "user-workspace"
    And I am in dimension space point {"language": "de"}

  Scenario: Remove node aggregate in live workspace
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "live"               |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"language": "de"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    Then I expect to have no changes in workspace "live"
    And I expect to have no changes in workspace "user-workspace"

  Scenario: Remove a document in a user-workspace attempt to publish this document
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "user-workspace"     |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"language": "de"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId  | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | nody-mc-nodeface | 0       | 0       | 0     | 1       | {"language": "gsw"}       |
    And I expect to have no changes in workspace "live"

    Then I expect the publishing of document "nody-mc-nodeface" from workspace "user-workspace" to fail
    Then an exception of type NodeAggregateCurrentlyDoesNotExist should be thrown with code 1710967964

  Scenario: Remove node aggregate in user-workspace attempt to publish the site
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "user-workspace"     |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"language": "de"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId  | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | nody-mc-nodeface | 0       | 0       | 0     | 1       | {"language": "gsw"}       |

    And I expect to have no changes in workspace "live"

    Then I expect the publishing of site "site" from workspace "user-workspace" to fail
    Then an exception of type "InvalidArgumentException" should be thrown with message:
    """
    The command "PublishIndividualNodesFromWorkspace" for workspace user-workspace must contain nodes to publish
    """

  Scenario: Remove node aggregate in user workspace which was already modified with "allSpecializations"
    Given the command SetNodeProperties is executed with payload:
      | Key                       | Value                  |
      | workspaceName             | "user-workspace"       |
      | nodeAggregateId           | "davids-child"         |
      | originDimensionSpacePoint | {"language": "de"}     |
      | propertyValues            | {"text": "Other text"} |

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                  |
      | workspaceName             | "user-workspace"       |
      | nodeAggregateId           | "davids-child"         |
      | originDimensionSpacePoint | {"language": "fr"}     |
      | propertyValues            | {"text": "Other text"} |

    And the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "user-workspace"     |
      | nodeAggregateId              | "davids-child"       |
      | coveredDimensionSpacePoint   | {"language": "de"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId | created | changed | moved | deleted | originDimensionSpacePoint |
      # changed 1 disappears which is not okay
      | davids-child    | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | davids-child    | 0       | 0       | 0     | 1       | {"language": "gsw"}       |
      | davids-child    | 0       | 1       | 0     | 0       | {"language": "fr"}        |
    And I expect to have no changes in workspace "live"

    When I publish the 1 changes in site "site" from workspace "user-workspace" to "live"
    Then I expect that the following node events have been published
      | type                    | event payload                                                                                                    |
      | NodePropertiesWereSet   | {"nodeAggregateId":"davids-child","originDimensionSpacePoint":{"language":"de"}}                                 |
      | NodePropertiesWereSet   | {"nodeAggregateId":"davids-child","originDimensionSpacePoint":{"language":"fr"}}                                 |
      | NodeAggregateWasRemoved | {"nodeAggregateId":"davids-child","affectedCoveredDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |

    Then I expect that the following node events are kept as remainder
      | type | event payload |

  Scenario: Publishing a document ignores all hard removals on that document
    # some change to allow publication at all
    Given the command SetNodeProperties is executed with payload:
      | Key                       | Value                    |
      | workspaceName             | "user-workspace"         |
      | nodeAggregateId           | "sir-david-nodenborough" |
      | originDimensionSpacePoint | {"language": "de"}       |
      | propertyValues            | {"title": "Other text"}  |

    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "user-workspace"     |
      | nodeAggregateId              | "davids-child"       |
      | coveredDimensionSpacePoint   | {"language": "de"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId        | created | changed | moved | deleted | originDimensionSpacePoint |
      | davids-child           | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | davids-child           | 0       | 0       | 0     | 1       | {"language": "gsw"}       |
      | sir-david-nodenborough | 0       | 1       | 0     | 0       | {"language": "de"}        |
    And I expect to have no changes in workspace "live"

    When I publish the 1 changes in document "sir-david-nodenborough" from workspace "user-workspace" to "live"
    Then I expect that the following node events have been published
      | type                  | event payload                                                                              |
      | NodePropertiesWereSet | {"nodeAggregateId":"sir-david-nodenborough","originDimensionSpacePoint":{"language":"de"}} |

    Then I expect that the following node events are kept as remainder
      | type                    | event payload                                                                                                    |
      | NodeAggregateWasRemoved | {"nodeAggregateId":"davids-child","affectedCoveredDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |

  Scenario: Publishing a site ignores all hard removals on that site
    # some change to allow publication at all
    Given the command SetNodeProperties is executed with payload:
      | Key                       | Value                  |
      | workspaceName             | "user-workspace"       |
      | nodeAggregateId           | "davids-child"         |
      | originDimensionSpacePoint | {"language": "de"}     |
      | propertyValues            | {"text": "Other text"} |

    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "user-workspace"     |
      | nodeAggregateId              | "site-two-document"  |
      | coveredDimensionSpacePoint   | {"language": "de"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "user-workspace"     |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"language": "de"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId   | created | changed | moved | deleted | originDimensionSpacePoint |
      | davids-child      | 0       | 1       | 0     | 0       | {"language": "de"}        |
      | site-two-document | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | site-two-document | 0       | 0       | 0     | 1       | {"language": "gsw"}       |
      | nody-mc-nodeface  | 0       | 0       | 0     | 1       | {"language": "de"}        |
      | nody-mc-nodeface  | 0       | 0       | 0     | 1       | {"language": "gsw"}       |
    And I expect to have no changes in workspace "live"

    When I publish the 1 changes in site "site" from workspace "user-workspace" to "live"
    Then I expect that the following node events have been published
      | type                  | event payload                                                                    |
      | NodePropertiesWereSet | {"nodeAggregateId":"davids-child","originDimensionSpacePoint":{"language":"de"}} |

    Then I expect that the following node events are kept as remainder
      | type                    | event payload                                                                                                         |
      | NodeAggregateWasRemoved | {"nodeAggregateId":"site-two-document","affectedCoveredDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]} |
      | NodeAggregateWasRemoved | {"nodeAggregateId":"nody-mc-nodeface","affectedCoveredDimensionSpacePoints":[{"language":"de"}, {"language":"gsw"}]}  |
