@contentrepository @adapters=DoctrineDBAL
Feature: Move dimension space point

  basically "renames" a dimension space point; needed if:
  - the dimension value should be changed: {language: de} -> {language: de_DE}
  - there were no dimensions beforehand, and now there are: {} -> {language: de}
  - ... or the opposite: {language: de} -> {}
  - new dimensions are introduced; so the existing DimensionSpacePoints need an additional value.

  !! Constraint: the Target Dimension Space must be empty.

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
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId                  | nodeTypeName                            | parentNodeAggregateId  | nodeName                     | originDimensionSpacePoint |
      | sir-david-nodenborough           | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | document                     | {"language": "de"}        |
      | varied-nodenborough              | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | varied-document              | {"language": "de"}        |
      | only-specialization-nodenborough | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | only-specialization-document | {"language": "ch"}        |
      | only-source-nodenborough         | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | only-source-document         | {"language": "de"}        |
      | nody-mc-nodeface                 | Neos.ContentRepository.Testing:Document | varied-nodenborough    | child-document               | {"language": "de"}        |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | nodeAggregateId | "varied-nodenborough" |
      | sourceOrigin    | {"language":"de"}     |
      | targetOrigin    | {"language":"ch"}     |
    And the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                      |
      | nodeAggregateId              | "only-source-nodenborough" |
      | coveredDimensionSpacePoint   | {"language":"ch"}          |
      | nodeVariantSelectionStrategy | "allSpecializations"       |

  Scenario: Success Case - Specializations can be renamed
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values           | Generalizations       |
      | language   | mul, de, en, gsw | gsw->de->mul, en->mul |

    When I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs", without publishing on success:
    """yaml
    migration:
      -
        transformations:
          -
            type: 'MoveDimensionSpacePoint'
            settings:
              from: { language: 'ch' }
              to: { language: 'gsw' }
    """

    Then I expect the graph projection to consist of exactly 9 nodes

    # the original content stream has not been touched
    When I am in workspace "live"
    Then I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language": "de"} to exist in the content graph
    And I expect a node identified by cs-identifier;varied-nodenborough;{"language": "de"} to exist in the content graph
    And I expect a node identified by cs-identifier;varied-nodenborough;{"language": "ch"} to exist in the content graph
    And I expect a node identified by cs-identifier;only-specialization-nodenborough;{"language": "ch"} to exist in the content graph
    And I expect a node identified by cs-identifier;only-source-nodenborough;{"language": "de"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language": "de"} to exist in the content graph

    # we find the node at the new DimensionSpacePoint, but not at the old one
    When I am in workspace "migration-workspace"
    And I expect a node identified by migration-cs;varied-nodenborough;{"language": "gsw"} to exist in the content graph
    And I expect a node identified by migration-cs;only-specialization-nodenborough;{"language": "gsw"} to exist in the content graph
    When I am in dimension space point {"language": "ch"}
    Then I expect the subgraph projection to consist of exactly 0 nodes
    When I am in dimension space point {"language": "gsw"}
    Then I expect the subgraph projection to consist of exactly 5 nodes
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node migration-cs;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de"}
    And I expect node aggregate identifier "varied-nodenborough" to lead to node migration-cs;varied-nodenborough;{"language": "gsw"}
    And I expect node aggregate identifier "only-specialization-nodenborough" to lead to node migration-cs;only-specialization-nodenborough;{"language": "gsw"}
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node migration-cs;nody-mc-nodeface;{"language": "de"}

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors

  Scenario: Success Case - Generalizations can be renamed if the specialization structure is unchanged
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values             | Generalizations         |
      | language   | mul, de_DE, en, ch | ch->de_DE->mul, en->mul |

    When I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs", without publishing on success:
    """yaml
    migration:
      -
        transformations:
          -
            type: 'MoveDimensionSpacePoint'
            settings:
              from: { language: 'de' }
              to: { language: 'de_DE' }
    """

    Then I expect the graph projection to consist of exactly 11 nodes

    # the original content stream has not been touched
    When I am in workspace "live"
    Then I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language": "de"} to exist in the content graph
    And I expect a node identified by cs-identifier;varied-nodenborough;{"language": "de"} to exist in the content graph
    And I expect a node identified by cs-identifier;varied-nodenborough;{"language": "ch"} to exist in the content graph
    And I expect a node identified by cs-identifier;only-specialization-nodenborough;{"language": "ch"} to exist in the content graph
    And I expect a node identified by cs-identifier;only-source-nodenborough;{"language": "de"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language": "de"} to exist in the content graph

    # we find the node at the new DimensionSpacePoint, but not at the old one
    When I am in workspace "migration-workspace"
    And I expect a node identified by migration-cs;sir-david-nodenborough;{"language": "de_DE"} to exist in the content graph
    And I expect a node identified by migration-cs;varied-nodenborough;{"language": "de_DE"} to exist in the content graph
    And I expect a node identified by migration-cs;only-source-nodenborough;{"language": "de_DE"} to exist in the content graph
    And I expect a node identified by migration-cs;nody-mc-nodeface;{"language": "de_DE"} to exist in the content graph
    When I am in dimension space point {"language": "de"}
    Then I expect the subgraph projection to consist of exactly 0 nodes
    When I am in dimension space point {"language": "de_DE"}
    Then I expect the subgraph projection to consist of exactly 5 nodes
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node migration-cs;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de_DE"}
    And I expect node aggregate identifier "varied-nodenborough" to lead to node migration-cs;varied-nodenborough;{"language": "de_DE"}
    And I expect node aggregate identifier "only-source-nodenborough" to lead to node migration-cs;only-source-nodenborough;{"language": "de_DE"}
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node migration-cs;nody-mc-nodeface;{"language": "de_DE"}
    # the fallback still works and is adjusted to the new generalization
    When I am in dimension space point {"language": "ch"}
    Then I expect the subgraph projection to consist of exactly 5 nodes
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node migration-cs;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de_DE"}
    And I expect node aggregate identifier "varied-nodenborough" to lead to node migration-cs;varied-nodenborough;{"language": "ch"}
    And I expect node aggregate identifier "only-specialization-nodenborough" to lead to node migration-cs;only-specialization-nodenborough;{"language": "ch"}
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node migration-cs;nody-mc-nodeface;{"language": "de_DE"}

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors

  Scenario: Success Case - disabled nodes stay disabled

    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"language": "de"}       |
      | nodeVariantSelectionStrategy | "allVariants"            |

    # ensure the node is disabled
    When I am in workspace "live" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node
    When VisibilityConstraints are set to "withoutRestrictions"
    Then I expect a node identified by cs-identifier;sir-david-nodenborough;{"language": "de"} to exist in the content graph
    When VisibilityConstraints are set to "default"

    # we change the dimension configuration
    When I change the content dimensions in content repository "default" to:
      | Identifier | Values           | Generalizations       |
      | language   | mul, de, en, gsw | gsw->de->mul, en->mul |

    When I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs", without publishing on success:
    """yaml
    migration:
      -
        transformations:
          -
            type: 'MoveDimensionSpacePoint'
            settings:
              from: { language: 'ch' }
              to: { language: 'gsw' }
    """

    # the original content stream has not been touched
    When I am in workspace "live" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node
    When VisibilityConstraints are set to "withoutRestrictions"
    Then I expect a node identified by cs-identifier;sir-david-nodenborough;{"language": "de"} to exist in the content graph
    When VisibilityConstraints are set to "default"

    # The subtree tags were modified
    When I am in workspace "migration-workspace" and dimension space point {"language": "gsw"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node
    When VisibilityConstraints are set to "withoutRestrictions"
    Then I expect a node identified by migration-cs;sir-david-nodenborough;{"language": "de"} to exist in the content graph
    When VisibilityConstraints are set to "default"

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors

  Scenario: Success Case - other migrations do not block this with changes on this workspace

    Given I change the node types in content repository "default" to:
    """yaml
    'Neos.ContentRepository:Root':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:OtherDocument': true

    'Neos.ContentRepository.Testing:OtherDocument': []
    """

    And I change the content dimensions in content repository "default" to:
      | Identifier | Values           | Generalizations       |
      | language   | mul, de, en, gsw | gsw->de->mul, en->mul |

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
      -
        transformations:
          -
            type: 'MoveDimensionSpacePoint'
            settings:
              from: { language: 'ch' }
              to: { language: 'gsw' }
    """
    # the original content stream has not been touched
    When I am in workspace "live" and dimension space point {"language": "ch"}
    Then I expect a node identified by cs-identifier;sir-david-nodenborough;{"language": "de"} to exist in the content graph
    And I expect this node to be of type "Neos.ContentRepository.Testing:Document"

    # we find the node underneath the new DimensionSpacePoint, but not underneath the old.
    When I am in workspace "migration-workspace" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node
    When I am in workspace "migration-workspace" and dimension space point {"language": "gsw"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:OtherDocument"

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors

  Scenario: Success Case - after eliminating all fallbacks via variation, a generalization can be moved while changing the specialization structure
    # Eliminate all fallbacks by varying the nodes
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"language":"de"}        |
      | targetOrigin    | {"language":"ch"}        |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | nodeAggregateId | "nody-mc-nodeface" |
      | sourceOrigin    | {"language":"de"}  |
      | targetOrigin    | {"language":"ch"}  |
    And I change the content dimensions in content repository "default" to:
      | Identifier | Values           | Generalizations            |
      | language   | mul, en, ch, gsw | ch->mul, gsw->mul, en->mul |
    And I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs", without publishing on success:
    """yaml
    migration:
      -
        transformations:
          -
            type: 'MoveDimensionSpacePoint'
            settings:
              from: { language: 'de' }
              to: { language: 'gsw' }
    """

    Then I expect the graph projection to consist of exactly 13 nodes

    # the original content stream has not been touched
    When I am in workspace "live"
    Then I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language": "de"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language": "ch"} to exist in the content graph
    And I expect a node identified by cs-identifier;varied-nodenborough;{"language": "de"} to exist in the content graph
    And I expect a node identified by cs-identifier;varied-nodenborough;{"language": "ch"} to exist in the content graph
    And I expect a node identified by cs-identifier;only-specialization-nodenborough;{"language": "ch"} to exist in the content graph
    And I expect a node identified by cs-identifier;only-source-nodenborough;{"language": "de"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language": "de"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language": "ch"} to exist in the content graph

    # we find the node at the new DimensionSpacePoint, but not at the old one
    When I am in workspace "migration-workspace"
    And I expect a node identified by migration-cs;sir-david-nodenborough;{"language": "gsw"} to exist in the content graph
    And I expect a node identified by migration-cs;varied-nodenborough;{"language": "gsw"} to exist in the content graph
    And I expect a node identified by migration-cs;only-source-nodenborough;{"language": "gsw"} to exist in the content graph
    And I expect a node identified by migration-cs;nody-mc-nodeface;{"language": "gsw"} to exist in the content graph

    When I am in dimension space point {"language": "de"}
    Then I expect the subgraph projection to consist of exactly 0 nodes

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors

    When the command PublishWorkspace is executed with payload:
      | Key           | Value                 |
      | workspaceName | "migration-workspace" |

    Then I expect the graph projection to consist of exactly 9 nodes
    # the live workspace is now up-to-date
    When I am in workspace "live"
    Then I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language": "gsw"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language": "ch"} to exist in the content graph
    And I expect a node identified by cs-identifier;varied-nodenborough;{"language": "gsw"} to exist in the content graph
    And I expect a node identified by cs-identifier;varied-nodenborough;{"language": "ch"} to exist in the content graph
    And I expect a node identified by cs-identifier;only-specialization-nodenborough;{"language": "ch"} to exist in the content graph
    And I expect a node identified by cs-identifier;only-source-nodenborough;{"language": "gsw"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language": "gsw"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language": "ch"} to exist in the content graph
