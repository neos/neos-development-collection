Feature: Behavior of Node timestamp properties "created", "originalCreated", "lastModified" and "originalLastModified"

  Background:
    And using the following content dimensions:
      | Identifier | Values          | Generalizations      |
      | language   | mul, de, en, ch | ch->de->mul, en->mul |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Homepage': {}
    'Neos.ContentRepository.Testing:Page':
      properties:
        text:
          type: string
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value     |
      | workspaceName      | "live"    |
      | newContentStreamId | "cs-live" |
    Given the current date and time is "2023-03-16T12:00:00+00:00"
    And I am in workspace "live" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeName | nodeTypeName                            | parentNodeAggregateId  | initialPropertyValues | tetheredDescendantNodeAggregateIds |
      | home            | home     | Neos.ContentRepository.Testing:Homepage | lady-eleonode-rootford | {}                    | {}                                 |
      | a               | a        | Neos.ContentRepository.Testing:Page     | home                   | {"text": "a"}         | {}                                 |

  Scenario: NodePropertiesWereSet events update last modified timestamps
    And the current date and time is "2023-03-16T12:30:00+00:00"
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value             |
      | nodeAggregateId | "a"               |
      | sourceOrigin    | {"language":"de"} |
      | targetOrigin    | {"language":"ch"} |

    When the current date and time is "2023-03-16T13:00:00+00:00"
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value               |
      | workspaceName             | "live"         |
      | originDimensionSpacePoint | {"language": "ch"}  |
      | nodeAggregateId           | "a"                 |
      | propertyValues            | {"text": "Changed"} |
    And I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 12:00:00 | 2023-03-16 12:00:00 |              |                      |

    When I am in workspace "live" and dimension space point {"language":"ch"}
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified        | originalLastModified |
      | 2023-03-16 12:30:00 | 2023-03-16 12:30:00 | 2023-03-16 13:00:00 | 2023-03-16 13:00:00  |

  Scenario: Original created and last modified timestamps when publishing nodes over multiple content streams
    When the current date and time is "2023-03-16T12:30:00+00:00"
    And the command CreateWorkspace is executed with payload:
      | Key                | Value       |
      | workspaceName      | "user-test" |
      | baseWorkspaceName  | "live"      |
      | newContentStreamId | "cs-user"   |

    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                                | Value                                 |
      | workspaceName                      | "user-test"                           |
      | nodeAggregateId                    | "b"                                   |
      | nodeName                           | "b"                                   |
      | nodeTypeName                       | "Neos.ContentRepository.Testing:Page" |
      | parentNodeAggregateId              | "home"                                |
      | initialPropertyValues              | {"text": "b"}                         |
      | tetheredDescendantNodeAggregateIds | {}                                    |

    When the current date and time is "2023-03-16T13:00:00+00:00"
    And the command SetNodeProperties is executed with payload:
      | Key             | Value               |
      | workspaceName   | "user-test"         |
      | nodeAggregateId | "b"                 |
      | propertyValues  | {"text": "Changed"} |

    And I am in workspace "user-test"
    Then I expect the node "b" to have the following timestamps:
      | created             | originalCreated     | lastModified        | originalLastModified |
      | 2023-03-16 12:30:00 | 2023-03-16 12:30:00 | 2023-03-16 13:00:00 | 2023-03-16 13:00:00  |

    And the current date and time is "2023-03-16T14:00:00+00:00"
    And the command PublishWorkspace is executed with payload:
      | Key           | Value       |
      | workspaceName | "user-test" |

    And I am in workspace "user-test"
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified        | originalLastModified |
      | 2023-03-16 12:00:00 | 2023-03-16 12:00:00 |              |                      |
    And I expect the node "b" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 14:00:00 | 2023-03-16 12:30:00 | 2023-03-16 14:00:00 | 2023-03-16 13:00:00  |

    And I am in workspace "live"
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified        | originalLastModified |
      | 2023-03-16 12:00:00 | 2023-03-16 12:00:00 |              |                      |
    And I expect the node "b" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 14:00:00 | 2023-03-16 12:30:00 | 2023-03-16 14:00:00 | 2023-03-16 13:00:00  |
