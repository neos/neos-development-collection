@contentrepository @adapters=DoctrineDBAL
Feature: Change property type from scalar to backed enum

  Background:
    Given using the following content dimensions:
      | Identifier | Values                      | Generalizations                      |
      | example    | general, source, peer, spec | spec->source->general, peer->general |
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:Document': true
          'Neos.ContentRepository.Testing:OtherDocument': true
    'Neos.ContentRepository.Testing:Document':
      properties:
        myString:
          type: string
        myInt:
          type: int
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                                     |
      | nodeAggregateId           | "nody-mc-nodeface"                                  |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document"                 |
      | originDimensionSpacePoint | {"example": "general"}                                    |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                                  |
      | initialPropertyValues     | {"myString": "https://schema.org/Wednesday", "myInt": 42} |
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "nody-mc-nodeface" |
      | sourceOrigin    | {"example":"general"}    |
      | targetOrigin    | {"example":"source"}     |
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "nody-mc-nodeface" |
      | sourceOrigin    | {"example":"general"}    |
      | targetOrigin    | {"example":"peer"}       |


  Scenario: Change property type from scalar to backed enum creating a new target workspace
    When I change the node types in content repository "default" to:
    """yaml
    'Neos.ContentRepository:Root':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:Document': true
          'Neos.ContentRepository.Testing:OtherDocument': true
    'Neos.ContentRepository.Testing:Document':
      properties:
        myString:
          type: \Neos\ContentRepository\Core\Tests\Behavior\Fixtures\DayOfWeek
        myInt:
          type: \Neos\ContentRepository\Core\Tests\Behavior\Fixtures\ArbitraryNumber
    """
    And I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs", without publishing on success:
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
            type: 'ChangePropertyTypeFromScalarToBackedEnum'
            settings:
              property: 'myString'
          -
            type: 'ChangePropertyTypeFromScalarToBackedEnum'
            settings:
              property: 'myInt'
    """

    # the original content stream has not been touched
    When I am in workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to have the following properties:
      | Key      | Value                        |
      | myString | https://schema.org/Wednesday |
      | myInt    | 42                           |

    When I am in workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example": "source"}
    And I expect this node to have the following properties:
      | Key      | Value                        |
      | myString | https://schema.org/Wednesday |
      | myInt    | 42                           |

    When I am in workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example": "peer"}
    And I expect this node to have the following properties:
      | Key      | Value                        |
      | myString | https://schema.org/Wednesday |
      | myInt    | 42                           |

    When I am in workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example": "source"}
    And I expect this node to have the following properties:
      | Key      | Value                        |
      | myString | https://schema.org/Wednesday |
      | myInt    | 42                           |

    # the node was changed in the new content stream, in all variants
    When I am in workspace "migration-workspace" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node migration-cs;nody-mc-nodeface;{"example": "general"}
    And I expect this node to have the following properties:
      | Key      | Value                                  |
      | myString | DayOfWeek:https://schema.org/Wednesday |
      | myInt    | ArbitraryNumber:42                     |

    When I am in workspace "migration-workspace" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node migration-cs;nody-mc-nodeface;{"example": "source"}
    And I expect this node to have the following properties:
      | Key      | Value                                  |
      | myString | DayOfWeek:https://schema.org/Wednesday |
      | myInt    | ArbitraryNumber:42                     |

    When I am in workspace "migration-workspace" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node migration-cs;nody-mc-nodeface;{"example": "peer"}
    And I expect this node to have the following properties:
      | Key      | Value                                  |
      | myString | DayOfWeek:https://schema.org/Wednesday |
      | myInt    | ArbitraryNumber:42                     |

    When I am in workspace "migration-workspace" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node migration-cs;nody-mc-nodeface;{"example": "source"}
    And I expect this node to have the following properties:
      | Key      | Value                                  |
      | myString | DayOfWeek:https://schema.org/Wednesday |
      | myInt    | ArbitraryNumber:42                     |
