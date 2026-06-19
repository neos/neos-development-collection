@flowEntities
Feature: Test the Fusion rendering for a request on a shortcut node

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root': {}
    'Neos.Neos:Content': {}
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Document':
      label: ${"Node (" + node.aggregateId + ")"}
      properties:
        title:
          type: string
        uriPathSegment:
          type: string
    'Neos.Neos:Shortcut':
      superTypes:
        'Neos.Neos:Document': true
      properties:
        targetMode:
          type: string
        target:
          type: string
    'Neos.Neos:Site':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Test.DocumentType':
      superTypes:
        'Neos.Neos:Document': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    When the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value             |
      | nodeAggregateId | "root"            |
      | nodeTypeName    | "Neos.Neos:Sites" |

    When an asset with id "some-asset" and file name "asset.txt" exists with the content "do we need asset shortcut nodes?"

    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | parentNodeAggregateId     | nodeTypeName                | initialPropertyValues                                                                                                       | nodeName |
      | a                          | root                      | Neos.Neos:Site              | {"title": "Node a"}                                                                                                         | a        |
      | sir-david-nodenborough     | a                         | Neos.Neos:Test.DocumentType | {"uriPathSegment": "david-nodenborough"}                                                                                    |          |
      | shortcuts                  | sir-david-nodenborough    | Neos.Neos:Test.DocumentType | {"uriPathSegment": "shortcuts"}                                                                                             |          |
      | shortcut-first-child-node  | shortcuts                 | Neos.Neos:Shortcut          | {"uriPathSegment": "shortcut-first-child-node", "targetMode": "firstChildNode"}                                             |          |
      | first-child-node           | shortcut-first-child-node | Neos.Neos:Test.DocumentType | {"uriPathSegment": "first-child-node"}                                                                                      |          |
      | second-child-node          | shortcut-first-child-node | Neos.Neos:Test.DocumentType | {"uriPathSegment": "second-child-node"}                                                                                     |          |
      | shortcut-parent-node       | shortcuts                 | Neos.Neos:Shortcut          | {"uriPathSegment": "shortcut-parent-node", "targetMode": "parentNode"}                                                      |          |
      | shortcut-selected-node     | shortcuts                 | Neos.Neos:Shortcut          | {"uriPathSegment": "shortcut-selected-node", "targetMode": "selectedTarget", "target": "node://sir-nodeward-nodington-iii"} |          |
      | shortcut-selected-asset    | shortcuts                 | Neos.Neos:Shortcut          | {"uriPathSegment": "shortcut-selected-asset", "targetMode": "selectedTarget", "target": "asset://some-asset"}               |          |
      | shortcut-external-url      | shortcuts                 | Neos.Neos:Shortcut          | {"uriPathSegment": "shortcut-external-url", "targetMode": "selectedTarget", "target": "https://neos.io"}                    |          |
      | sir-david-nodenborough-ii  | a                         | Neos.Neos:Test.DocumentType | {"uriPathSegment": "david-nodenborough-2"}                                                                                  |          |
      | sir-nodeward-nodington-iii | sir-david-nodenborough-ii | Neos.Neos:Test.DocumentType | {"uriPathSegment": "nodeward-3"}                                                                                            |          |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "cs-user-id"     |

    And A site exists for node name "a" and domain "http://localhost" and package "Vendor.Site"
    And the sites configuration is:
    """yaml
    Neos:
      Neos:
        sites:
          'a':
            preset: default
            uriPathSuffix: ''
            contentDimensions:
              resolver:
                factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\NoopResolverFactory
    """

    And the Fusion code for package "Vendor.Site" is:
    """fusion
    prototype(Neos.Neos:Test.DocumentType) < prototype(Neos.Fusion:Component) {
        renderer = afx`
          node: {node.aggregateId}
        `
    }
    """

  Scenario: Match direct node
    When I dispatch the following request "/david-nodenborough"
    Then I expect the following response:
    """
    HTTP/1.1 200 OK
    Content-Type: text/html
    X-Flow-Powered: Flow/dev Neos/dev

    node: sir-david-nodenborough
    """

  Scenario: Match shortcut nodes (NodeShortcutResolver)
    When I dispatch the following request "/david-nodenborough/shortcuts/shortcut-first-child-node"
    Then I expect the following response:
    """
    HTTP/1.1 303 See Other
    Location: /david-nodenborough/shortcuts/shortcut-first-child-node/first-child-node
    X-Flow-Powered: Flow/dev Neos/dev


    """

    When I dispatch the following request "/david-nodenborough/shortcuts/shortcut-parent-node"
    Then I expect the following response:
    """
    HTTP/1.1 303 See Other
    Location: /david-nodenborough/shortcuts
    X-Flow-Powered: Flow/dev Neos/dev


    """

    When I dispatch the following request "/david-nodenborough/shortcuts/shortcut-selected-node"
    Then I expect the following response:
    """
    HTTP/1.1 303 See Other
    Location: /david-nodenborough-2/nodeward-3
    X-Flow-Powered: Flow/dev Neos/dev


    """

    When I dispatch the following request "/david-nodenborough/shortcuts/shortcut-selected-asset"
    Then I expect the following response:
    """
    HTTP/1.1 303 See Other
    Location: http://localhost/_Resources/Testing/Persistent/23dae371d1664f1d9cc7dd029b299ea717298103/asset.txt
    X-Flow-Powered: Flow/dev Neos/dev


    """

    When I dispatch the following request "/david-nodenborough/shortcuts/shortcut-external-url"
    Then I expect the following response:
    """
    HTTP/1.1 303 See Other
    Location: https://neos.io
    X-Flow-Powered: Flow/dev Neos/dev


    """

  Scenario: Render shortcut node with target information in backend (fusion rendering of Neos.Neos:Shortcut)
    When I dispatch the following request "/neos/preview?node=%7B%22contentRepositoryId%22%3A%22default%22%2C%22workspaceName%22%3A%22user-workspace%22%2C%22dimensionSpacePoint%22%3A%7B%7D%2C%22aggregateId%22%3A%22shortcut-first-child-node%22%7D"
    Then I expect the following response:
    """
    HTTP/1.1 200 OK
    Content-Type: text/html
    X-Flow-Powered: Flow/dev Neos/dev

    <!DOCTYPE html><html>
    <!--
    This website is powered by Neos, the Open Source Content Application Platform licensed under the GNU/GPL.
    Neos is based on Flow, a powerful PHP application framework licensed under the MIT license.

    More information and contribution opportunities at https://www.neos.io
    -->
    <head><meta charset="UTF-8" /><title></title><link rel="stylesheet" href="http://localhost/_Resources/Testing/Static/Packages/Neos.Neos/Styles/BackendInformation.css" /></head><div id="neos-backend-information"><p>This is a shortcut to the first child page.<br />Click <a href="/neos/preview?node=%7B%22contentRepositoryId%22%3A%22default%22%2C%22workspaceName%22%3A%22user-workspace%22%2C%22dimensionSpacePoint%22%3A%5B%5D%2C%22aggregateId%22%3A%22first-child-node%22%7D">Node (first-child-node)</a> to continue to the page.</p></div><body class></body></html>
    """

    When I dispatch the following request "/neos/preview?node=%7B%22contentRepositoryId%22%3A%22default%22%2C%22workspaceName%22%3A%22user-workspace%22%2C%22dimensionSpacePoint%22%3A%7B%7D%2C%22aggregateId%22%3A%22shortcut-parent-node%22%7D"
    Then I expect the following response:
    """
    HTTP/1.1 200 OK
    Content-Type: text/html
    X-Flow-Powered: Flow/dev Neos/dev

    <!DOCTYPE html><html>
    <!--
    This website is powered by Neos, the Open Source Content Application Platform licensed under the GNU/GPL.
    Neos is based on Flow, a powerful PHP application framework licensed under the MIT license.

    More information and contribution opportunities at https://www.neos.io
    -->
    <head><meta charset="UTF-8" /><title></title><link rel="stylesheet" href="http://localhost/_Resources/Testing/Static/Packages/Neos.Neos/Styles/Shortcut.css" /></head><body class><div id="neos-shortcut"><p>This is a shortcut to the parent page.<br />Click <a href="/neos/preview?node=%7B%22contentRepositoryId%22%3A%22default%22%2C%22workspaceName%22%3A%22user-workspace%22%2C%22dimensionSpacePoint%22%3A%5B%5D%2C%22aggregateId%22%3A%22shortcuts%22%7D">Node (shortcuts)</a> to continue to the page.</p></div></body></html>
    """

    When I dispatch the following request "/neos/preview?node=%7B%22contentRepositoryId%22%3A%22default%22%2C%22workspaceName%22%3A%22user-workspace%22%2C%22dimensionSpacePoint%22%3A%7B%7D%2C%22aggregateId%22%3A%22shortcut-selected-node%22%7D"
    Then I expect the following response:
    """
    HTTP/1.1 200 OK
    Content-Type: text/html
    X-Flow-Powered: Flow/dev Neos/dev

    <!DOCTYPE html><html>
    <!--
    This website is powered by Neos, the Open Source Content Application Platform licensed under the GNU/GPL.
    Neos is based on Flow, a powerful PHP application framework licensed under the MIT license.

    More information and contribution opportunities at https://www.neos.io
    -->
    <head><meta charset="UTF-8" /><title></title><link rel="stylesheet" href="http://localhost/_Resources/Testing/Static/Packages/Neos.Neos/Styles/Shortcut.css" /></head><body class><div id="neos-shortcut"><p>This is a shortcut to a specific target:<br/>Click <a href="/neos/preview?node=%7B%22contentRepositoryId%22%3A%22default%22%2C%22workspaceName%22%3A%22user-workspace%22%2C%22dimensionSpacePoint%22%3A%5B%5D%2C%22aggregateId%22%3A%22sir-nodeward-nodington-iii%22%7D">Node (sir-nodeward-nodington-iii)</a> to continue to the page.</p></div></body></html>
    """

    When I dispatch the following request "/neos/preview?node=%7B%22contentRepositoryId%22%3A%22default%22%2C%22workspaceName%22%3A%22user-workspace%22%2C%22dimensionSpacePoint%22%3A%7B%7D%2C%22aggregateId%22%3A%22shortcut-selected-asset%22%7D"
    Then I expect the following response:
    """
    HTTP/1.1 200 OK
    Content-Type: text/html
    X-Flow-Powered: Flow/dev Neos/dev

    <!DOCTYPE html><html>
    <!--
    This website is powered by Neos, the Open Source Content Application Platform licensed under the GNU/GPL.
    Neos is based on Flow, a powerful PHP application framework licensed under the MIT license.

    More information and contribution opportunities at https://www.neos.io
    -->
    <head><meta charset="UTF-8" /><title></title><link rel="stylesheet" href="http://localhost/_Resources/Testing/Static/Packages/Neos.Neos/Styles/Shortcut.css" /></head><body class><div id="neos-shortcut"><p>This is a shortcut to a specific target:<br/>Click <a target="_blank" href="http://localhost/_Resources/Testing/Persistent/23dae371d1664f1d9cc7dd029b299ea717298103/asset.txt">asset.txt</a> to see the file.</p></div></body></html>
    """

    When I dispatch the following request "/neos/preview?node=%7B%22contentRepositoryId%22%3A%22default%22%2C%22workspaceName%22%3A%22user-workspace%22%2C%22dimensionSpacePoint%22%3A%7B%7D%2C%22aggregateId%22%3A%22shortcut-external-url%22%7D"
    Then I expect the following response:
    """
    HTTP/1.1 200 OK
    Content-Type: text/html
    X-Flow-Powered: Flow/dev Neos/dev

    <!DOCTYPE html><html>
    <!--
    This website is powered by Neos, the Open Source Content Application Platform licensed under the GNU/GPL.
    Neos is based on Flow, a powerful PHP application framework licensed under the MIT license.

    More information and contribution opportunities at https://www.neos.io
    -->
    <head><meta charset="UTF-8" /><title></title><link rel="stylesheet" href="http://localhost/_Resources/Testing/Static/Packages/Neos.Neos/Styles/Shortcut.css" /></head><body class><div id="neos-shortcut"><p>This is a shortcut to a specific target:<br/>Click <a href="https://neos.io" target="_blank">https://neos.io</a> to open the link.</p></div></body></html>
    """
