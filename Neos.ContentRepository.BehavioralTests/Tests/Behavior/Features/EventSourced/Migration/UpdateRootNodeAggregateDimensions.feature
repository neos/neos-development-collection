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
    And the command CreateRootWorkspace is executed with payload:
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

  Scenario: Run migration after removing a new dimension value
    # we change the dimension configuration
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values      | Generalizations |
      | language   | mul, de, ch | ch->de->mul     |

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

    When I am in workspace "migration-workspace" and dimension space point {"language": "en"}
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"language":"mul"},{"language":"de"},{"language":"ch"}]

    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to no node

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors

  Scenario: Run migration after renaming a new dimension value
    # we change the dimension configuration
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values      | Generalizations |
      | language   | mul, de_DE, en, ch | ch->de_DE->mul, en->mul |

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

    When I am in workspace "migration-workspace" and dimension space point {"language": "de_DE"}
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"language":"mul"},{"language":"de_DE"},{"language":"en"},{"language":"ch"}]

    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node migration-cs;lady-eleonode-rootford;{}

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors

  Scenario: Without migration, creating new nodeaggregates in new dimensionspacepoint will fail
    # we change the dimension configuration
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values              | Generalizations      |
      | language   | mul, de, en, ch, fr | ch->de->mul, en->mul |

    When I am in workspace "live" and dimension space point {"language": "fr"}
    And the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                       | Value                                     |
      | nodeAggregateId           | "sir-david-nodenborough"                  |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {"language": "fr"}                        |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                  |
    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint"

    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to no node

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors
