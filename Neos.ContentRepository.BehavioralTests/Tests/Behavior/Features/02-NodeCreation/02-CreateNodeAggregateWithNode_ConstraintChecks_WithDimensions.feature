@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Create node aggregate with node

  As a user of the CR I want to create a new externally referencable node aggregate of a specific type with an initial node
  in a specific dimension space point.

  This is the tale of venerable root node aggregate Lady Eleonode Rootford already persistent in the content graph
  and its soon-to-be child node aggregate Sir David Nodenborough

  Background:
    Given using the following content dimensions:
      | Identifier | Values                      | Generalizations                      |
      | example    | general, source, spec, peer | spec->source->general, peer->general |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Node':
      properties:
        postalAddress:
          type: 'Neos\ContentRepository\Core\Tests\Behavior\Fixtures\PostalAddress'
    'Neos.ContentRepository.Testing:NodeWithInvalidPropertyType':
      properties:
        postalAddress:
          type: '\I\Do\Not\Exist'
    'Neos.ContentRepository.Testing:NodeWithInvalidDefaultValue':
      properties:
        postalAddress:
          type: 'Neos\ContentRepository\Core\Tests\Behavior\Fixtures\PostalAddress'
          defaultValue:
            iDoNotExist: 'whatever'
    'Neos.ContentRepository.Testing:AbstractNode':
      abstract: true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in the active content stream of workspace "live"
    And I am in dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date

  Scenario: Try to create a node aggregate in an origin dimension space point the parent node does not cover:
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | nodeAggregateId           | "sir-david-nodenborough"              |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |
      | originDimensionSpacePoint | {"example":"spec"}                    |
    And the graph projection is fully up to date
    And the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                       | Value                                 |
      | nodeAggregateId           | "nody-mc-nodeface"                    |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "sir-david-nodenborough"              |
      | originDimensionSpacePoint | {"example":"source"}                  |
    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint"

  Scenario: Try to create a node aggregate using a name that is already partially covered by one of its siblings
    Given the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | nodeAggregateId           | "sir-david-nodenborough"              |
      | originDimensionSpacePoint | {"example":"peer"}                    |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |
      | nodeName                  | "document"                            |
    And the graph projection is fully up to date
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                       | Value                                 |
      | nodeAggregateId           | "nody-mc-nodeface"                    |
      | originDimensionSpacePoint | {"example":"source"}                  |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |
      | nodeName                  | "document"                            |

    Then the last command should have thrown an exception of type "NodeNameIsAlreadyCovered"

  Scenario: Try to create a node aggregate with a root parent and a sibling already claiming the name
    # root nodes are special in that they have the empty DSP as origin, wich may affect constraint checks
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | nodeAggregateId           | "sir-david-nodenborough"              |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |
      | originDimensionSpacePoint | {"example":"source"}                  |
      | nodeName                  | "document"                            |
    And the graph projection is fully up to date
    And the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                       | Value                                 |
      | nodeAggregateId           | "nody-mc-nodeface"                    |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |
      | originDimensionSpacePoint | {"example":"source"}                  |
      | nodeName                  | "document"                            |
    Then the last command should have thrown an exception of type "NodeNameIsAlreadyCovered"

  Scenario: Try to create a node aggregate using a name of a not yet existent, tethered child of the parent
    Given the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | nodeAggregateId           | "sir-david-nodenborough"              |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |
      | originDimensionSpacePoint | {"example":"source"}                  |
    And the graph projection is fully up to date
    Given I change the node types in content repository "default" to:
    """yaml
    'Neos.ContentRepository.Testing:LeafNode': {}
    'Neos.ContentRepository.Testing:Node':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:LeafNode'
      properties:
        postalAddress:
          type: 'Neos\ContentRepository\Core\Tests\Behavior\Fixtures\PostalAddress'
    'Neos.ContentRepository.Testing:NodeWithInvalidPropertyType':
      properties:
        postalAddress:
          type: '\I\Do\Not\Exist'
    'Neos.ContentRepository.Testing:NodeWithInvalidDefaultValue':
      properties:
        postalAddress:
          type: 'Neos\ContentRepository\Core\Tests\Behavior\Fixtures\PostalAddress'
          defaultValue:
            iDoNotExist: 'whatever'
    'Neos.ContentRepository.Testing:AbstractNode':
      abstract: true
    """
    # We don't run structure adjustments here on purpose
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                       | Value                                 |
      | nodeAggregateId           | "nody-mc-nodeface"                    |
      | nodeTypeName              | "Neos.ContentRepository.Testing:LeafNode" |
      | parentNodeAggregateId     | "sir-david-nodenborough"              |
      | originDimensionSpacePoint | {"example":"source"}                  |
      | nodeName                  | "tethered"                            |
    Then the last command should have thrown an exception of type "NodeNameIsAlreadyCovered"
