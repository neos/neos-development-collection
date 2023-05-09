@fixtures @contentrepository
# Note: For the routing tests to work we rely on Configuration/Testing/Behat/NodeTypes.Test.Routing.yaml
Feature: Routing behavior of shortcut nodes

  Background:
    Given I have no content dimensions
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                    |
      | contentStreamId             | "cs-identifier"          |
      | nodeAggregateId             | "lady-eleonode-rootford" |
      | nodeTypeName                | "Neos.Neos:Sites"        |
      | coveredDimensionSpacePoints | [{}]                     |
      | nodeAggregateClassification | "root"                   |
    And the graph projection is fully up to date

    # lady-eleonode-rootford
    #   shernode-homes
    #      sir-david-nodenborough
    #        shortcuts
    #          shortcut-first-child-node
    #            first-child-node
    #            second-child-node
    #          shortcut-parent-node
    #          shortcut-selected-node
    #          shortcut-external-url
    #      sir-david-nodenborough-ii
    #        sir-nodeward-nodington-iii
    #
    And I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | parentNodeAggregateId     | nodeTypeName                                       | initialPropertyValues                                                                                                       | nodeName |
      | shernode-homes             | lady-eleonode-rootford    | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "ignore-me"}                                                                                             | node1    |
      | sir-david-nodenborough     | shernode-homes            | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "david-nodenborough"}                                                                                    | node2    |
      | shortcuts                  | sir-david-nodenborough    | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "shortcuts"}                                                                                             | node3    |
      | shortcut-first-child-node  | shortcuts                 | Neos.Neos:Shortcut                                 | {"uriPathSegment": "shortcut-first-child", "targetMode": "firstChildNode"}                                                  | node4    |
      | first-child-node           | shortcut-first-child-node | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "first-child-node"}                                                                                      | node5    |
      | second-child-node          | shortcut-first-child-node | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "second-child-node"}                                                                                     | node6    |
      | shortcut-parent-node       | shortcuts                 | Neos.Neos:Shortcut                                 | {"uriPathSegment": "shortcut-parent-node", "targetMode": "parentNode"}                                                      | node7    |
      | shortcut-selected-node     | shortcuts                 | Neos.Neos:Shortcut                                 | {"uriPathSegment": "shortcut-selected-node", "targetMode": "selectedTarget", "target": "node://sir-nodeward-nodington-iii"} | node8    |
      | shortcut-selected-asset    | shortcuts                 | Neos.Neos:Shortcut                                 | {"uriPathSegment": "shortcut-selected-asset", "targetMode": "selectedTarget", "target": "asset://some-asset"}               | node9    |
      | shortcut-external-url      | shortcuts                 | Neos.Neos:Shortcut                                 | {"uriPathSegment": "shortcut-external-url", "targetMode": "selectedTarget", "target": "https://neos.io"}                    | node10   |
      | sir-david-nodenborough-ii  | shernode-homes            | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "david-nodenborough-2"}                                                                                  | node11   |
      | sir-nodeward-nodington-iii | sir-david-nodenborough-ii | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "nodeward-3"}                                                                                            | node12   |
    And A site exists for node name "node1"
    And the sites configuration is:
    """
    Neos:
      Neos:
        sites:
          '*':
            contentRepository: default
            contentDimensions:
              resolver:
                factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\NoopResolverFactory
    """
    And The documenturipath projection is up to date

  Scenario: Shortcut parent node
    When I am on URL "/"
    Then the node "shortcut-parent-node" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/shortcuts"

  Scenario: Shortcut selected target node
    When I am on URL "/"
    Then the node "shortcut-selected-node" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough-2/nodeward-3"

  Scenario: Shortcut selected target asset
    Given an asset with id "some-asset" and file name "asset.txt" exists with the content "do we need asset shortcut nodes?"
    When I am on URL "/"
    Then the node "shortcut-selected-asset" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/_Resources/Testing/Persistent/23dae371d1664f1d9cc7dd029b299ea717298103/asset.txt"

  Scenario: Shortcut selected target URL
    When I am on URL "/"
    Then the node "shortcut-external-url" in content stream "cs-identifier" and dimension "{}" should resolve to URL "https://neos.io/"

  Scenario: Shortcut selected target relative URL
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                            |
      | contentStreamId           | "cs-identifier"                  |
      | nodeAggregateId           | "shortcut-external-url"          |
      | originDimensionSpacePoint | {}                               |
      | propertyValues            | {"target": "/some/relative/url"} |
    And The documenturipath projection is up to date
    When I am on URL "https://current.host/"
    Then the node "shortcut-external-url" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/some/relative/url"

  Scenario: Shortcut selected target URL keeps schema, port, query and fragment of absolute target URLs
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                                                                                 |
      | contentStreamId           | "cs-identifier"                                                                       |
      | nodeAggregateId           | "shortcut-external-url"                                                               |
      | originDimensionSpacePoint | {}                                                                                    |
      | propertyValues            | {"target": "https://www.some-domain.tld:1234/some/url/path?some=query#some-fragment"} |
    And The documenturipath projection is up to date
    When I am on URL "http://current.host/"
    Then the node "shortcut-external-url" in content stream "cs-identifier" and dimension "{}" should resolve to URL "https://www.some-domain.tld:1234/some/url/path?some=query#some-fragment"

  Scenario: Shortcut first child node
    When I am on URL "/"
    Then the node "shortcut-first-child-node" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/shortcuts/shortcut-first-child/first-child-node"

  Scenario: Shortcut first child node is updated when a new first child node aggregate is created
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                              | Value                                                |
      | contentStreamId                  | "cs-identifier"                                      |
      | nodeAggregateId                  | "nody-mc-newface"                                    |
      | nodeTypeName                     | "Neos.Neos:Test.Routing.Page" |
      | originDimensionSpacePoint        | {}                                                   |
      | parentNodeAggregateId            | "shortcut-first-child-node"                          |
      | initialPropertyValues            | {"uriPathSegment": "new-child-node"}                 |
      | succeedingSiblingNodeAggregateId | "first-child-node"                                   |
    And The documenturipath projection is up to date
    When I am on URL "/"
    Then the node "shortcut-first-child-node" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/shortcuts/shortcut-first-child/new-child-node"

  Scenario: Shortcut first child node is updated when a node aggregate gets moved to be the new first child node
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                        |
      | contentStreamId                     | "cs-identifier"              |
      | nodeAggregateId                     | "sir-nodeward-nodington-iii" |
      | dimensionSpacePoint                 | {}                           |
      | newParentNodeAggregateId            | "shortcut-first-child-node"  |
      | newSucceedingSiblingNodeAggregateId | "first-child-node"           |
    And The documenturipath projection is up to date
    When I am on URL "/"
    Then the node "shortcut-first-child-node" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/shortcuts/shortcut-first-child/nodeward-3"

  Scenario: Shortcut first child node is updated when a node aggregate gets moved to be the new first child node on the same leve
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value               |
      | contentStreamId                     | "cs-identifier"     |
      | nodeAggregateId                     | "second-child-node" |
      | dimensionSpacePoint                 | {}                  |
      | newParentNodeAggregateId            | null                |
      | newSucceedingSiblingNodeAggregateId | "first-child-node"  |
    And The documenturipath projection is up to date
    When I am on URL "/"
    Then the node "shortcut-first-child-node" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/shortcuts/shortcut-first-child/second-child-node"

  Scenario: Shortcut first child node is not updated when a node aggregate gets moved behind an existing first child node
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                        |
      | contentStreamId                     | "cs-identifier"              |
      | nodeAggregateId                     | "sir-nodeward-nodington-iii" |
      | dimensionSpacePoint                 | {}                           |
      | newParentNodeAggregateId            | "shortcut-first-child-node"  |
      | newSucceedingSiblingNodeAggregateId | "second-child-node"          |
    And The documenturipath projection is up to date
    When I am on URL "/"
    Then the node "shortcut-first-child-node" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/shortcuts/shortcut-first-child/first-child-node"

  Scenario: Shortcut first child node is not updated when a node aggregate gets moved behind an existing first child node on the same leve
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                              | Value                                                |
      | contentStreamId                  | "cs-identifier"                                      |
      | nodeAggregateId                  | "nody-mc-newface"                                    |
      | nodeTypeName                     | "Neos.Neos:Test.Routing.Page" |
      | originDimensionSpacePoint        | {}                                                   |
      | parentNodeAggregateId            | "shortcut-first-child-node"                          |
      | initialPropertyValues            | {"uriPathSegment": "new-child-node"}                 |
      | succeedingSiblingNodeAggregateId | "second-child-node"                                  |
    And the graph projection is fully up to date
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value             |
      | contentStreamId                     | "cs-identifier"   |
      | nodeAggregateId                     | "nody-mc-newface" |
      | dimensionSpacePoint                 | {}                |
      | newParentNodeAggregateId            | null              |
      | newSucceedingSiblingNodeAggregateId | null              |
    And The documenturipath projection is up to date
    When I am on URL "/"
    Then the node "shortcut-first-child-node" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/shortcuts/shortcut-first-child/first-child-node"

  Scenario: Regular document node gets turned into a shortcut node
    When the command ChangeNodeAggregateType was published with payload:
      | Key             | Value                       |
      | contentStreamId | "cs-identifier"             |
      | nodeAggregateId | "sir-david-nodenborough-ii" |
      | newNodeTypeName | "Neos.Neos:Shortcut"        |
      | strategy        | "happypath"                 |
    And The documenturipath projection is up to date
    When I am on URL "/"
    Then the node "sir-david-nodenborough-ii" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough-2/nodeward-3"

  Scenario: Shortcut node gets turned into a regular document node
    When the command ChangeNodeAggregateType was published with payload:
      | Key             | Value                                                |
      | contentStreamId | "cs-identifier"                                      |
      | nodeAggregateId | "shortcut-first-child-node"                          |
      | newNodeTypeName | "Neos.Neos:Test.Routing.Page" |
      | strategy        | "happypath"                                          |
    And The documenturipath projection is up to date
    When I am on URL "/"
    Then the node "shortcut-first-child-node" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/shortcuts/shortcut-first-child"

  Scenario: Change shortcut targetMode from "firstChildNode" to "parentNode"
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | contentStreamId           | "cs-identifier"              |
      | nodeAggregateId           | "shortcut-first-child-node"  |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"targetMode": "parentNode"} |
    And The documenturipath projection is up to date
    When I am on URL "/"
    Then the node "shortcut-first-child-node" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/shortcuts"

  Scenario: Change shortcut targetMode from "firstChildNode" to "selectedTarget" (URL)
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                                                            |
      | contentStreamId           | "cs-identifier"                                                  |
      | nodeAggregateId           | "shortcut-first-child-node"                                      |
      | originDimensionSpacePoint | {}                                                               |
      | propertyValues            | {"targetMode": "selectedTarget", "target": "http://www.neos.io"} |
    And The documenturipath projection is up to date
    When I am on URL "/"
    Then the node "shortcut-first-child-node" in content stream "cs-identifier" and dimension "{}" should resolve to URL "http://www.neos.io/"

  Scenario: Change shortcut targetMode from "parentNode" to "firstChildNode"
    And I am in content stream "cs-identifier" and dimension space point {}
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId | nodeTypeName                                       | initialPropertyValues           | nodeName |
      | new-child-node  | shortcut-parent-node  | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "new-child"} | new      |
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                            |
      | contentStreamId           | "cs-identifier"                  |
      | nodeAggregateId           | "shortcut-parent-node"           |
      | originDimensionSpacePoint | {}                               |
      | propertyValues            | {"targetMode": "firstChildNode"} |
    And The documenturipath projection is up to date
    When I am on URL "/"
    Then the node "shortcut-parent-node" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/shortcuts/shortcut-parent-node/new-child"

  Scenario: Change shortcut targetMode from "parentNode" to "selectedTarget" (URL)
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                                                          |
      | contentStreamId           | "cs-identifier"                                                |
      | nodeAggregateId           | "shortcut-parent-node"                                         |
      | originDimensionSpacePoint | {}                                                             |
      | propertyValues            | {"targetMode": "selectedTarget", "target": "https://neos.io/"} |
    And The documenturipath projection is up to date
    When I am on URL "/"
    Then the node "shortcut-parent-node" in content stream "cs-identifier" and dimension "{}" should resolve to URL "https://neos.io/"

  Scenario: Shortcut node with an invalid targetMode
    Given the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                       | Value                                                                  |
      | contentStreamId           | "cs-identifier"                                                        |
      | nodeAggregateId           | "invalid-target-mode"                                                  |
      | nodeTypeName              | "Neos.Neos:Shortcut"                                                   |
      | originDimensionSpacePoint | {}                                                                     |
      | parentNodeAggregateId     | "shortcuts"                                                            |
      | initialPropertyValues     | {"uriPathSegment": "invalid-target-mode", "targetMode": "invalidMode"} |
      | nodeName                  | "some-node-name"                                                       |
    And The documenturipath projection is up to date
    When I am on URL "/"
    Then The node "invalid-target-mode" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL

  Scenario: Shortcut node with targetMode "selectedTarget" but without target
    Given the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                       | Value                                                                        |
      | contentStreamId           | "cs-identifier"                                                              |
      | nodeAggregateId           | "invalid-missing-target"                                                     |
      | nodeTypeName              | "Neos.Neos:Shortcut"                                                         |
      | originDimensionSpacePoint | {}                                                                           |
      | parentNodeAggregateId     | "shortcuts"                                                                  |
      | initialPropertyValues     | {"uriPathSegment": "invalid-missing-target", "targetMode": "selectedTarget"} |
      | nodeName                  | "some-node-name"                                                             |
    And The documenturipath projection is up to date
    When I am on URL "/"
    Then The node "invalid-missing-target" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL

  Scenario: Shortcut node without child nodes and targetMode "firstChildNode"
    Given the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                       | Value                                                                              |
      | contentStreamId           | "cs-identifier"                                                                    |
      | nodeAggregateId           | "invalid-shortcut-first-child-node"                                                |
      | nodeTypeName              | "Neos.Neos:Shortcut"                                                               |
      | originDimensionSpacePoint | {}                                                                                 |
      | parentNodeAggregateId     | "shortcuts"                                                                        |
      | initialPropertyValues     | {"uriPathSegment": "invalid-shortcut-first-child", "targetMode": "firstChildNode"} |
      | nodeName                  | "some-node-name"                                                                   |
    And The documenturipath projection is up to date
    When I am on URL "/"
    Then The node "invalid-shortcut-first-child-node" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL

  Scenario: Shortcut node with targetMode "selectedTarget" and a non-existing target node
    Given the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                       | Value                                                                                                                      |
      | contentStreamId           | "cs-identifier"                                                                                                            |
      | nodeAggregateId           | "invalid-shortcut-selected-node"                                                                                           |
      | nodeTypeName              | "Neos.Neos:Shortcut"                                                                                                       |
      | originDimensionSpacePoint | {}                                                                                                                         |
      | parentNodeAggregateId     | "shortcuts"                                                                                                                |
      | initialPropertyValues     | {"uriPathSegment": "invalid-shortcut-selected-node", "targetMode": "selectedTarget", "target": "node://non-existing-node"} |
      | nodeName                  | "some-node-name"                                                                                                           |
    And The documenturipath projection is up to date
    When I am on URL "/"
    Then The node "invalid-shortcut-selected-node" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL

  Scenario: Shortcut node with targetMode "selectedTarget" and a empty target node
    Given the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                       | Value                                                                                                     |
      | contentStreamId           | "cs-identifier"                                                                                           |
      | nodeAggregateId           | "invalid-shortcut-selected-node"                                                                          |
      | nodeTypeName              | "Neos.Neos:Shortcut"                                                                                      |
      | originDimensionSpacePoint | {}                                                                                                        |
      | parentNodeAggregateId     | "shortcuts"                                                                                               |
      | initialPropertyValues     | {"uriPathSegment": "invalid-shortcut-selected-node", "targetMode": "selectedTarget", "target": "node://"} |
      | nodeName                  | "some-node-name"                                                                                          |
    And The documenturipath projection is up to date
    When I am on URL "/"
    Then The node "invalid-shortcut-selected-node" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL

  Scenario: Recursive shortcuts
    And I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId | nodeTypeName       | initialPropertyValues                                                                                      | nodeName |
      | level-1         | shortcuts             | Neos.Neos:Shortcut | {"uriPathSegment": "level1", "targetMode": "selectedTarget", "target": "node://level-2"}                   | level1   |
      | level-2         | shortcuts             | Neos.Neos:Shortcut | {"uriPathSegment": "level2", "targetMode": "selectedTarget", "target": "node://shortcut-first-child-node"} | level2   |
    And The documenturipath projection is up to date
    When I am on URL "/"
    Then the node "level-1" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/shortcuts/shortcut-first-child/first-child-node"
    Then the node "level-2" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/shortcuts/shortcut-first-child/first-child-node"

  Scenario: Unlimited recursive shortcuts
    And I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId | nodeTypeName       | initialPropertyValues                                                              | nodeName |
      | node-a          | shortcuts             | Neos.Neos:Shortcut | {"uriPathSegment": "a", "targetMode": "selectedTarget", "target": "node://node-b"} | node-a   |
      | node-b          | shortcuts             | Neos.Neos:Shortcut | {"uriPathSegment": "b", "targetMode": "selectedTarget", "target": "node://node-a"} | node-b   |
    And The documenturipath projection is up to date
    When I am on URL "/"
    Then The node "node-a" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL
