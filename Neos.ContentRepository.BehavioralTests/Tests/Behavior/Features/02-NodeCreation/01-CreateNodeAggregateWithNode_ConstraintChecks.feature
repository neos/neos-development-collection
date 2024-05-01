@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Create node aggregate with node

  As a user of the CR I want to create a new externally referencable node aggregate of a specific type with an initial node
  in a specific dimension space point.

  This is the tale of venerable root node aggregate Lady Eleonode Rootford already persistent in the content graph
  and its soon-to-be child node aggregate Sir David Nodenborough

  Background:
    Given using no content dimensions
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
    And I am in the active content stream of workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

  Scenario: Try to create a node aggregate in a workspace that currently does not exist:
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                   | Value                                 |
      | workspaceName         | "non-existent"                        |
      | nodeAggregateId       | "sir-david-nodenborough"              |
      | nodeTypeName          | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId | "lady-eleonode-rootford"              |
      | nodeName              | "document"                            |

    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to create a node aggregate in a workspace whose content stream is closed:
    When the command CloseContentStream is executed with payload:
      | Key             | Value           |
      | contentStreamId | "cs-identifier" |
    And the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                   | Value                                 |
      | nodeAggregateId       | "sir-david-nodenborough"              |
      | nodeTypeName          | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId | "lady-eleonode-rootford"              |
      | nodeName              | "document"                            |

    Then the last command should have thrown an exception of type "ContentStreamIsClosed"

  Scenario: Try to create a node aggregate in a workspace where it is already present:
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                   | Value                                 |
      | nodeAggregateId       | "lady-eleonode-rootford"              |
      | nodeTypeName          | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId | "lady-eleonode-rootford"              |
      | nodeName              | "document"                            |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyExists"

  Scenario: Try to create a (non-root) node aggregate of a root node type:
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                   | Value                         |
      | nodeAggregateId       | "sir-david-nodenborough"      |
      | nodeTypeName          | "Neos.ContentRepository:Root" |
      | parentNodeAggregateId | "lady-eleonode-rootford"      |
      | nodeName              | "document"                    |
    Then the last command should have thrown an exception of type "NodeTypeIsOfTypeRoot"

  Scenario: Try to create a node aggregate of a non-existing node type:
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                   | Value                                        |
      | nodeAggregateId       | "sir-david-nodenborough"                     |
      | nodeTypeName          | "Neos.ContentRepository.Testing:IDoNotExist" |
      | parentNodeAggregateId | "lady-eleonode-rootford"                     |
      | nodeName              | "document"                                   |
    Then the last command should have thrown an exception of type "NodeTypeNotFound"

  Scenario: Try to create a node aggregate of an abstract node type:
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                   | Value                                         |
      | nodeAggregateId       | "sir-david-nodenborough"                      |
      | nodeTypeName          | "Neos.ContentRepository.Testing:AbstractNode" |
      | parentNodeAggregateId | "lady-eleonode-rootford"                      |
      | nodeName              | "document"                                    |
    Then the last command should have thrown an exception of type "NodeTypeIsAbstract"

  Scenario: Try to create a node aggregate in an origin dimension space point not within the allowed dimension subspace:
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                       | Value                                 |
      | nodeAggregateId           | "sir-david-nodenborough"              |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | originDimensionSpacePoint | {"undeclared": "undefined"}           |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |
      | nodeName                  | "document"                            |

    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to create a node aggregate as a child of a non-existing parent
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                   | Value                                 |
      | nodeAggregateId       | "nody-mc-nodeface"                    |
      | nodeTypeName          | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId | "i-do-not-exist"                      |
      | nodeName              | "document"                            |

    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to create a node aggregate as a sibling of a non-existing succeeding sibling
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                              | Value                                 |
      | nodeAggregateId                  | "nody-mc-nodeface"                    |
      | nodeTypeName                     | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId            | "lady-eleonode-rootford"              |
      | succeedingSiblingNodeAggregateId | "i-do-not-exist"                      |
      | nodeName                         | "document"                            |

    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to create a node aggregate using a name that is already taken by one of its siblings
    Given the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                   | Value                                 |
      | nodeAggregateId       | "sir-david-nodenborough"              |
      | nodeTypeName          | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId | "lady-eleonode-rootford"              |
      | nodeName              | "document"                            |
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                       | Value                                 |
      | nodeAggregateId           | "nody-mc-nodeface"                    |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |
      | nodeName                  | "document"                            |

    Then the last command should have thrown an exception of type "NodeNameIsAlreadyOccupied"

  Scenario: Try to create a node aggregate with a property the node type does not declare
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                   | Value                                 |
      | nodeAggregateId       | "nody-mc-nodeface"                    |
      | nodeTypeName          | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId | "lady-eleonode-rootford"              |
      | initialPropertyValues | {"iDoNotExist": "whatever"}           |
    Then the last command should have thrown an exception of type "PropertyCannotBeSet" with code 1615664798

  Scenario: Try to create a node aggregate with a property of a wrong type
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                   | Value                                           |
      | nodeAggregateId       | "nody-mc-nodeface"                              |
      | nodeTypeName          | "Neos.ContentRepository.Testing:Node"           |
      | parentNodeAggregateId | "lady-eleonode-rootford"                        |
      | initialPropertyValues | {"postalAddress": "28 31st of February Street"} |
    Then the last command should have thrown an exception of type "PropertyCannotBeSet" with code 1615466573

  Scenario: Try to create a node aggregate with a property having an undefined type
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                   | Value                                                        |
      | nodeAggregateId       | "nody-mc-nodeface"                                           |
      | nodeTypeName          | "Neos.ContentRepository.Testing:NodeWithInvalidPropertyType" |
      | parentNodeAggregateId | "lady-eleonode-rootford"                                     |
      | initialPropertyValues | {"postalAddress": "28 31st of February Street"}              |
    Then the last command should have thrown an exception of type "PropertyTypeIsInvalid"

  Scenario: Try to create a node aggregate with a property having a wrongly declared default value
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                   | Value                                                        |
      | nodeAggregateId       | "nody-mc-nodeface"                                           |
      | nodeTypeName          | "Neos.ContentRepository.Testing:NodeWithInvalidDefaultValue" |
      | parentNodeAggregateId | "lady-eleonode-rootford"                                     |
    Then the last command should have thrown an exception of type "CallErrorException"
