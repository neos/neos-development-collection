@contentrepository @adapters=DoctrineDBAL
Feature: Update root node aggregate dimensions

  Creates empty root node aggregate dimensions for each allowed dimension combination and removes them for all non-configured ones.

  Background:
    ########################
    # SETUP
    ########################
    Given using the following content dimensions:
      | Identifier | Values          | Generalizations      |
      | language   | mul, de, en, ch | ch->de->mul, en->mul |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      properties:
        title:
          type: string
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

  Scenario: Run migration after adding a new dimension value
    # we change the dimension configuration
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values              | Generalizations      |
      | language   | mul, de, en, ch, fr | ch->de->mul, en->mul |

    When I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs", without publishing on success:
    """yaml
    migration:
      -
        transformations:
          -
            type: 'UpdateRootNodeAggregateDimensions'
            settings:
              nodeType: 'Neos.ContentRepository:Root'
    """

    When I am in workspace "live"
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"language":"mul"},{"language":"de"},{"language":"en"},{"language":"ch"}]

    When I am in workspace "migration-workspace" and dimension space point {"language": "fr"}
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"language":"mul"},{"language":"de"},{"language":"en"},{"language":"ch"},{"language":"fr"}]

    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node migration-cs;lady-eleonode-rootford;{}

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors

  Scenario: Success Case - other migrations do not block this with changes on this workspace
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "sir-david-nodenborough"                  |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {"language": "de"}                        |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                  |
      | initialPropertyValues     | {"title": "Original text"}                 |

    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values              | Generalizations      |
      | language   | mul, de, en, ch, fr | ch->de->mul, en->mul |

    When I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs", with publishing on success:
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
            type: 'RemoveProperty'
            settings:
              property: 'title'
      -
        transformations:
          -
            type: 'UpdateRootNodeAggregateDimensions'
            settings:
              nodeType: 'Neos.ContentRepository:Root'
    """

    When I am in workspace "live"
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"language":"mul"},{"language":"de"},{"language":"en"},{"language":"ch"},{"language":"fr"}]

    Then I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"de"} to exist in the content graph
    And I expect this node to have no properties

  Scenario: Run migration after removing a new dimension value
    # we change the dimension configuration
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values      | Generalizations |
      | language   | mul, de, ch | ch->de->mul     |

    When I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs" and exceptions are caught:
    """yaml
    migration:
      -
        transformations:
          -
            type: 'UpdateRootNodeAggregateDimensions'
            settings:
              nodeType: 'Neos.ContentRepository:Root'
    """

    Then the last command should have thrown an exception of type "NodeMigrationRequireConfirmationException" with message:
    """
    1 warnings: commands UpdateRootNodeAggregateDimensions require confirmation: Updating the dimensions of root node lady-eleonode-rootford will remove all its descendants in dimensions [{"language":"en"}]
    """

    When I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs", with force and publishing on success:
    """yaml
    migration:
      -
        transformations:
          -
            type: 'UpdateRootNodeAggregateDimensions'
            settings:
              nodeType: 'Neos.ContentRepository:Root'
    """

    When I am in workspace "live"
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"language":"mul"},{"language":"de"},{"language":"en"},{"language":"ch"}]

    When I am in workspace "migration-workspace" and dimension space point {"language": "en"}
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"language":"mul"},{"language":"de"},{"language":"ch"}]

    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to no node

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors
