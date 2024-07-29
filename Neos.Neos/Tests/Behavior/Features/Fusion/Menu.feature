@flowEntities @contentrepository
Feature: Tests for the "Neos.Neos:Menu" and related Fusion prototypes

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
        hiddenInMenu:
          type: bool
    'Neos.Neos:Site':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Content':
      properties:
        title:
          type: string
    'Neos.Neos:Test.DocumentType1':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Test.DocumentType2':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Test.DocumentType2a':
      superTypes:
        'Neos.Neos:Test.DocumentType2': true
    'Neos.Neos:Test.Content':
      superTypes:
        'Neos.Neos:Content': true

    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"

    When the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value             |
      | nodeAggregateId | "root"            |
      | nodeTypeName    | "Neos.Neos:Sites" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId | nodeTypeName                  | initialPropertyValues                                                | nodeName |
      | a               | root                  | Neos.Neos:Site                | {"title": "Node a"}                                                  | a        |
      | a1              | a                     | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1", "title": "Node a1"}                         | a1       |
      | a1a             | a1                    | Neos.Neos:Test.DocumentType2a | {"uriPathSegment": "a1a", "title": "Node a1a"}                       | a1a      |
      | a1b             | a1                    | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1b", "title": "Node a1b"}                       | a1b      |
      | a1b1            | a1b                   | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1b1", "title": "Node a1b1"}                     | a1b1     |
      | a1b1a           | a1b1                  | Neos.Neos:Test.DocumentType2a | {"uriPathSegment": "a1b1a", "title": "Node a1b1a"}                   | a1b1a    |
      | a1b1b           | a1b1                  | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1b1b", "title": "Node a1b1b"}                   | a1b1b    |
      | a1b2            | a1b                   | Neos.Neos:Test.DocumentType2  | {"uriPathSegment": "a1b2", "title": "Node a1b2"}                     | a1b2     |
      | a1b3            | a1b                   | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1b3", "title": "Node a1b3"}                     | a1b3     |
      | a1c             | a1                    | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1c", "title": "Node a1c", "hiddenInMenu": true} | a1c      |
      | a1c1            | a1c                   | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1c1", "title": "Node a1c1"}                     | a1c1     |
    And A site exists for node name "a" and domain "http://localhost"
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
    And the Fusion context node is "a1a"
    And the Fusion context request URI is "http://localhost"
    And I have the following Fusion setup:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    prototype(Neos.Neos:Test.Menu.ItemStateIndicator) < prototype(Neos.Fusion:Component) {
      state = null
      renderer = Neos.Fusion:Match {
        @subject = ${props.state}
        @default = '?'
        normal = ''
        current = '*'
        active = '.'
        absent = 'x'
      }
    }

    prototype(Neos.Neos:Test.Menu) < prototype(Neos.Fusion:Component) {
      items = ${[]}
      renderer = Neos.Fusion:Loop {
        items = ${props.items}
        itemRenderer = afx`
          {q(item.node).id()}<Neos.Neos:Test.Menu.ItemStateIndicator state={item.state.value} /> ({item.menuLevel}){String.chr(10)}
          <Neos.Neos:Test.Menu items={item.subItems} @if={item.subItems} />
        `
      }
    }

    # Always calculate menu item state to get Neos < 9 behavior
    prototype(Neos.Neos:MenuItems) {
      calculateItemStates = true
    }

    """

  Scenario: MenuItems (default)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems
    }
    """
    Then I expect the following Fusion rendering result:
    """
    a1. (1)
    a1a* (2)
    a1b (2)

    """

  Scenario: MenuItems (default on home page)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems
    }
    """
    And the Fusion context node is "a"
    Then I expect the following Fusion rendering result:
    """
    a1. (1)
    a1a* (2)
    a1b (2)

    """

  Scenario: MenuItems (maximumLevels = 3)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems {
        maximumLevels = 3
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """
    a1. (1)
    a1a* (2)
    a1b (2)
    a1b1 (3)
    a1b2 (3)
    a1b3 (3)

    """

  Scenario: MenuItems (entryLevel = -5)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems {
        entryLevel = -5
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """

    """


  Scenario: MenuItems (entryLevel = -1)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems {
        entryLevel = -1
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """
    a1a* (1)
    a1b (1)
    a1b1 (2)
    a1b2 (2)
    a1b3 (2)

    """

  Scenario: MenuItems (entryLevel = 0)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems {
        entryLevel = 0
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """

    """

  Scenario: MenuItems (entryLevel = 2)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems {
        entryLevel = 2
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """
    a1a* (1)
    a1b (1)
    a1b1 (2)
    a1b2 (2)
    a1b3 (2)

    """

  Scenario: MenuItems (entryLevel = 5)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems {
        entryLevel = 5
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """

    """

  Scenario: MenuItems (lastLevel = -5)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems {
        lastLevel = -5
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """

    """

  Scenario: MenuItems (lastLevel = -1)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems {
        lastLevel = -1
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """
    a1. (1)
    a1a* (2)
    a1b (2)

    """

  Scenario: MenuItems (lastLevel = 0)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems {
        lastLevel = 0
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """
    a1. (1)
    a1a* (2)
    a1b (2)

    """

  Scenario: MenuItems (lastLevel = 1)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems {
        lastLevel = 1
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """
    a1. (1)

    """

  Scenario: MenuItems (filter non existing node type)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems {
        filter = 'Non.Existing:NodeType'
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """
    """

  Scenario: MenuItems (filter = DocumentType1)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems {
        filter = 'Neos.Neos:Test.DocumentType1'
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """
    a1. (1)
    a1b (2)

    """

  Scenario: MenuItems (filter = DocumentType2)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems {
        filter = 'Neos.Neos:Test.DocumentType2'
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """

    """

  Scenario: MenuItems (renderHiddenInMenu)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems {
        renderHiddenInMenu = true
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """
    a1. (1)
    a1a* (2)
    a1b (2)
    a1c (2)

    """

  Scenario: MenuItems (empty itemCollection)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems {
        itemCollection = ${[]}
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """
    """

  Scenario: MenuItems (itemCollection document nodes)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems {
        itemCollection = ${q(site).filter('[instanceof Neos.Neos:Document]').get()}
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """
    a. (1)
    a1. (2)

    """

  Scenario: MenuItems (startingPoint a1b1)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems {
        startingPoint = ${q(node).children('[instanceof Neos.Neos:Document]').children('[instanceof Neos.Neos:Document]').get(0)}
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """
    a1. (1)
    a1a* (2)
    a1b (2)

    """

  Scenario: MenuItems (startingPoint a1b1, negative entryLevel)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems {
        startingPoint = ${q(node).children('[instanceof Neos.Neos:Document]').children('[instanceof Neos.Neos:Document]').get(0)}
        entryLevel = -1
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """
    a1a* (1)
    a1b (1)
    a1b1 (2)
    a1b2 (2)
    a1b3 (2)

    """

  Scenario: MenuItems (startingPoint a1b1, negative lastLevel)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems {
        startingPoint = ${q(node).children('[instanceof Neos.Neos:Document]').children('[instanceof Neos.Neos:Document]').get(0)}
        lastLevel = -1
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """
    a1. (1)
    a1a* (2)
    a1b (2)

    """

  Scenario: MenuItems (startingPoint a1b, filter DocumentType2a)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems {
        startingPoint = ${q(node).children('[instanceof Neos.Neos:Document]').get(0)}
        filter = 'Neos.Neos:Test.DocumentType2a'
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """

    """

  Scenario: MenuItems (startingPoint a1c, renderHiddenInMenu)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.Menu {
      items = Neos.Neos:MenuItems {
        startingPoint = ${q(node).find('#a1c').get(0)}
        renderHiddenInMenu = true
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """
    a1c1 (1)

    """

  Scenario: Menu
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Neos:Menu {
      # calculate menu item state to get Neos < 9 behavior
      calculateItemStates = true
    }
    """
    Then I expect the following Fusion rendering result as HTML:
    """html
    <ul>
        <li class="active">
            <a href="/a1" title="Neos.Neos:Test.DocumentType1 (a1)">Neos.Neos:Test.DocumentType1 (a1)</a>
            <ul>
                <li class="current">
                    <a href="/a1/a1a" title="Neos.Neos:Test.DocumentType2a (a1a)">Neos.Neos:Test.DocumentType2a (a1a)</a>
                </li>
                <li class="normal">
                    <a href="/a1/a1b" title="Neos.Neos:Test.DocumentType1 (a1b)">Neos.Neos:Test.DocumentType1 (a1b)</a>
                </li>
            </ul>
        </li>
    </ul>
    """

  Scenario: BreadcrumbMenu
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Neos:BreadcrumbMenu {
      # calculate menu item state to get Neos < 9 behavior
      calculateItemStates = true
    }
    """
    Then I expect the following Fusion rendering result as HTML:
    """html
    <ul>
      <li class="active">
        <a href="/" title="Neos.Neos:Site (a)">Neos.Neos:Site (a)</a>
      </li>
      <li class="active">
        <a href="/a1" title="Neos.Neos:Test.DocumentType1 (a1)">Neos.Neos:Test.DocumentType1 (a1)</a>
      </li>
      <li class="current">
        <a href="/a1/a1a" title="Neos.Neos:Test.DocumentType2a (a1a)">Neos.Neos:Test.DocumentType2a (a1a)</a>
      </li>
    </ul>
    """
