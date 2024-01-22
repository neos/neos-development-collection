@contentrepository @adapters=DoctrineDBAL
Feature: Adjust node types with a node migration

  Background:
    Given using the following content dimensions:
      | Identifier | Values          | Generalizations      |
      | language   | mul, de, en, ch | ch->de->mul, en->mul |
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:Document': true
          'Neos.ContentRepository.Testing:OtherDocument': true

    'Neos.ContentRepository.Testing:Document': []
    'Neos.ContentRepository.Testing:OtherDocument': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"

  Scenario: Success Case
    ########################
    # SETUP
    ########################
    When the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamId | "cs-identifier"      |
    And the graph projection is fully up to date
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                                      |
      | contentStreamId     | "cs-identifier"                                                            |
      | nodeAggregateId     | "lady-eleonode-rootford"                                                   |
      | nodeTypeName                | "Neos.ContentRepository:Root"                                              |
    And the graph projection is fully up to date
    # Node /document
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"language": "de"}                        |
      | parentNodeAggregateId | "lady-eleonode-rootford"                  |
    And the graph projection is fully up to date

    ########################
    # Actual Test
    ########################
    # we remove the Document node type (which still exists in the CR)
    When I change the node types in content repository "default" to:
    """yaml
    'Neos.ContentRepository:Root':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:OtherDocument': true

    'Neos.ContentRepository.Testing:OtherDocument': []
    """
    # we should be able to rename the node type
    When I run the following node migration for workspace "live", creating content streams "migration-cs":
    """yaml
    migration:
      -
        filters:
          -
            type: 'NodeType'
            settings:
              nodeType: 'Neos.ContentRepository.Testing:Document'
        transformations:
          -
            type: 'ChangeNodeType'
            settings:
              newType: 'Neos.ContentRepository.Testing:OtherDocument'
    """
    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:Document"
    # ... also in the fallback dimension
    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:Document"

    # the node type was changed inside the new content stream
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:OtherDocument"
    # ... also in the fallback dimension
    When I am in content stream "migration-cs" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:OtherDocument"
