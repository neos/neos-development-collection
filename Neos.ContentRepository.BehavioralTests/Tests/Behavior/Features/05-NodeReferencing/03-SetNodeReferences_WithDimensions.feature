@fixtures @adapters=DoctrineDBAL,Postgres
Feature: Node References with Dimensions

  As a user of the CR I want to be able to create, overwrite, reorder and delete reference between nodes

  References between nodes are created are available in specializations but not in generalizations or peer variants.

  Background:
    Given I have the following content dimensions:
      | Identifier | Values          | Generalizations      |
      | language   | mul, de, en, ch | ch->de->mul, en->mul |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:NodeWithReferences':
      properties:
        referenceProperty:
          type: reference
        referencesProperty:
          type: references
        text:
          type: string
    """
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamIdentifier | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateIdentifier | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | nodeTypeName                                      | parentNodeAggregateIdentifier |
      | source-nodandaise       | Neos.ContentRepository.Testing:NodeWithReferences | lady-eleonode-rootford        |
      | anthony-destinode       | Neos.ContentRepository.Testing:NodeWithReferences | lady-eleonode-rootford        |

  Scenario: Create a reference and check whether they can be read in the different subgraphs
    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | referenceName                       | "referenceProperty"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"language": "de"}
    And I expect this node to have the following references:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;anthony-destinode;{\"language\": \"de\"}"] |
    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"language": "de"}
    And I expect this node to be referenced by:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;source-nodandaise;{\"language\": \"de\"}"] |

    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"language": "de"}
    And I expect this node to have the following references:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;anthony-destinode;{\"language\": \"de\"}"] |
    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"language": "de"}
    And I expect this node to be referenced by:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;source-nodandaise;{\"language\": \"de\"}"] |
