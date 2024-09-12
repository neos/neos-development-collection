@flowEntities
Feature: Tests for the ContentCacheFlusher and cache flushing on asset changes

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root': {}
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Document':
      properties:
        title:
          type: string
        uriPathSegment:
          type: string
    'Neos.Neos:Site':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Test.DocumentType1':
      superTypes:
        'Neos.Neos:Document': true
      properties:
        asset:
          type: Neos\Media\Domain\Model\Asset
        assets:
          type: array<Neos\Media\Domain\Model\Asset>
    'Neos.Neos:Test.DocumentType2':
      superTypes:
        'Neos.Neos:Document': true
      properties:
        text:
          type: string

    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"

    When an asset exists with id "an-asset-to-change"
    And the asset "an-asset-to-change" has the title "First asset" and caption "This is an asset" and copyright notice "Copyright Neos 2024"
    When an asset exists with id "some-other-asset"
    And the asset "some-other-asset" has the title "Some other asset" and caption "This is some other asset" and copyright notice "Copyright Neos 2024"
    When an asset exists with id "an-asset-to-change-deep-in-tree"
    And the asset "an-asset-to-change-deep-in-tree" has the title "Deep in the tree" and caption "This is an asset, deep in the tree" and copyright notice "Copyright Neos 2024"
    And the ContentCacheFlusher flushes all collected tags

    When the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value             |
      | nodeAggregateId | "root"            |
      | nodeTypeName    | "Neos.Neos:Sites" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId | nodeTypeName                 | initialPropertyValues                                                                                     | nodeName |
      | a               | root                  | Neos.Neos:Site               | {}                                                                                                        | site     |
      | a1              | a                     | Neos.Neos:Test.DocumentType1 | {"uriPathSegment": "a1", "title": "Node a1", "asset": "Asset:an-asset-to-change"}                         | a1       |
      | a1-1            | a1                    | Neos.Neos:Test.DocumentType1 | {"uriPathSegment": "a1-1", "title": "Node a1-1", "assets": ["Asset:an-asset-to-change"]}                  | a1-1     |
      | a1-2            | a1                    | Neos.Neos:Test.DocumentType1 | {"uriPathSegment": "a1-2", "title": "Node a1-2", "asset": "Asset:some-other-asset"}                       | a1-2     |
      | a1-1-1          | a1-1                  | Neos.Neos:Test.DocumentType1 | {"uriPathSegment": "a1-1-1", "title": "Node a1-1-1", "assets": ["Asset:an-asset-to-change-deep-in-tree"]} | a1-1-1   |
      | a2              | a                     | Neos.Neos:Test.DocumentType2 | {"uriPathSegment": "a2", "title": "Node a2", "text": "Link to asset://an-asset-to-change."}               | a2       |
      | a3              | a                     | Neos.Neos:Test.DocumentType2 | {"uriPathSegment": "a2", "title": "Node a2", "text": "Link to asset://some-other-asset."}                 | a3       |
    When the command RebaseWorkspace is executed with payload:
      | Key           | Value       |
      | workspaceName | "user-test" |
    And A site exists for node name "a" and domain "http://localhost"
    And the sites configuration is:
    """yaml
    Neos:
      Neos:
        sites:
          '*':
            contentRepository: default
            contentDimensions:
              resolver:
                factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\NoopResolverFactory
    """
    And the Fusion context node is "a1"
    And the Fusion context request URI is "http://localhost"
    And I have the following Fusion setup:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    prototype(Neos.Neos:Test.DocumentType1) < prototype(Neos.Fusion:Component) {

      cacheVerifier = ${null}
      assetTitle = ${q(node).property("asset").title}
      assetTitleOfArray = ${q(node).property("assets")[0].title}

      renderer = afx`
        cacheVerifier={props.cacheVerifier},
        assetTitle={props.assetTitle},
        assetTitleOfArray={props.assetTitleOfArray}
      `

      @cache {
        mode = 'cached'
        entryIdentifier {
          documentNode = ${Neos.Caching.entryIdentifierForNode(node)}
        }
        entryTags {
          1 = ${Neos.Caching.nodeTag(node)}
          2 = ${Neos.Caching.descendantOfTag(node)}
        }
      }
    }

    prototype(Neos.Neos:Test.DocumentType2) < prototype(Neos.Fusion:Component) {

      cacheVerifier = ${null}
      text = ${q(node).property('text')}

      renderer = afx`
        cacheVerifier={props.cacheVerifier},
        text={props.text}
      `

      @cache {
        mode = 'cached'
        entryIdentifier {
          documentNode = ${Neos.Caching.entryIdentifierForNode(node)}
        }
        entryTags {
          1 = ${Neos.Caching.nodeTag(node)}
          2 = ${Neos.Caching.descendantOfTag(node)}
        }
      }
    }

    """

  Scenario: ContentCache gets flushed when an referenced asset in a property has changed
    Given I have Fusion content cache enabled
    And the Fusion context node is "a1"

    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType1 {
      cacheVerifier = ${"first execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=first execution, assetTitle=First asset, assetTitleOfArray=
    """

    Then the asset "an-asset-to-change" has the title "First changed asset"
    And the ContentCacheFlusher flushes all collected tags

    Then the Fusion context node is "a1"
    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType1 {
      cacheVerifier = ${"second execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=second execution, assetTitle=First changed asset, assetTitleOfArray=
    """

  Scenario: ContentCache gets flushed when an referenced asset in a property array has changed
    Given I have Fusion content cache enabled
    And the Fusion context node is "a1-1"

    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType1 {
      cacheVerifier = ${"first execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=first execution, assetTitle=, assetTitleOfArray=First asset
    """

    Then the asset "an-asset-to-change" has the title "First changed asset"
    And the ContentCacheFlusher flushes all collected tags

    Then the Fusion context node is "a1-1"
    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType1 {
      cacheVerifier = ${"second execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=second execution, assetTitle=, assetTitleOfArray=First changed asset
    """


  Scenario: ContentCache doesn't get flushed when another asset than the referenced asset in a property has changed
    Given I have Fusion content cache enabled
    And the Fusion context node is "a1-2"

    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType1 {
      cacheVerifier = ${"first execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=first execution, assetTitle=Some other asset, assetTitleOfArray=
    """

    Then the asset "an-asset-to-change" has the title "First changed asset"
    And the ContentCacheFlusher flushes all collected tags

    Then the Fusion context node is "a1-2"
    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType1 {
      cacheVerifier = ${"second execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=first execution, assetTitle=Some other asset, assetTitleOfArray=
    """

  Scenario: ContentCache gets flushed for live workspace when a referenced asset in a property text has changed
    Given I have Fusion content cache enabled
    And the Fusion context node is a2

    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType2 {
      cacheVerifier = ${"first execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=first execution, text=Link to asset://an-asset-to-change.
    """

    Then the asset "an-asset-to-change" has the title "First changed asset"
    And the ContentCacheFlusher flushes all collected tags

    Then the Fusion context node is a2
    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType2 {
      cacheVerifier = ${"second execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=second execution, text=Link to asset://an-asset-to-change.
    """

  Scenario: ContentCache gets flushed for user workspace when a referenced asset in a property text has changed
    Given I have Fusion content cache enabled
    And I am in workspace "user-test" and dimension space point {}
    And the Fusion context node is a2

    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType2 {
      cacheVerifier = ${"first execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=first execution, text=Link to asset://an-asset-to-change.
    """

    And I am in workspace "live" and dimension space point {}
    Then the asset "an-asset-to-change" has the title "First changed asset"
    And the ContentCacheFlusher flushes all collected tags

    And I am in workspace "user-test" and dimension space point {}
    Then the Fusion context node is a2
    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType2 {
      cacheVerifier = ${"second execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=second execution, text=Link to asset://an-asset-to-change.
    """

  Scenario: ContentCache doesn't get flushed when a non-referenced asset in a property text has changed
    Given I have Fusion content cache enabled
    And the Fusion context node is a3

    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType2 {
      cacheVerifier = ${"first execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=first execution, text=Link to asset://some-other-asset.
    """

    Then the asset "an-asset-to-change" has the title "First changed asset"
    And the ContentCacheFlusher flushes all collected tags

    Then the Fusion context node is a3
    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType2 {
      cacheVerifier = ${"second execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=first execution, text=Link to asset://some-other-asset.
    """

  Scenario: ContentCache gets flushed when an referenced asset in a property has changed in a descendant node (2 levels)
    Given I have Fusion content cache enabled
    And the Fusion context node is "a1"

    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType1 {
      cacheVerifier = ${"first execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=first execution, assetTitle=First asset, assetTitleOfArray=
    """

    Then the asset "an-asset-to-change-deep-in-tree" has the title "Deep in the tree changed"
    And the ContentCacheFlusher flushes all collected tags

    Then the Fusion context node is "a1"
    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType1 {
      cacheVerifier = ${"second execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=second execution, assetTitle=First asset, assetTitleOfArray=
    """