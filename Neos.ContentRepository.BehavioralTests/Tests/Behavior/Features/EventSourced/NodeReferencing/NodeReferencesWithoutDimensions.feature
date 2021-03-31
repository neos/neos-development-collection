@fixtures
Feature: Node References without Dimensions

  References between nodes can be created, overwritten, reordered and deleted

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:NodeWithReferences':
      properties:
        referenceProperty:
          type: reference
        referencesProperty:
          type: references
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | initiatingUserIdentifier   | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier | "cs-identifier"                        |
    And the graph projection is fully up to date
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                      | Value                                  |
      | contentStreamIdentifier  | "cs-identifier"                        |
      | nodeAggregateIdentifier  | "lady-eleonode-rootford"               |
      | nodeTypeName             | "Neos.ContentRepository:Root"          |
      | initiatingUserIdentifier | "00000000-0000-0000-0000-000000000000" |
    And the graph projection is fully up to date
    And the following intermediary CreateNodeAggregateWithNode commands are executed for content stream "cs-identifier" and origin "{}":
      | nodeAggregateIdentifier | parentNodeAggregateIdentifier | nodeTypeName                                      |
      | source-nodandaise       | lady-eleonode-rootford        | Neos.ContentRepository.Testing:NodeWithReferences |
      | anthony-destinode       | lady-eleonode-rootford        | Neos.ContentRepository.Testing:NodeWithReferences |
      | berta-destinode         | lady-eleonode-rootford        | Neos.ContentRepository.Testing:NodeWithReferences |
      | carl-destinode          | lady-eleonode-rootford        | Neos.ContentRepository.Testing:NodeWithReferences |

  Scenario: Ensure that a reference between nodes can be set and read
    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | contentStreamIdentifier             | "cs-identifier"       |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
      | referenceName                       | "referenceProperty"   |

    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}

    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value                 |
      | referenceProperty | ["anthony-destinode"] |

    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["source-nodandaise"] |

  Scenario: Ensure that references between nodes can be set and red

    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                                 |
      | contentStreamIdentifier             | "cs-identifier"                       |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"                   |
      | destinationNodeAggregateIdentifiers | ["berta-destinode", "carl-destinode"] |
      | referenceName                       | "referencesProperty"                  |

    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}

    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key                | Value                                 |
      | referencesProperty | ["berta-destinode", "carl-destinode"] |

    And I expect the node aggregate "berta-destinode" to be referenced by:
      | Key                | Value                 |
      | referencesProperty | ["source-nodandaise"] |

    And I expect the node aggregate "carl-destinode" to be referenced by:
      | Key                | Value                 |
      | referencesProperty | ["source-nodandaise"] |

  Scenario: Ensure that references between nodes can be set and overwritten

    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                                 |
      | contentStreamIdentifier             | "cs-identifier"                       |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"                   |
      | destinationNodeAggregateIdentifiers | ["berta-destinode", "carl-destinode"] |
      | referenceName                       | "referencesProperty"                  |

    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}

    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key                | Value                                 |
      | referencesProperty | ["berta-destinode", "carl-destinode"] |

    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | contentStreamIdentifier             | "cs-identifier"       |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
      | referenceName                       | "referencesProperty"  |

    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}

    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key                | Value                 |
      | referencesProperty | ["anthony-destinode"] |

  Scenario: Ensure that references between nodes can be set and reordered

    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                                 |
      | contentStreamIdentifier             | "cs-identifier"                       |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"                   |
      | destinationNodeAggregateIdentifiers | ["berta-destinode", "carl-destinode"] |
      | referenceName                       | "referencesProperty"                  |

    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}

    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key                | Value                                 |
      | referencesProperty | ["berta-destinode", "carl-destinode"] |

    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                                 |
      | contentStreamIdentifier             | "cs-identifier"                       |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"                   |
      | destinationNodeAggregateIdentifiers | ["carl-destinode", "berta-destinode"] |
      | referenceName                       | "referencesProperty"                  |

    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}

    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key                | Value                                 |
      | referencesProperty | ["carl-destinode", "berta-destinode"] |

  Scenario: Ensure that references between nodes can be deleted

    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                                 |
      | contentStreamIdentifier             | "cs-identifier"                       |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"                   |
      | destinationNodeAggregateIdentifiers | ["berta-destinode", "carl-destinode"] |
      | referenceName                       | "referencesProperty"                  |

    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}

    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key                | Value                                 |
      | referencesProperty | ["berta-destinode", "carl-destinode"] |

    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                |
      | contentStreamIdentifier             | "cs-identifier"      |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"  |
      | destinationNodeAggregateIdentifiers | []                   |
      | referenceName                       | "referencesProperty" |

    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}

    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key                | Value |
      | referencesProperty | []    |

  Scenario: Ensure that references from multiple nodes read from the opposing side

    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | contentStreamIdentifier             | "cs-identifier"       |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
      | referenceName                       | "referenceProperty"   |

    And the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | contentStreamIdentifier             | "cs-identifier"       |
      | sourceNodeAggregateIdentifier       | "berta-destinode"     |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
      | referenceName                       | "referenceProperty"   |

    And the graph projection is fully up to date

    And I am in content stream "cs-identifier" and Dimension Space Point {}

    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value                                    |
      | referenceProperty | ["source-nodandaise", "berta-destinode"] |

