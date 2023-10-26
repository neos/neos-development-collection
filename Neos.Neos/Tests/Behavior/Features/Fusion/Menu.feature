@fixtures
Feature: Tests for the "Neos.Neos:Menu" and related Fusion prototypes

  Background:
    Given I have the site "a"
    And I have the following NodeTypes configuration:
    """yaml
    'unstructured': {}
    'Neos.Neos:FallbackNode': {}
    'Neos.Neos:Document':
      properties:
        title:
          type: string
        uriPathSegment:
          type: string
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
    And I have the following nodes:
      | Identifier | Path                       | Node Type                     | Properties                                         | Hidden in index |
      | root       | /sites                     | unstructured                  |                                                    | false           |
      | a          | /sites/a                   | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a", "title": "Node a"}         | false           |
      | a1         | /sites/a/a1                | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1", "title": "Node a1"}       | false           |
      | a1a        | /sites/a/a1/a1a            | Neos.Neos:Test.DocumentType2a | {"uriPathSegment": "a1a", "title": "Node a1a"}     | false           |
      | a1b        | /sites/a/a1/a1b            | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1b", "title": "Node a1b"}     | false           |
      | a1b1       | /sites/a/a1/a1b/a1b1       | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1b1", "title": "Node a1b1"}   | false           |
      | a1b1a      | /sites/a/a1/a1b/a1b1/a1b1a | Neos.Neos:Test.DocumentType2a | {"uriPathSegment": "a1b1a", "title": "Node a1b1a"} | false           |
      | a1b1b      | /sites/a/a1/a1b/a1b1/a1b1b | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1b1b", "title": "Node a1b1b"} | false           |
      | a1b2       | /sites/a/a1/a1b/a1b2       | Neos.Neos:Test.DocumentType2  | {"uriPathSegment": "a1b2", "title": "Node a1b2"}   | false           |
      | a1b3       | /sites/a/a1/a1b/a1b3       | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1b3", "title": "Node a1b3"}   | false           |
      | a1c        | /sites/a/a1/a1c            | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1c", "title": "Node a1c"}     | true            |
      | a1c1       | /sites/a/a1/a1c/a1c1       | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1c1", "title": "Node a1c1"}   | false           |
    And the Fusion context node is "a1a"
    And the Fusion context request URI is "http://localhost"

  Scenario: MenuItems
    When I execute the following Fusion code:
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
          {item.node.identifier}<Neos.Neos:Test.Menu.ItemStateIndicator state={item.state} /> ({item.menuLevel}){String.chr(10)}
          <Neos.Neos:Test.Menu items={item.subItems} @if={item.subItems} />
        `
      }
    }

   test = Neos.Fusion:DataStructure {
      default = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems
      }
      maximumLevels_3 = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems {
          maximumLevels = 3
        }
      }
      entryLevel_negative = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems {
          entryLevel = -1
        }
      }
      entryLevel_negative_out_of_range = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems {
          entryLevel = -5
        }
      }
      entryLevel_0 = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems {
          entryLevel = 0
        }
      }
      entryLevel_2 = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems {
          entryLevel = 2
        }
      }
      entryLevel_positive_out_of_range = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems {
          entryLevel = 5
        }
      }
      lastLevel_negative = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems {
          lastLevel = -1
        }
      }
      lastLevel_negative_out_of_range = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems {
          lastLevel = -5
        }
      }
      lastLevel_0 = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems {
          lastLevel = 0
        }
      }
      lastLevel_1 = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems {
          lastLevel = 1
        }
      }
      filter_nonExisting = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems {
          filter = 'Non.Existing:NodeType'
        }
      }
      filter_DocumentType1 = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems {
          filter = 'Neos.Neos:Test.DocumentType1'
        }
      }
      filter_DocumentType2 = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems {
          filter = 'Neos.Neos:Test.DocumentType2'
        }
      }
      renderHiddenInIndex = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems {
          renderHiddenInIndex = true
        }
      }
      itemCollection_empty = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems {
          itemCollection = ${[]}
        }
      }
      itemCollection_documentNodes = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems {
          itemCollection = ${q(site).filter('[instanceof Neos.Neos:Document]').get()}
        }
      }
      startingPoint_a1b1 = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems {
          startingPoint = ${q(node).children('[instanceof Neos.Neos:Document]').children('[instanceof Neos.Neos:Document]').get(0)}
        }
      }
      startingPoint_a1b1_entryLevel_negative = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems {
          startingPoint = ${q(node).children('[instanceof Neos.Neos:Document]').children('[instanceof Neos.Neos:Document]').get(0)}
          entryLevel = -1
        }
      }
      startingPoint_a1b1_lastLevel_negative = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems {
          startingPoint = ${q(node).children('[instanceof Neos.Neos:Document]').children('[instanceof Neos.Neos:Document]').get(0)}
          lastLevel = -1
        }
      }
      startingPoint_a1b_filter_DocumentType2a = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems {
          startingPoint = ${q(node).children('[instanceof Neos.Neos:Document]').get(0)}
          filter = 'Neos.Neos:Test.DocumentType2a'
        }
      }
      startingPoint_a1c_renderHiddenInIndex = Neos.Neos:Test.Menu {
        items = Neos.Neos:MenuItems {
          startingPoint = ${q(node).find('#a1c').get(0)}
          renderHiddenInIndex = true
        }
      }
    }
    test.@process.join = ${Array.join(Array.map(Array.keys(value), k => k + ':' + String.chr(10) + String.trim(value[k])), String.chr(10) + String.chr(10))}
    """
    Then I expect the following Fusion rendering result:
    """
    default:
    a1. (1)
    a1a* (2)
    a1b (2)

    maximumLevels_3:
    a1. (1)
    a1a* (2)
    a1b (2)
    a1b1 (3)
    a1b2 (3)
    a1b3 (3)

    entryLevel_negative:
    a1a* (1)
    a1b (1)
    a1b1 (2)
    a1b2 (2)
    a1b3 (2)

    entryLevel_negative_out_of_range:
    a1. (1)
    a1a* (2)
    a1b (2)

    entryLevel_0:


    entryLevel_2:
    a1a* (1)
    a1b (1)
    a1b1 (2)
    a1b2 (2)
    a1b3 (2)

    entryLevel_positive_out_of_range:


    lastLevel_negative:
    a1. (1)
    a1a* (2)
    a1b (2)

    lastLevel_negative_out_of_range:
    a1. (1)

    lastLevel_0:
    a1. (1)
    a1a* (2)
    a1b (2)

    lastLevel_1:
    a1. (1)

    filter_nonExisting:


    filter_DocumentType1:
    a1. (1)
    a1b (2)

    filter_DocumentType2:


    renderHiddenInIndex:
    a1. (1)
    a1a* (2)
    a1b (2)
    a1c (2)

    itemCollection_empty:


    itemCollection_documentNodes:
    a (1)
    a1. (2)

    startingPoint_a1b1:
    a1. (1)
    a1a* (2)
    a1b (2)

    startingPoint_a1b1_entryLevel_negative:
    a1a* (1)
    a1b (1)
    a1b1 (2)
    a1b2 (2)
    a1b3 (2)

    startingPoint_a1b1_lastLevel_negative:
    a1. (1)
    a1a* (2)
    a1b (2)

    startingPoint_a1b_filter_DocumentType2a:


    startingPoint_a1c_renderHiddenInIndex:
    a1c1 (1)
    """

  Scenario: Menu
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Neos:Menu
    """
    Then I expect the following Fusion rendering result as HTML:
    """html
    <ul>
        <li class="active">
            <a href="/en/a1" title="Neos.Neos:Test.DocumentType1 (a1)">Neos.Neos:Test.DocumentType1 (a1)</a>
            <ul>
                <li class="current">
                    <a href="/en/a1/a1a" title="Neos.Neos:Test.DocumentType2a (a1a)">Neos.Neos:Test.DocumentType2a (a1a)</a>
                </li>
                <li class="normal">
                    <a href="/en/a1/a1b" title="Neos.Neos:Test.DocumentType1 (a1b)">Neos.Neos:Test.DocumentType1 (a1b)</a>
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

    test = Neos.Neos:BreadcrumbMenu
    """
    Then I expect the following Fusion rendering result as HTML:
    """html
    <ul class="breadcrumb">
      <li class="normal">
        <a href="/" title="Neos.Neos:Test.DocumentType1 (a)">Neos.Neos:Test.DocumentType1 (a)</a>
      </li>
      <li class="active">
        <a href="/en/a1" title="Neos.Neos:Test.DocumentType1 (a1)">Neos.Neos:Test.DocumentType1 (a1)</a>
      </li>
      <li class="current">Neos.Neos:Test.DocumentType2a (a1a)</li>
    </ul>
    """
