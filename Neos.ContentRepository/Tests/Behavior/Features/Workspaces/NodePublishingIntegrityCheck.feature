@fixtures
Feature: Node publishing integrity check

  Let's say we have the following node structure in the beginning:

  |-- site
  . |-- cr
  . | |-- subpage
  . |   |-- nested
  . |-- other

  Now, user-demo moves /site/cr/subpage underneath /site/other/ in the user workspace. This means in the
  user workspace the following status exists:
  |-- site
  . |-- cr
  .   |-- subpage   SHADOW NODE in user-demo
  .     |-- nested  SHADOW NODE in user-demo
  . |-- other
  .   |-- subpage   user-demo
  .     |-- nested  user-demo

  Now, let's assume user-demo forgets about this (thus not publishing), and a few weeks later needs to do
  a text change on subpage:
  |-- site
  . |-- cr
  .   |-- subpage   live + SHADOW NODE in user-demo
  .     |-- nested  live + SHADOW NODE in user-demo
  . |-- other
  .   |-- subpage   user-demo <-- THIS node gets edited by user-demo
  .     |-- nested  user-demo

  Now user-demo publishes only  /sites/other/subpage which leads to the following structure:
  |-- site
  . |-- cr
  .   |-- [NODE DOES NOT EXIST ANYMORE]
  .     |-- nested  live + SHADOW NODE in user-demo   <-- !!BUG!!
  . |-- other
  .   |-- subpage
  .     |-- nested  user-demo

  The first "nested" node (marked with !!BUG!!) is NOT visible anymore in live, because the parent does not exist
  anymore. It's hard to detect this as user-demo, because user-demo sees the moved nested node.

  We want to prevent those structures get created by forbidding those partial publishes in the first place!

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Presets |
      | language   | de      | de=de   |

    # The 'Neos.ContentRepository.Testing:Page' does not has 'Neos.Neos:Document' as supertype
    # and is by default no aggregate. This changes the behaviour of the PublishingService!
    And I have the following NodeTypes configuration:
    """
    'unstructured':
      constraints:
        nodeTypes:
          '*': true

    'Neos.Neos:ContentCollection': {}
    'Neos.Neos:Document':
      abstract: true
      aggregate: true
    'Neos.ContentRepository.Testing:Page':
      superTypes:
        'Neos.Neos:Document': true
      childNodes:
        main:
          type: 'Neos.Neos:ContentCollection'
      properties:
        title:
          type: string
    """

    And I have the following nodes:
      | Identifier                           | Path                     | Node Type                           | Properties            | Language |
      | 86198d18-8c4a-41eb-95fa-56223b2a3a97 | /sites                   | unstructured                        |                       | de       |
      | 594cd631-cf19-4072-9ee8-f8d840e85f5f | /sites/cr                | Neos.ContentRepository.Testing:Page | {"title": "CR SEITE"} | de       |
      | 7378845c-79cc-464c-90cf-03ec9ed551e8 | /sites/cr/subpage        | Neos.ContentRepository.Testing:Page | {"title": "Subpage"}  | de       |
      | 97d7a295-a3ed-44ec-bed3-22d501920578 | /sites/cr/subpage/nested | Neos.ContentRepository.Testing:Page | {"title": "Nested"}   | de       |
      | 94d5a8a2-d0d2-427b-af0a-2e4152f102ee | /sites/other             | Neos.ContentRepository.Testing:Page | {"title": "Other"}    | de       |
    And I am authenticated with role "Neos.Neos:Editor"

  Scenario: We only move a single document node. No parents, no document children (only content) => SHOULD WORK
    Given I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace  | Language |
      | user-admin | de       |

    # move node and publish
    When I move the node into the node with path "/sites/other"
    When I publish the following nodes with integrity check:
      | path                | Workspace  | Language |
      | /sites/other/nested | user-admin | de       |

    # Assertions: node was published successfully
    And I get a node by path "/sites/other/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root

    # Assertions: node does not exists on source location anymore
    When I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes

  Scenario: We move a parent and its child and only publish the child => child would be disconnected in live workspace => SHOULD FAIL
    Given I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    And I move the node into the node with path "/sites/other"

    When I publish the following nodes with integrity check and exceptions are caught:
      | path                        | Workspace  | Language |
      | /sites/other/subpage/nested | user-admin | de       |
    Then the last caught exception should be of type "NodePublishingIntegrityCheckViolationException" with message:
    """
      language-de: Parent with path /sites/other/subpage did not exists and it will NOT be created in the same publish
    """

    # Assertions: node was NOT published
    When I get a node by path "/sites/other/subpage/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes

    # Assertion: the node still exists at the old location
    When I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root

  Scenario: We move a parent and its child and only publish the parent-node => child would be disconnected in live workspace => SHOULD FAIL
    Given I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |

    # move nodes and publish
    And I move the node into the node with path "/sites/other"
    When I publish the following nodes with integrity check and exceptions are caught:
      | path                 | Workspace  | Language |
      | /sites/other/subpage | user-admin | de       |
    Then the last caught exception should be of type "NodePublishingIntegrityCheckViolationException" with message:
    """
    language-de: child node at path /sites/cr/subpage/nested still exists after publish
    """

    # Assertions: node was NOT published
    When I get a node by path "/sites/other/subpage" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes

    # Assertion: the node still exists at the old location
    When I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root

  Scenario: We move a parent and its child and publish both together => SHOULD WORK
    Given I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |

    # move nodes and publish
    When I move the node into the node with path "/sites/other"
    And I publish the following nodes with integrity check:
      | path                        | Workspace  | Language |
      | /sites/other/subpage        | user-admin | de       |
      | /sites/other/subpage/nested | user-admin | de       |

    # Assertions: nodes were published successfully
    And I get a node by path "/sites/other/subpage" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root
    And I get a node by path "/sites/other/subpage/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root

    # Assertions: node does not exists on source location anymore
    When I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes
    When I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes

  Scenario: We delete a child-document, move the parent to a new location and publish both changes => SHOULD WORK
    Given I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    And I remove the node

    And I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    And I move the node into the node with path "/sites/other"

    When I publish the following nodes with integrity check:
      | path                     | Workspace  | Language |
      | /sites/cr/subpage/nested | user-admin | de       |
      | /sites/other/subpage     | user-admin | de       |

    # Assertions: node was published successfully
    And I get a node by path "/sites/other/subpage" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root
    And I get a node by path "/sites/other/subpage/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes

    # Assertions: node does not exists on source location anymore
    When I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes
    When I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes

  Scenario: We delete a child-document, move the parent to a new location and publish ONLY the moved parent => SHOULD FAIL
    Given I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    And I remove the node

    And I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    And I move the node into the node with path "/sites/other"
    When I publish the following nodes with integrity check and exceptions are caught:
      | path                 | Workspace  | Language |
      | /sites/other/subpage | user-admin | de       |
    Then the last caught exception should be of type "NodePublishingIntegrityCheckViolationException" with message:
    """
    language-de: child node at path /sites/cr/subpage/nested still exists after publish
    """

    # Assertions: nodes were not published
    When I get a node by path "/sites/other/subpage" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes
    And I get a node by path "/sites/other/subpage/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes

    # Assertions: nodes still exist on source location
    When I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root
    When I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root

  # At the end of the publish, the child-page stays at the original location and in the user-workspace
  # the child was moved together with the new parent, so no node gets disconnected
  Scenario: We move a child-document and the new parent gets moved to another location, we only publish the new parent => SHOULD WORK
    Given I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    And I move the node into the node with path "/sites/other"

    Given I get a node by path "/sites/other" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    And I move the node into the node with path "/sites/cr"

    When I publish the following nodes with integrity check:
      | path            | Workspace  | Language |
      | /sites/cr/other | user-admin | de       |

    # Assertions: node was published
    When I get a node by path "/sites/cr/other" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root

    # Assertions: nested page still at old location in live workspace
    And I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root

    # Assertions: nodes still exist on source location
    When I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root
    When I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root

  Scenario: We move a node, remove the new parent and publish both changes => SHOULD FAIL
    Given I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    And I move the node into the node with path "/sites/other"

    Given I get a node by path "/sites/other" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    And I remove the node

    When I publish the following nodes with integrity check and exceptions are caught:
      | path                | Workspace  | Language |
      | /sites/other        | user-admin | de       |
      | /sites/other/nested | user-admin | de       |
    Then the last caught exception should be of type "NodePublishingIntegrityCheckViolationException" with message:
    """
    language-de: Target parent with path /sites/other gets removed in same publish!
    language-de: Parent with path /sites/other/nested did not exists and it will NOT be created in the same publish
    """

    # Assertions: node was not published
    When I get a node by path "/sites/other/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes

    # Assertions: nodes still exist on source location
    When I get a node by path "/sites/other" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root
    When I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root

  Scenario: We create a new node, move a node in the created node and publish both changes => SHOULD WORK
    # create the node with path /sites/new
    Given I have the following nodes:
      | Identifier                           | Path       | Node Type                           | Properties            | Language | Workspace  |
      | 65e8bb46-901d-4baf-b864-415994848906 | /sites/new | Neos.ContentRepository.Testing:Page | {"title": "CR SEITE"} | de       | user-admin |

    When I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    And I move the node into the node with path "/sites/new"
    And I publish the following nodes with integrity check:
      | path              | Workspace  | Language |
      | /sites/new        | user-admin | de       |
      | /sites/new/nested | user-admin | de       |

    # Assertions: nodes were published
    When I get a node by path "/sites/new" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root
    And I get a node by path "/sites/new/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root

    # Assertions: node does not exist on source location
    When I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes

  Scenario: We create a new node, move a node in the created node and publish ONLY the moved node => SHOULD FAIL
    # create the node with path /sites/new
    Given I have the following nodes:
      | Identifier                           | Path       | Node Type                           | Properties            | Language | Workspace  |
      | 65e8bb46-901d-4baf-b864-415994848906 | /sites/new | Neos.ContentRepository.Testing:Page | {"title": "CR SEITE"} | de       | user-admin |

    When I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    And I move the node into the node with path "/sites/new"
    And I publish the following nodes with integrity check and exceptions are caught:
      | path              | Workspace  | Language |
      | /sites/new/nested | user-admin | de       |
    Then the last caught exception should be of type "NodePublishingIntegrityCheckViolationException" with message:
    """
    language-de: Parent with path /sites/new did not exists and it will NOT be created in the same publish
    """

    # Assertions: node was not published
    When I get a node by path "/sites/new/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes

    # Assertions: node still exists on source location
    When I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root

  Scenario: We move a node, change a property of the new parent node and publish both changes => SHOULD WORK
    Given I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace  | Language |
      | user-admin | de       |

    When I move the node into the node with path "/sites/other"
    And I get a node by path "/sites/other" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    And I set the node property "title" to "new title for other page"
    And I publish the following nodes with integrity check:
      | path                | Workspace  | Language |
      | /sites/other        | user-admin | de       |
      | /sites/other/nested | user-admin | de       |

    # Assertions: nodes were published
    When I get a node by path "/sites/other" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root
    And the node property "title" should be "new title for other page"

    Then I get a node by path "/sites/other/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root

    # Assertions: node does not exist on source location
    When I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes


  ######################################################################
  ######################################################################
  #######                                                        #######
  #######               END OF SINGLE DIMENSION TESTS            #######
  #######                                                        #######
  #######   START OF MULTI DIMENSION TESTS WITHOUT FALLBACKS     #######
  #######                                                        #######
  ######################################################################
  ######################################################################

  # Basically a copy of "We only move a single document node. No parents, no document children (only content) => SHOULD WORK"
  # but added a second language dimension to check that the algorithm can handle two or more language dimensions
  Scenario: I got two language dimensions and publish changes in only in one of those
    # we need to set the default to en, because otherwise the auto-created child-nodes are missing
    # Reason: the getContextForProperties is called with $addDimensionDefaults=true (we do not know why)
    # this leads to adding the default dimension when not already in Language
    # This happens ONLY: in 'Given I have the following nodes:'
    Given I have the following content dimensions:
      | Identifier | Default | Presets      |
      | language   | en      | de=de; en=en |
    Given I have the following nodes:
      | Identifier                           | Path                     | Node Type                           | Properties            | Language |
      | 86198d18-8c4a-41eb-95fa-56223b2a3a97 | /sites                   | unstructured                        |                       | en       |
      | 594cd631-cf19-4072-9ee8-f8d840e85f5f | /sites/cr                | Neos.ContentRepository.Testing:Page | {"title": "CR SEITE"} | en       |
      | 7378845c-79cc-464c-90cf-03ec9ed551e8 | /sites/cr/subpage        | Neos.ContentRepository.Testing:Page | {"title": "Subpage"}  | en       |
      | 97d7a295-a3ed-44ec-bed3-22d501920578 | /sites/cr/subpage/nested | Neos.ContentRepository.Testing:Page | {"title": "Nested"}   | en       |
      | 94d5a8a2-d0d2-427b-af0a-2e4152f102ee | /sites/other             | Neos.ContentRepository.Testing:Page | {"title": "Other"}    | en       |

    Given I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace  | Language |
      | user-admin | en       |

    # move node and publish
    When I move the node into the node with path "/sites/other"
    When I publish the following nodes with integrity check:
      | path                | Workspace  | Language |
      | /sites/other/nested | user-admin | en       |

    # Assertions: node was published successfully
    And I get a node by path "/sites/other/nested" with the following context:
      | Workspace | Language |
      | live      | en       |
    Then I should have one node
    And the node should be connected to the root

    # Assertions: node does not exists on source location anymore
    When I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace | Language |
      | live      | en       |
    Then I should have 0 nodes
