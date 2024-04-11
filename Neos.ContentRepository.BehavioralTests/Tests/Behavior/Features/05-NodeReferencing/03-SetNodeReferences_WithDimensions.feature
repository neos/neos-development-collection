@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Node References with Dimensions

  As a user of the CR I want to be able to create, overwrite, reorder and delete reference between nodes

  References between nodes are created are available in specializations but not in generalizations or peer variants.

  Background:
    Given using the following content dimensions:
      | Identifier | Values          | Generalizations      |
      | language   | mul, de, en, ch | ch->de->mul, en->mul |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:NodeWithReferences':
      properties:
        referenceProperty:
          type: reference
        referencesProperty:
          type: references
        text:
          type: string
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamId | "cs-identifier"      |
    And I am in the active content stream of workspace "live" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName                                      | parentNodeAggregateId |
      | source-nodandaise       | Neos.ContentRepository.Testing:NodeWithReferences | lady-eleonode-rootford        |
      | anthony-destinode       | Neos.ContentRepository.Testing:NodeWithReferences | lady-eleonode-rootford        |

  Scenario: Create a reference and check whether they can be read in the different subgraphs
    When the command SetNodeReferences is executed with payload:
      | Key                           | Value                             |
      | sourceNodeAggregateId | "source-nodandaise"               |
      | referenceName                 | "referenceProperty"               |
      | references                    | [{"target": "anthony-destinode"}] |

    When I am in the active content stream of workspace "live" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"language": "de"}
    And I expect this node to have the following references:
      | Name              | Node                                               | Properties |
      | referenceProperty | cs-identifier;anthony-destinode;{"language": "de"} | null       |
    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"language": "de"}
    And I expect this node to be referenced by:
      | Name              | Node                                               | Properties |
      | referenceProperty | cs-identifier;source-nodandaise;{"language": "de"} | null       |

    When I am in the active content stream of workspace "live" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"language": "de"}
    And I expect this node to have the following references:
      | Name              | Node                                               | Properties |
      | referenceProperty | cs-identifier;anthony-destinode;{"language": "de"} | null       |
    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"language": "de"}
    And I expect this node to be referenced by:
      | Name              | Node                                               | Properties |
      | referenceProperty | cs-identifier;source-nodandaise;{"language": "de"} | null       |
