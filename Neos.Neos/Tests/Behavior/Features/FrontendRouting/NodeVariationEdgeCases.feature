@flowEntities
Feature: Test cases for node variation edge cases

  Scenario: Create peer variant of node to dimension space point with specializations
    Given using the following content dimensions:
      | Identifier | Values               | Generalizations |
      | example    | source,peer,peerSpec | peerSpec->peer  |
    And using the following node types:
    """yaml
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Document':
      properties:
        uriPathSegment:
          type: string
    'Neos.Neos:Site':
      superTypes:
        'Neos.Neos:Document': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {"example":"source"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
      | nodeTypeName    | "Neos.Neos:Sites"        |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                    |
      | nodeAggregateId           | "shernode-homes"         |
      | nodeTypeName              | "Neos.Neos:Site"         |
      | parentNodeAggregateId     | "lady-eleonode-rootford" |
      | originDimensionSpacePoint | {"example":"source"}     |
      | nodeName                  | "site"                   |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                |
      | nodeAggregateId | "shernode-homes"     |
      | sourceOrigin    | {"example":"source"} |
      | targetOrigin    | {"example":"peer"}   |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | originDimensionSpacePoint | nodeName          | parentNodeAggregateId | succeedingSiblingNodeAggregateId | nodeTypeName       | initialPropertyValues          |
    # Set up our test subject document
      | nody-mc-nodeface     | {"example":"source"}      | document          | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "nody"}     |
    # Let's create some siblings, both in source and target, to check ordering
      | elder-mc-nodeface    | {"example":"source"}      | elder-document    | shernode-homes        | nody-mc-nodeface                 | Neos.Neos:Document | {"uriPathSegment": "elder"}    |
      | youngest-mc-nodeface | {"example":"source"}      | youngest-document | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "youngest"} |
      | eldest-mc-nodeface   | {"example":"peer"}        | eldest-document   | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "eldest"}   |

    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                |
      | nodeAggregateId | "nody-mc-nodeface"   |
      | sourceOrigin    | {"example":"source"} |
      | targetOrigin    | {"example":"peer"}   |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                |
      | nodeAggregateId | "elder-mc-nodeface"  |
      | sourceOrigin    | {"example":"source"} |
      | targetOrigin    | {"example":"peer"}   |
    # Complete the sibling set with a node in the target DSP between the middle and last node
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                         |
      | nodeAggregateId           | "younger-mc-nodeface"         |
      | nodeTypeName              | "Neos.Neos:Document"          |
      | parentNodeAggregateId     | "shernode-homes"              |
      | originDimensionSpacePoint | {"example":"peer"}            |
      | nodeName                  | "younger-document"            |
      | initialPropertyValues     | {"uriPathSegment": "younger"} |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                  |
      | nodeAggregateId | "youngest-mc-nodeface" |
      | sourceOrigin    | {"example":"source"}   |
      | targetOrigin    | {"example":"peer"}     |

    Then I expect the documenturipath table to contain exactly:
      | dimensionspacepointhash    | uripath    | nodeaggregateidpath                                          | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename         |
      | hash{"example":"peerSpec"} | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | hash{"example":"source"}   | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | hash{"example":"peer"}     | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | hash{"example":"peerSpec"} | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | hash{"example":"source"}   | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | hash{"example":"peer"}     | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | hash{"example":"peerSpec"} | "elder"    | "lady-eleonode-rootford/shernode-homes/elder-mc-nodeface"    | "elder-mc-nodeface"      | "shernode-homes"         | "eldest-mc-nodeface"     | "nody-mc-nodeface"        | "Neos.Neos:Document" |
      | hash{"example":"source"}   | "elder"    | "lady-eleonode-rootford/shernode-homes/elder-mc-nodeface"    | "elder-mc-nodeface"      | "shernode-homes"         | null                     | "nody-mc-nodeface"        | "Neos.Neos:Document" |
      | hash{"example":"peer"}     | "elder"    | "lady-eleonode-rootford/shernode-homes/elder-mc-nodeface"    | "elder-mc-nodeface"      | "shernode-homes"         | "eldest-mc-nodeface"     | "nody-mc-nodeface"        | "Neos.Neos:Document" |
      | hash{"example":"peerSpec"} | "eldest"   | "lady-eleonode-rootford/shernode-homes/eldest-mc-nodeface"   | "eldest-mc-nodeface"     | "shernode-homes"         | null                     | "elder-mc-nodeface"       | "Neos.Neos:Document" |
      | hash{"example":"peer"}     | "eldest"   | "lady-eleonode-rootford/shernode-homes/eldest-mc-nodeface"   | "eldest-mc-nodeface"     | "shernode-homes"         | null                     | "elder-mc-nodeface"       | "Neos.Neos:Document" |
      | hash{"example":"peerSpec"} | "nody"     | "lady-eleonode-rootford/shernode-homes/nody-mc-nodeface"     | "nody-mc-nodeface"       | "shernode-homes"         | "elder-mc-nodeface"      | "younger-mc-nodeface"     | "Neos.Neos:Document" |
      | hash{"example":"source"}   | "nody"     | "lady-eleonode-rootford/shernode-homes/nody-mc-nodeface"     | "nody-mc-nodeface"       | "shernode-homes"         | "elder-mc-nodeface"      | "youngest-mc-nodeface"    | "Neos.Neos:Document" |
      | hash{"example":"peer"}     | "nody"     | "lady-eleonode-rootford/shernode-homes/nody-mc-nodeface"     | "nody-mc-nodeface"       | "shernode-homes"         | "elder-mc-nodeface"      | "younger-mc-nodeface"     | "Neos.Neos:Document" |
      | hash{"example":"peerSpec"} | "younger"  | "lady-eleonode-rootford/shernode-homes/younger-mc-nodeface"  | "younger-mc-nodeface"    | "shernode-homes"         | "nody-mc-nodeface"       | "youngest-mc-nodeface"    | "Neos.Neos:Document" |
      | hash{"example":"peer"}     | "younger"  | "lady-eleonode-rootford/shernode-homes/younger-mc-nodeface"  | "younger-mc-nodeface"    | "shernode-homes"         | "nody-mc-nodeface"       | "youngest-mc-nodeface"    | "Neos.Neos:Document" |
      | hash{"example":"peerSpec"} | "youngest" | "lady-eleonode-rootford/shernode-homes/youngest-mc-nodeface" | "youngest-mc-nodeface"   | "shernode-homes"         | "younger-mc-nodeface"    | null                      | "Neos.Neos:Document" |
      | hash{"example":"source"}   | "youngest" | "lady-eleonode-rootford/shernode-homes/youngest-mc-nodeface" | "youngest-mc-nodeface"   | "shernode-homes"         | "nody-mc-nodeface"       | null                      | "Neos.Neos:Document" |
      | hash{"example":"peer"}     | "youngest" | "lady-eleonode-rootford/shernode-homes/youngest-mc-nodeface" | "youngest-mc-nodeface"   | "shernode-homes"         | "younger-mc-nodeface"    | null                      | "Neos.Neos:Document" |

  Scenario: Create peer variant of a shortcut with descendants and a parent node aggregate with differing uri path segments
    Given using the following content dimensions:
      | Identifier | Values       | Generalizations |
      | example    | peerA, peerB |                 |
    And using the following node types:
    """yaml
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Document':
      properties:
        uriPathSegment:
          type: string
    'Neos.Neos:Site':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Shortcut':
      superTypes:
        'Neos.Neos:Document': true
      properties:
        targetMode:
          type: string
        target:
          type: string
    'Neos.Neos:ExtendedShortcut':
      superTypes:
        'Neos.Neos:Shortcut': true
        'Neos.Neos:Document': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {"example":"peerA"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
      | nodeTypeName    | "Neos.Neos:Sites"        |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | originDimensionSpacePoint | nodeName       | parentNodeAggregateId  | nodeTypeName       | initialPropertyValues                                              |
      | shernode-homes         | {"example":"peerA"}       | site           | lady-eleonode-rootford | Neos.Neos:Site     | {}                                                                 |
      | sir-david-nodenborough | {"example":"peerA"}       | document       | shernode-homes         | Neos.Neos:Document | {"uriPathSegment": "sir-david-nodenborough"}                       |
      | nody-mc-nodeface       | {"example":"peerA"}       | document       | sir-david-nodenborough | Neos.Neos:ExtendedShortcut | {"targetMode": "parentNode", "uriPathSegment": "nody-mc-nodeface"} |
      | nodimus-mediocre       | {"example":"peerA"}       | child-document | nody-mc-nodeface       | Neos.Neos:Document | {"uriPathSegment": "nodimus-mediocre"}                             |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value               |
      | nodeAggregateId | "shernode-homes"    |
      | sourceOrigin    | {"example":"peerA"} |
      | targetOrigin    | {"example":"peerB"} |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"example":"peerA"}      |
      | targetOrigin    | {"example":"peerB"}      |
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                                   |
      | nodeAggregateId           | "sir-david-nodenborough"                      |
      | originDimensionSpacePoint | {"example":"peerB"}                     |
      | propertyValues            | {"uriPathSegment": "sir-david-nodenborough-b"} |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value               |
      | nodeAggregateId | "nody-mc-nodeface"  |
      | sourceOrigin    | {"example":"peerA"} |
      | targetOrigin    | {"example":"peerB"} |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value               |
      | nodeAggregateId | "nodimus-mediocre"  |
      | sourceOrigin    | {"example":"peerA"} |
      | targetOrigin    | {"example":"peerB"} |
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                                   |
      | nodeAggregateId           | "nody-mc-nodeface"                      |
      | originDimensionSpacePoint | {"example":"peerA"}                     |
      | propertyValues            | {"uriPathSegment": "nody-mc-nodeface-a"} |
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                                    |
      | nodeAggregateId           | "nodimus-mediocre"                       |
      | originDimensionSpacePoint | {"example":"peerB"}                      |
      | propertyValues            | {"uriPathSegment": "nodimus-mediocre-b"} |
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                                    |
      | nodeAggregateId           | "nody-mc-nodeface"                       |
      | originDimensionSpacePoint | {"example":"peerB"}                      |
      | propertyValues            | {"uriPathSegment": "nody-mc-nodeface-b"} |
    Then I expect the documenturipath table to contain exactly:
      | dimensionspacepointhash | uripath                                                          | nodeaggregateidpath                                                                              | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                 |
      | hash{"example":"peerB"} | ""                                                               | "lady-eleonode-rootford"                                                                         | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"            |
      | hash{"example":"peerA"} | ""                                                               | "lady-eleonode-rootford"                                                                         | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"            |
      | hash{"example":"peerB"} | ""                                                               | "lady-eleonode-rootford/shernode-homes"                                                          | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"             |
      | hash{"example":"peerA"} | ""                                                               | "lady-eleonode-rootford/shernode-homes"                                                          | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"             |
      | hash{"example":"peerB"} | "sir-david-nodenborough-b"                                       | "lady-eleonode-rootford/shernode-homes/sir-david-nodenborough"                                   | "sir-david-nodenborough" | "shernode-homes"         | null                     | null                      | "Neos.Neos:Document"         |
      | hash{"example":"peerA"} | "sir-david-nodenborough"                                         | "lady-eleonode-rootford/shernode-homes/sir-david-nodenborough"                                   | "sir-david-nodenborough" | "shernode-homes"         | null                     | null                      | "Neos.Neos:Document"         |
      | hash{"example":"peerB"} | "sir-david-nodenborough-b/nody-mc-nodeface-b"                    | "lady-eleonode-rootford/shernode-homes/sir-david-nodenborough/nody-mc-nodeface"                  | "nody-mc-nodeface"       | "sir-david-nodenborough" | null                     | null                      | "Neos.Neos:ExtendedShortcut" |
      | hash{"example":"peerA"} | "sir-david-nodenborough/nody-mc-nodeface-a"                      | "lady-eleonode-rootford/shernode-homes/sir-david-nodenborough/nody-mc-nodeface"                  | "nody-mc-nodeface"       | "sir-david-nodenborough" | null                     | null                      | "Neos.Neos:ExtendedShortcut" |
      | hash{"example":"peerB"} | "sir-david-nodenborough-b/nody-mc-nodeface-b/nodimus-mediocre-b" | "lady-eleonode-rootford/shernode-homes/sir-david-nodenborough/nody-mc-nodeface/nodimus-mediocre" | "nodimus-mediocre"       | "nody-mc-nodeface"       | null                     | null                      | "Neos.Neos:Document"         |
      | hash{"example":"peerA"} | "sir-david-nodenborough/nody-mc-nodeface-a/nodimus-mediocre"     | "lady-eleonode-rootford/shernode-homes/sir-david-nodenborough/nody-mc-nodeface/nodimus-mediocre" | "nodimus-mediocre"       | "nody-mc-nodeface"       | null                     | null                      | "Neos.Neos:Document"         |

  Scenario: Create generalization of node to dimension space point with further generalization and specializations
    Given using the following content dimensions:
      | Identifier | Values                              | Generalizations                                                   |
      | example    | rootGeneral, general, source, specB | source -> general -> rootGeneral, specB -> general -> rootGeneral |
    And using the following node types:
    """yaml
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Document':
      properties:
        uriPathSegment:
          type: string
    'Neos.Neos:Site':
      superTypes:
        'Neos.Neos:Document': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {"example":"source"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
      | nodeTypeName    | "Neos.Neos:Sites"        |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                     |
      | nodeAggregateId           | "shernode-homes"          |
      | nodeTypeName              | "Neos.Neos:Site"          |
      | parentNodeAggregateId     | "lady-eleonode-rootford"  |
      | originDimensionSpacePoint | {"example":"rootGeneral"} |
      | nodeName                  | "site"                    |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | originDimensionSpacePoint | nodeName          | parentNodeAggregateId | succeedingSiblingNodeAggregateId | nodeTypeName       | initialPropertyValues          |
    # Let's create some siblings, both in source and target, to check ordering
      | eldest-mc-nodeface   | {"example":"general"}     | eldest-document   | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "eldest"}   |
      | nody-mc-nodeface     | {"example":"source"}      | document          | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "nody"}     |
      | elder-mc-nodeface    | {"example":"source"}      | elder-document    | shernode-homes        | nody-mc-nodeface                 | Neos.Neos:Document | {"uriPathSegment": "elder"}    |
      | younger-mc-nodeface  | {"example":"general"}     | younger-document  | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "younger"}  |
      | youngest-mc-nodeface | {"example":"source"}      | youngest-document | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "youngest"} |

    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | nodeAggregateId | "nody-mc-nodeface"    |
      | sourceOrigin    | {"example":"source"}  |
      | targetOrigin    | {"example":"general"} |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | nodeAggregateId | "elder-mc-nodeface"   |
      | sourceOrigin    | {"example":"source"}  |
      | targetOrigin    | {"example":"general"} |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                  |
      | nodeAggregateId | "youngest-mc-nodeface" |
      | sourceOrigin    | {"example":"source"}   |
      | targetOrigin    | {"example":"general"}  |

    Then I expect the documenturipath table to contain exactly:
      | dimensionspacepointhash       | uripath    | nodeaggregateidpath                                          | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename         |
      | hash{"example":"general"}     | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | hash{"example":"source"}      | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | hash{"example":"sourceB"}     | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | hash{"example":"rootGeneral"} | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | hash{"example":"general"}     | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | hash{"example":"source"}      | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | hash{"example":"sourceB"}     | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | hash{"example":"rootGeneral"} | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | hash{"example":"general"}     | "elder"    | "lady-eleonode-rootford/shernode-homes/elder-mc-nodeface"    | "elder-mc-nodeface"      | "shernode-homes"         | "eldest-mc-nodeface"     | "nody-mc-nodeface"        | "Neos.Neos:Document" |
      | hash{"example":"source"}      | "elder"    | "lady-eleonode-rootford/shernode-homes/elder-mc-nodeface"    | "elder-mc-nodeface"      | "shernode-homes"         | "eldest-mc-nodeface"     | "nody-mc-nodeface"        | "Neos.Neos:Document" |
      | hash{"example":"sourceB"}     | "elder"    | "lady-eleonode-rootford/shernode-homes/elder-mc-nodeface"    | "elder-mc-nodeface"      | "shernode-homes"         | "eldest-mc-nodeface"     | "nody-mc-nodeface"        | "Neos.Neos:Document" |
      | hash{"example":"general"}     | "eldest"   | "lady-eleonode-rootford/shernode-homes/eldest-mc-nodeface"   | "eldest-mc-nodeface"     | "shernode-homes"         | null                     | "elder-mc-nodeface"       | "Neos.Neos:Document" |
      | hash{"example":"source"}      | "eldest"   | "lady-eleonode-rootford/shernode-homes/eldest-mc-nodeface"   | "eldest-mc-nodeface"     | "shernode-homes"         | null                     | "elder-mc-nodeface"       | "Neos.Neos:Document" |
      | hash{"example":"sourceB"}     | "eldest"   | "lady-eleonode-rootford/shernode-homes/eldest-mc-nodeface"   | "eldest-mc-nodeface"     | "shernode-homes"         | null                     | "elder-mc-nodeface"       | "Neos.Neos:Document" |
      | hash{"example":"general"}     | "nody"     | "lady-eleonode-rootford/shernode-homes/nody-mc-nodeface"     | "nody-mc-nodeface"       | "shernode-homes"         | "elder-mc-nodeface"      | "younger-mc-nodeface"     | "Neos.Neos:Document" |
      | hash{"example":"source"}      | "nody"     | "lady-eleonode-rootford/shernode-homes/nody-mc-nodeface"     | "nody-mc-nodeface"       | "shernode-homes"         | "elder-mc-nodeface"      | "younger-mc-nodeface"     | "Neos.Neos:Document" |
      | hash{"example":"sourceB"}     | "nody"     | "lady-eleonode-rootford/shernode-homes/nody-mc-nodeface"     | "nody-mc-nodeface"       | "shernode-homes"         | "elder-mc-nodeface"      | "younger-mc-nodeface"     | "Neos.Neos:Document" |
      | hash{"example":"general"}     | "younger"  | "lady-eleonode-rootford/shernode-homes/younger-mc-nodeface"  | "younger-mc-nodeface"    | "shernode-homes"         | "nody-mc-nodeface"       | "youngest-mc-nodeface"    | "Neos.Neos:Document" |
      | hash{"example":"source"}      | "younger"  | "lady-eleonode-rootford/shernode-homes/younger-mc-nodeface"  | "younger-mc-nodeface"    | "shernode-homes"         | "nody-mc-nodeface"       | "youngest-mc-nodeface"    | "Neos.Neos:Document" |
      | hash{"example":"sourceB"}     | "younger"  | "lady-eleonode-rootford/shernode-homes/younger-mc-nodeface"  | "younger-mc-nodeface"    | "shernode-homes"         | "nody-mc-nodeface"       | "youngest-mc-nodeface"    | "Neos.Neos:Document" |
      | hash{"example":"general"}     | "youngest" | "lady-eleonode-rootford/shernode-homes/youngest-mc-nodeface" | "youngest-mc-nodeface"   | "shernode-homes"         | "younger-mc-nodeface"    | null                      | "Neos.Neos:Document" |
      | hash{"example":"source"}      | "youngest" | "lady-eleonode-rootford/shernode-homes/youngest-mc-nodeface" | "youngest-mc-nodeface"   | "shernode-homes"         | "younger-mc-nodeface"    | null                      | "Neos.Neos:Document" |
      | hash{"example":"sourceB"}     | "youngest" | "lady-eleonode-rootford/shernode-homes/youngest-mc-nodeface" | "youngest-mc-nodeface"   | "shernode-homes"         | "younger-mc-nodeface"    | null                      | "Neos.Neos:Document" |

  Scenario: Delete the node in a virtual specialization and then create the node in that specialization, forcing the edges to be recreated
    Given using the following content dimensions:
      | Identifier | Values                 | Generalizations            |
      | example    | source, spec, leafSpec | leafSpec -> spec -> source |
    And using the following node types:
    """yaml
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Document':
      properties:
        uriPathSegment:
          type: string
    'Neos.Neos:Site':
      superTypes:
        'Neos.Neos:Document': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {"example":"source"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
      | nodeTypeName    | "Neos.Neos:Sites"        |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                    |
      | nodeAggregateId           | "shernode-homes"         |
      | nodeTypeName              | "Neos.Neos:Site"         |
      | parentNodeAggregateId     | "lady-eleonode-rootford" |
      | originDimensionSpacePoint | {"example":"source"}     |
      | nodeName                  | "site"                   |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | nodeName          | parentNodeAggregateId | succeedingSiblingNodeAggregateId | nodeTypeName       | initialPropertyValues          |
    # Let's create our test subject...
      | nody-mc-nodeface     | document          | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "nody"}     |
    # ...and add some siblings to check orderings. Also, everything gets better with siblings.
      | elder-mc-nodeface    | elder-document    | shernode-homes        | nody-mc-nodeface                 | Neos.Neos:Document | {"uriPathSegment": "elder"}    |
      | eldest-mc-nodeface   | eldest-document   | shernode-homes        | elder-mc-nodeface                | Neos.Neos:Document | {"uriPathSegment": "eldest"}   |
      | younger-mc-nodeface  | younger-document  | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "younger"}  |
      | youngest-mc-nodeface | youngest-document | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "youngest"} |
    And the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"example":"spec"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                |
      | contentStreamId | "cs-identifier"      |
      | nodeAggregateId | "nody-mc-nodeface"   |
      | sourceOrigin    | {"example":"source"} |
      | targetOrigin    | {"example":"spec"}   |

    Then I expect the documenturipath table to contain exactly:
      | dimensionspacepointhash    | uripath    | nodeaggregateidpath                                          | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename         |
      | hash{"example":"source"}   | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | hash{"example":"spec"}     | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | hash{"example":"leafSpec"} | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | hash{"example":"source"}   | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | hash{"example":"spec"}     | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | hash{"example":"leafSpec"} | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | hash{"example":"source"}   | "elder"    | "lady-eleonode-rootford/shernode-homes/elder-mc-nodeface"    | "elder-mc-nodeface"      | "shernode-homes"         | "eldest-mc-nodeface"     | "nody-mc-nodeface"        | "Neos.Neos:Document" |
      | hash{"example":"spec"}     | "elder"    | "lady-eleonode-rootford/shernode-homes/elder-mc-nodeface"    | "elder-mc-nodeface"      | "shernode-homes"         | "eldest-mc-nodeface"     | "nody-mc-nodeface"        | "Neos.Neos:Document" |
      | hash{"example":"leafSpec"} | "elder"    | "lady-eleonode-rootford/shernode-homes/elder-mc-nodeface"    | "elder-mc-nodeface"      | "shernode-homes"         | "eldest-mc-nodeface"     | "nody-mc-nodeface"        | "Neos.Neos:Document" |
      | hash{"example":"source"}   | "eldest"   | "lady-eleonode-rootford/shernode-homes/eldest-mc-nodeface"   | "eldest-mc-nodeface"     | "shernode-homes"         | null                     | "elder-mc-nodeface"       | "Neos.Neos:Document" |
      | hash{"example":"spec"}     | "eldest"   | "lady-eleonode-rootford/shernode-homes/eldest-mc-nodeface"   | "eldest-mc-nodeface"     | "shernode-homes"         | null                     | "elder-mc-nodeface"       | "Neos.Neos:Document" |
      | hash{"example":"leafSpec"} | "eldest"   | "lady-eleonode-rootford/shernode-homes/eldest-mc-nodeface"   | "eldest-mc-nodeface"     | "shernode-homes"         | null                     | "elder-mc-nodeface"       | "Neos.Neos:Document" |
      | hash{"example":"source"}   | "nody"     | "lady-eleonode-rootford/shernode-homes/nody-mc-nodeface"     | "nody-mc-nodeface"       | "shernode-homes"         | "elder-mc-nodeface"      | "younger-mc-nodeface"     | "Neos.Neos:Document" |
      | hash{"example":"spec"}     | "nody"     | "lady-eleonode-rootford/shernode-homes/nody-mc-nodeface"     | "nody-mc-nodeface"       | "shernode-homes"         | "elder-mc-nodeface"      | "younger-mc-nodeface"     | "Neos.Neos:Document" |
      | hash{"example":"leafSpec"} | "nody"     | "lady-eleonode-rootford/shernode-homes/nody-mc-nodeface"     | "nody-mc-nodeface"       | "shernode-homes"         | "elder-mc-nodeface"      | "younger-mc-nodeface"     | "Neos.Neos:Document" |
      | hash{"example":"source"}   | "younger"  | "lady-eleonode-rootford/shernode-homes/younger-mc-nodeface"  | "younger-mc-nodeface"    | "shernode-homes"         | "nody-mc-nodeface"       | "youngest-mc-nodeface"    | "Neos.Neos:Document" |
      | hash{"example":"spec"}     | "younger"  | "lady-eleonode-rootford/shernode-homes/younger-mc-nodeface"  | "younger-mc-nodeface"    | "shernode-homes"         | "nody-mc-nodeface"       | "youngest-mc-nodeface"    | "Neos.Neos:Document" |
      | hash{"example":"leafSpec"} | "younger"  | "lady-eleonode-rootford/shernode-homes/younger-mc-nodeface"  | "younger-mc-nodeface"    | "shernode-homes"         | "nody-mc-nodeface"       | "youngest-mc-nodeface"    | "Neos.Neos:Document" |
      | hash{"example":"source"}   | "youngest" | "lady-eleonode-rootford/shernode-homes/youngest-mc-nodeface" | "youngest-mc-nodeface"   | "shernode-homes"         | "younger-mc-nodeface"    | null                      | "Neos.Neos:Document" |
      | hash{"example":"spec"}     | "youngest" | "lady-eleonode-rootford/shernode-homes/youngest-mc-nodeface" | "youngest-mc-nodeface"   | "shernode-homes"         | "younger-mc-nodeface"    | null                      | "Neos.Neos:Document" |
      | hash{"example":"leafSpec"} | "youngest" | "lady-eleonode-rootford/shernode-homes/youngest-mc-nodeface" | "youngest-mc-nodeface"   | "shernode-homes"         | "younger-mc-nodeface"    | null                      | "Neos.Neos:Document" |
