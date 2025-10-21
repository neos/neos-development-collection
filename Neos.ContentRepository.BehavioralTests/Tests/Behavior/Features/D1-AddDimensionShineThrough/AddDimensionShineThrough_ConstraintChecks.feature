@contentrepository @adapters=DoctrineDBAL
Feature: Add Dimension Specialization - constraint checks

  This is needed if "de" exists, and you want to create a "de_CH" specialization:
  - there, you want to create EDGES for de_CH, without materializing NODES (so that the shine-through works as expected)

  !! Constraint: the Target Dimension Space should be empty.

  Background:
    ########################
    # SETUP
    ########################
    Given using the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | mul, de, en | de->mul         |
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
    'Neos.ContentRepository.Testing:OtherDocument': []
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
    # Node /document
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "sir-david-nodenborough"                  |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {"language": "de"}                        |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                  |
      | initialPropertyValues     | {"text": "hello" }                        |

  Scenario: Error case - there's already an edge in the target dimension in another workspace, e.g. by executing the same command
    When I change the content dimensions in content repository "default" to:
      | Identifier | Values          | Generalizations |
      | language   | mul, de, ch, en | ch->de->mul     |

    When I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs", without publishing on success:
    """yaml
    migration:
      -
        transformations:
          -
            type: 'AddDimensionShineThrough'
            settings:
              from: { language: 'de' }
              to: { language: 'ch' }
    """

    When I run the following node migration for workspace "live", creating target workspace "another-migration-workspace" on contentStreamId "another-migration-cs" and exceptions are caught:
    """yaml
    migration:
      -
        transformations:
          -
            type: 'AddDimensionShineThrough'
            settings:
              from: { language: 'de' }
              to: { language: 'ch' }
    """
    Then the last command should have thrown an exception of type "DimensionSpacePointAlreadyExists"

  Scenario: Error case - the target dimension is not configured
    When I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs" and exceptions are caught:
    """yaml
    migration:
      -
        transformations:
          -
            type: 'AddDimensionShineThrough'
            settings:
              from: { language: 'de' }
              to: { language: 'notexisting' }
    """
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"


  Scenario: Error case - the target dimension is not a specialization of the source dimension (1)
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values       | Generalizations |
      | language   | mul, de, foo | de->mul         |

    When I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs" and exceptions are caught:
    """yaml
    migration:
      -
        transformations:
          -
            type: 'AddDimensionShineThrough'
            settings:
              from: { language: 'de' }
              to: { language: 'foo' }
    """
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNoSpecialization"


  Scenario: Error case - the target dimension is not a specialization of the source dimension (2)
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values       | Generalizations   |
      | language   | mul, de, foo | de->mul, foo->mul |

    When I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs" and exceptions are caught:
    """yaml
    migration:
      -
        transformations:
          -
            type: 'AddDimensionShineThrough'
            settings:
              from: { language: 'de' }
              to: { language: 'foo' }
    """
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNoSpecialization"

  Scenario: Error case - adjusting workspace that is non-root or not immediately based on root
  This limitation is required as we validate that all workspaces except the current on is empty.
  For publishing we store the originally attempted workspace in $initialWorkspaceName as during the
  publication this workspace is allowed to contain changes. Allowing to publish adjustments through multiple workspace
  complicates things and is not desired, as they are rare fundamental changes that should be run on root or in a migration
  (sandbox) workspace which is published to root.

    Given the command CreateWorkspace is executed with payload:
      | Key                | Value                  |
      | workspaceName      | "shared"               |
      | baseWorkspaceName  | "live"                 |
      | newContentStreamId | "shared-cs-identifier" |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "shared"             |
      | newContentStreamId | "user-cs-identifier" |

    And I change the content dimensions in content repository "default" to:
      | Identifier | Values           | Generalizations       |
      | language   | mul, de, en, gsw | gsw->de->mul |

    When I run the following node migration for workspace "user-test", creating target workspace "migration-workspace-2" on contentStreamId "migration-cs-2" and exceptions are caught:
    """yaml
    migration:
      -
        transformations:
          -
            type: 'AddDimensionShineThrough'
            settings:
              from: { language: 'de' }
              to: { language: 'gsw' }
    """

    Then the last command should have thrown an exception of type "InvalidDimensionAdjustmentTargetWorkspace"

