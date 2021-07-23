@fixtures
Feature: Node References with Dimensions

  As a user of the CR I want to disable a node aggregate and expect its descendants to also be disabled.

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
      | nodeAggregateIdentifier       | "anthony-destinode"                                 |
      | originDimensionSpacePoint     | {"language": "de"}                                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithReferences" |
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

    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"language": "de"}
    And I expect this node to have the following references:
      | Key               | Value                                                                                     |
      | referenceProperty | ["cs-identifier;anthony-destinode;{"language": "de"}"] |
    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"language": "de"}
    And I expect this node to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["cs-identifier;source-nodandaise{"language": "de"}"] |

    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"language": "de"}
    And I expect this node to have the following references:
      | Key               | Value                                                                                     |
      | referenceProperty | ["cs-identifier;anthony-destinode;{"language": "de"}"] |
    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"language": "de"}
    And I expect this node to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["cs-identifier;source-nodandaise{"language": "de"}"] |

    # todo: does this case even make sense?
    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to no node
    Then I expect node aggregate identifier "anthony-destinode" to lead to no node



