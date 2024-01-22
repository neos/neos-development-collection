@contentrepository @adapters=DoctrineDBAL
Feature: Remove Nodes

  Background:
    Given using the following content dimensions:
      | Identifier | Values           | Generalizations       |
      | language   | mul, de, en, gsw | gsw->de->mul, en->mul |
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:Document': true
          'Neos.ContentRepository.Testing:OtherDocument': true
    'Neos.ContentRepository.Testing:Document':
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
    And the graph projection is fully up to date
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                                       |
      | contentStreamId             | "cs-identifier"                                                             |
      | nodeAggregateId             | "lady-eleonode-rootford"                                                    |
      | nodeTypeName                | "Neos.ContentRepository:Root"                                               |
    And the graph projection is fully up to date
    # Node /document (in "de")
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | contentStreamId           | "cs-identifier"                           |
      | nodeAggregateId           | "sir-david-nodenborough"                  |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {"language": "de"}                        |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                  |
      | initialPropertyValues     | {"text": "Original text"}                 |
    And the graph projection is fully up to date

    # Node /document (in "en")
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | contentStreamId | "cs-identifier"          |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"language":"de"}        |
      | targetOrigin    | {"language":"en"}        |
    And the graph projection is fully up to date


  Scenario: Remove nodes in a given dimension space point removes the node with all virtual specializations
    When I run the following node migration for workspace "live", creating content streams "migration-cs":
    """yaml
    migration:
      -
        filters:
          -
            type: 'NodeType'
            settings:
              nodeType: 'Neos.ContentRepository.Testing:Document'
          -
            type: 'DimensionSpacePoints'
            settings:
              points:
                - {"language": "de"}
        transformations:
          -
            type: 'RemoveNode'
    """
    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "gsw"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "en"}

    # the node was removed inside the new content stream, but only in de and gsw (virtual specialization)
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I am in content stream "migration-cs" and dimension space point {"language": "gsw"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I am in content stream "migration-cs" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "en"}

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors


  Scenario: Remove nodes in a given dimension space point removes the node without shine-throughs with strategy "allSpecializations"
    When I run the following node migration for workspace "live", creating content streams "migration-cs":
    """yaml
    migration:
      -
        filters:
          -
            type: 'NodeType'
            settings:
              nodeType: 'Neos.ContentRepository.Testing:Document'
          -
            type: 'DimensionSpacePoints'
            settings:
              points:
                - {"language": "de"}
        transformations:
          -
            type: 'RemoveNode'
            settings:
              strategy: 'allSpecializations'
    """

    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "gsw"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "en"}

    # the node was removed inside the new content stream, but only in de and gsw, since it is a specialization
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I am in content stream "migration-cs" and dimension space point {"language": "gsw"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I am in content stream "migration-cs" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "en"}

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors


  Scenario: allVariants is not supported in RemoveNode, as this would violate the filter configuration potentially
    When I run the following node migration for workspace "live", creating content streams "migration-cs" and exceptions are caught:
    """yaml
    migration:
      -
        filters:
          -
            type: 'NodeType'
            settings:
              nodeType: 'Neos.ContentRepository.Testing:Document'
          -
            type: 'DimensionSpacePoints'
            settings:
              points:
                - {"language": "de"}
        transformations:
          -
            type: 'RemoveNode'
            settings:
              strategy: 'allVariants'
    """
    Then the last command should have thrown an exception of type "InvalidMigrationConfiguration"


  Scenario: Remove nodes in a virtual specialization (gsw)
    When I run the following node migration for workspace "live", creating content streams "migration-cs":
    """yaml
    migration:
      -
        filters:
          -
            type: 'NodeType'
            settings:
              nodeType: 'Neos.ContentRepository.Testing:Document'
          -
            type: 'DimensionSpacePoints'
            settings:
              points:
                - {"language": "de"}
        transformations:
          -
            type: 'RemoveNode'
            settings:
              overriddenDimensionSpacePoint: {"language": "gsw"}
    """

    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "gsw"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "en"}

    # the node was removed inside the new content stream, but only in gsw
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "migration-cs" and dimension space point {"language": "gsw"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I am in content stream "migration-cs" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "en"}

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors


  Scenario: Remove nodes in a shine-through dimension space point (gsw)
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
            type: 'RemoveNode'
            settings:
              overriddenDimensionSpacePoint: {"language": "gsw"}
    """

    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "gsw"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "en"}

    # the node was removed inside the new content stream, but only in gsw
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "migration-cs" and dimension space point {"language": "gsw"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I am in content stream "migration-cs" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "en"}

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors


  Scenario: Remove nodes in a shine-through dimension space point (DE,gsw)
    When I run the following node migration for workspace "live", creating content streams "migration-cs":
    """yaml
    migration:
      -
        filters:
          -
            type: 'NodeType'
            settings:
              nodeType: 'Neos.ContentRepository.Testing:Document'
          -
            type: 'DimensionSpacePoints'
            settings:
              points:
                - {"language": "de"}
                - {"language": "gsw"}
                - {"language": "en"}
        transformations:
          -
            type: 'RemoveNode'
    """

    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "gsw"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "en"}

    # the node was removed inside the new content stream, but only in gsw
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I am in content stream "migration-cs" and dimension space point {"language": "gsw"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I am in content stream "migration-cs" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors

  Scenario: Remove nodes in a shine-through dimension space point (DE,gsw) - variant 2
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
            type: 'RemoveNode'
    """

    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "gsw"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "en"}

    # the node was removed inside the new content stream, but only in gsw
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I am in content stream "migration-cs" and dimension space point {"language": "gsw"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I am in content stream "migration-cs" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors
