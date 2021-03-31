@fixtures
Feature: Node References with Dimensions

  References between nodes are created are available in specializations but not in generalizations or peer variants.

#  @todo implement scenario that verifies references not available in generalisations of the source they are created in
#  @todo implement scenario that verifies references are copied when a node specialisation is created
#  @todo implement scenario that verifies references can be overwritten in node specialisation without affecting the generalization

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values          | Generalizations      |
      | language   | mul     | mul, de, en, ch | ch->de->mul, en->mul |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:NodeWithReferences':
      properties:
        referenceProperty:
          type: reference
        referencesProperty:
          type: references
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                      | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | initiatingUserIdentifier   | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier | "cs-identifier"                        |
    And the graph projection is fully up to date
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                      | Value                                  |
      | contentStreamIdentifier  | "cs-identifier"                        |
      | nodeAggregateIdentifier  | "lady-eleonode-rootford"               |
      | nodeTypeName             | "Neos.ContentRepository:Root"          |
      | initiatingUserIdentifier | "00000000-0000-0000-0000-000000000000" |
    And the graph projection is fully up to date
    And the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                               |
      | contentStreamIdentifier       | "cs-identifier"                                     |
      | nodeAggregateIdentifier       | "source-nodandaise"                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithReferences" |
      | originDimensionSpacePoint     | {"language": "de"}                                  |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"              |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                            |
    And the graph projection is fully up to date
    And the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                               |
      | contentStreamIdentifier       | "cs-identifier"                                     |
      | nodeAggregateIdentifier       | "anthony-destinode"                                 |
      | originDimensionSpacePoint     | {"language": "de"}                                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithReferences" |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"              |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                            |
    And the graph projection is fully up to date


  Scenario: Create a reference and check whether they can be read in the different subgraphs
    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | contentStreamIdentifier             | "cs-identifier"       |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | sourceOriginDimensionSpacePoint     | {"language": "de"}    |
      | referenceName                       | "referenceProperty"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and Dimension Space Point {"language": "de"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value                 |
      | referenceProperty | ["anthony-destinode"] |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["source-nodandaise"] |

    When I am in content stream "cs-identifier" and Dimension Space Point {"language": "ch"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value                 |
      | referenceProperty | ["anthony-destinode"] |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["source-nodandaise"] |

    When I am in content stream "cs-identifier" and Dimension Space Point {"language": "en"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value |
      | referenceProperty | []    |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value |
      | referenceProperty | []    |



