@contentrepository @adapters=DoctrineDBAL
Feature: Change Property Value across dimensions; and test DimensionSpacePoints filter

  NOTE: ChangePropertyValue is tested exhaustively in ChangePropertyValues_NoDimensions.feature; here,
  we focus more on dimension selection (using the new DimensionSpace Filter)

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
      | Key                         | Value                                                                      |
      | contentStreamId             | "cs-identifier"                                                            |
      | nodeAggregateId             | "lady-eleonode-rootford"                                                   |
      | nodeTypeName                | "Neos.ContentRepository:Root"                                              |
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


  Scenario: change materialized "de" node, should shine through in "ch", but not in "en"
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
            type: 'ChangePropertyValue'
            settings:
              property: 'text'
              newSerializedValue: 'fixed value'
    """


    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}
    And I expect this node to have the following properties:
      | Key  | Value           |
      | text | "Original text" |

    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}
    And I expect this node to have the following properties:
      | Key  | Value           |
      | text | "Original text" |

    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "en"}
    And I expect this node to have the following properties:
      | Key  | Value           |
      | text | "Original text" |


    # the node was changed inside the new content stream, but only in DE (and shined through to CH; not in EN)
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de"}
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "fixed value" |

    When I am in content stream "migration-cs" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de"}
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "fixed value" |

    When I am in content stream "migration-cs" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "en"}
    And I expect this node to have the following properties:
      | Key  | Value           |
      | text | "Original text" |

  Scenario: change materialized "de" node, should NOT shine through in "ch" (as it was materialized beforehand) (includeSpecializations = FALSE - default)
    # Node /document (in "ch")
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | contentStreamId | "cs-identifier"          |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"language":"de"}        |
      | targetOrigin    | {"language":"ch"}        |
    And the graph projection is fully up to date

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
            type: 'ChangePropertyValue'
            settings:
              property: 'text'
              newSerializedValue: 'fixed value'
    """

    # the node was changed inside the new content stream, but only in DE
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de"}
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "fixed value" |

    When I am in content stream "migration-cs" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "ch"}
    # !!! CH is still unmodified
    And I expect this node to have the following properties:
      | Key  | Value           |
      | text | "Original text" |


  Scenario: change materialized "de" node; and with includeSpecializations = TRUE, also the CH node is modified
    # Node /document (in "ch")
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | contentStreamId | "cs-identifier"          |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"language":"de"}        |
      | targetOrigin    | {"language":"ch"}        |
    And the graph projection is fully up to date

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
              includeSpecializations: true
              points:
                - {"language": "de"}
        transformations:
          -
            type: 'ChangePropertyValue'
            settings:
              property: 'text'
              newSerializedValue: 'fixed value'
    """

    # the node was changed inside the new content stream in DE and EN
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de"}
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "fixed value" |

    When I am in content stream "migration-cs" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "ch"}
    # !!! CH is modified now
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "fixed value" |


  Scenario: matching only happens based on originDimensionSpacePoint, not on visibleDimensionSpacePoints - we try to change CH, but should not see any modification (includeSpecializations = FALSE - default)
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
                # !!! CH here
                - {"language": "ch"}
        transformations:
          -
            type: 'ChangePropertyValue'
            settings:
              property: 'text'
              newSerializedValue: 'fixed value'
    """

    # neither DE or CH is modified
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de"}
    And I expect this node to have the following properties:
      | Key  | Value           |
      | text | "Original text" |

    When I am in content stream "migration-cs" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de"}
    And I expect this node to have the following properties:
      | Key  | Value           |
      | text | "Original text" |

  Scenario: matching only happens based on originDimensionSpacePoint, not on visibleDimensionSpacePoints - we try to change CH, but should not see any modification (includeSpecializations = TRUE)
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
              includeSpecializations: true
              points:
                # !!! CH here
                - {"language": "ch"}
        transformations:
          -
            type: 'ChangePropertyValue'
            settings:
              property: 'text'
              newSerializedValue: 'fixed value'
    """

    # neither DE or CH is modified
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de"}
    And I expect this node to have the following properties:
      | Key  | Value           |
      | text | "Original text" |

    When I am in content stream "migration-cs" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de"}
    And I expect this node to have the following properties:
      | Key  | Value           |
      | text | "Original text" |
