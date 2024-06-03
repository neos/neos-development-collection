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
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    # Node /document
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "sir-david-nodenborough"                  |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {"language": "de"}                        |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                  |

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
    When I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs", without publishing on success:
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
    When I am in workspace "live" and dimension space point {"language": "de"}
    Then I expect a node identified by cs-identifier;sir-david-nodenborough;{"language": "de"} to exist in the content graph
    And I expect this node to be of type "Neos.ContentRepository.Testing:Document"
    # ... also in the fallback dimension
    When I am in workspace "live" and dimension space point {"language": "ch"}
    Then I expect a node identified by cs-identifier;sir-david-nodenborough;{"language": "de"} to exist in the content graph
    And I expect this node to be of type "Neos.ContentRepository.Testing:Document"

    # the node type was changed inside the new content stream
    When I am in workspace "migration-workspace" and dimension space point {"language": "de"}
    Then I expect a node identified by migration-cs;sir-david-nodenborough;{"language": "de"} to exist in the content graph
    And I expect this node to be of type "Neos.ContentRepository.Testing:OtherDocument"
    # ... also in the fallback dimension
    When I am in workspace "migration-workspace" and dimension space point {"language": "ch"}
    Then I expect a node identified by migration-cs;sir-david-nodenborough;{"language": "de"} to exist in the content graph
    And I expect this node to be of type "Neos.ContentRepository.Testing:OtherDocument"
