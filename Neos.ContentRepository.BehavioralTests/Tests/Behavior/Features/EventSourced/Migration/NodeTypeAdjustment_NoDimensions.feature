Feature: Adjust node types with a node migration

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:Document': true
          'Neos.ContentRepository.Testing:OtherDocument': true

    'Neos.ContentRepository.Testing:Document':
      childNodes:
        main:
          type: 'Neos.ContentRepository.Testing:ContentCollection'
          constraints:
            nodeTypes:
              'Neos.ContentRepository.Testing:OnlyInDocumentContentCollection': true
              '*': false
    'Neos.ContentRepository.Testing:OtherDocument':
      childNodes:
          main:
            type: 'Neos.ContentRepository.Testing:ContentCollection'
            constraints:
              nodeTypes:
                'Neos.ContentRepository.Testing:OnlyInDocumentContentCollection': true
                '*': false
    'Neos.ContentRepository.Testing:OnlyInDocumentContentCollection': []

    Neos.ContentRepository.Testing:ContentCollection:
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:OnlyInDocumentContentCollection': false
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"

  Scenario: Success case
    ########################
    # SETUP
    ########################
    When the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    # Node /document
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                                | Value                                     |
      | nodeAggregateId                    | "sir-david-nodenborough"                  |
      | nodeTypeName                       | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint          | {}                                        |
      | parentNodeAggregateId              | "lady-eleonode-rootford"                  |
      | tetheredDescendantNodeAggregateIds | {"main": "main-collection-id"}            |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                                            |
      | nodeAggregateId           | "sir-david-nodenborough-child"                                   |
      | nodeTypeName              | "Neos.ContentRepository.Testing:OnlyInDocumentContentCollection" |
      | originDimensionSpacePoint | {}                                                               |
      | parentNodeAggregateId     | "main-collection-id"                                             |

    ########################
    # Actual Test
    ########################
    # we remove the Document node type (which still exists in the CR)
    And I change the node types in content repository "default" to:
    """yaml
    'Neos.ContentRepository:Root':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:OtherDocument': true

    'Neos.ContentRepository.Testing:OtherDocument':
      childNodes:
          main:
            type: 'Neos.ContentRepository.Testing:ContentCollection'
            constraints:
              nodeTypes:
                'Neos.ContentRepository.Testing:OnlyInDocumentContentCollection': true
                '*': false

    'Neos.ContentRepository.Testing:OnlyInDocumentContentCollection': []

    Neos.ContentRepository.Testing:ContentCollection:
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:OnlyInDocumentContentCollection': false
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
    When I am in workspace "live" and dimension space point {}
    Then I expect a node identified by cs-identifier;sir-david-nodenborough;{} to exist in the content graph
    And I expect this node to be of type "Neos.ContentRepository.Testing:Document"

    # the node type was changed inside the new content stream
    When I am in workspace "migration-workspace" and dimension space point {}
    Then I expect a node identified by migration-cs;sir-david-nodenborough;{} to exist in the content graph
    And I expect this node to be of type "Neos.ContentRepository.Testing:OtherDocument"
