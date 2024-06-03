@flowEntities @contentrepository
Feature: Test cases for node creation edge cases

  Scenario: Delete the succeeding sibling node in a virtual specialization and then create the node
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
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
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
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | nodeName          | parentNodeAggregateId | succeedingSiblingNodeAggregateId | nodeTypeName       | initialPropertyValues          |
    # Let's prepare some siblings to check orderings. Also, everything gets better with siblings.
      | elder-mc-nodeface    | elder-document    | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "elder"}    |
      | eldest-mc-nodeface   | eldest-document   | shernode-homes        | elder-mc-nodeface                | Neos.Neos:Document | {"uriPathSegment": "eldest"}   |
      | younger-mc-nodeface  | younger-document  | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "younger"}  |
      | youngest-mc-nodeface | youngest-document | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "youngest"} |
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "younger-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example":"spec"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | nodeName | parentNodeAggregateId | succeedingSiblingNodeAggregateId | nodeTypeName       | initialPropertyValues      |
      | nody-mc-nodeface | document | shernode-homes        | younger-mc-nodeface              | Neos.Neos:Document | {"uriPathSegment": "nody"} |

    Then I expect the documenturipath table to contain exactly:
      # source: 65901ded4f068dac14ad0dce4f459b29
      # spec: 9a723c057afa02982dae9d0b541739be
      # leafSpec: c60c44685475d0e2e4f2b964e6158ce2
      | dimensionspacepointhash            | uripath    | nodeaggregateidpath                                          | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename         |
      | "65901ded4f068dac14ad0dce4f459b29" | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | "9a723c057afa02982dae9d0b541739be" | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | "c60c44685475d0e2e4f2b964e6158ce2" | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | "65901ded4f068dac14ad0dce4f459b29" | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | "9a723c057afa02982dae9d0b541739be" | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | "c60c44685475d0e2e4f2b964e6158ce2" | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | "65901ded4f068dac14ad0dce4f459b29" | "elder"    | "lady-eleonode-rootford/shernode-homes/elder-mc-nodeface"    | "elder-mc-nodeface"      | "shernode-homes"         | "eldest-mc-nodeface"     | "nody-mc-nodeface"        | "Neos.Neos:Document" |
      | "9a723c057afa02982dae9d0b541739be" | "elder"    | "lady-eleonode-rootford/shernode-homes/elder-mc-nodeface"    | "elder-mc-nodeface"      | "shernode-homes"         | "eldest-mc-nodeface"     | "nody-mc-nodeface"        | "Neos.Neos:Document" |
      | "c60c44685475d0e2e4f2b964e6158ce2" | "elder"    | "lady-eleonode-rootford/shernode-homes/elder-mc-nodeface"    | "elder-mc-nodeface"      | "shernode-homes"         | "eldest-mc-nodeface"     | "nody-mc-nodeface"        | "Neos.Neos:Document" |
      | "65901ded4f068dac14ad0dce4f459b29" | "eldest"   | "lady-eleonode-rootford/shernode-homes/eldest-mc-nodeface"   | "eldest-mc-nodeface"     | "shernode-homes"         | null                     | "elder-mc-nodeface"       | "Neos.Neos:Document" |
      | "9a723c057afa02982dae9d0b541739be" | "eldest"   | "lady-eleonode-rootford/shernode-homes/eldest-mc-nodeface"   | "eldest-mc-nodeface"     | "shernode-homes"         | null                     | "elder-mc-nodeface"       | "Neos.Neos:Document" |
      | "c60c44685475d0e2e4f2b964e6158ce2" | "eldest"   | "lady-eleonode-rootford/shernode-homes/eldest-mc-nodeface"   | "eldest-mc-nodeface"     | "shernode-homes"         | null                     | "elder-mc-nodeface"       | "Neos.Neos:Document" |
      | "65901ded4f068dac14ad0dce4f459b29" | "nody"     | "lady-eleonode-rootford/shernode-homes/nody-mc-nodeface"     | "nody-mc-nodeface"       | "shernode-homes"         | "elder-mc-nodeface"      | "younger-mc-nodeface"     | "Neos.Neos:Document" |
      | "9a723c057afa02982dae9d0b541739be" | "nody"     | "lady-eleonode-rootford/shernode-homes/nody-mc-nodeface"     | "nody-mc-nodeface"       | "shernode-homes"         | "elder-mc-nodeface"      | "youngest-mc-nodeface"    | "Neos.Neos:Document" |
      | "c60c44685475d0e2e4f2b964e6158ce2" | "nody"     | "lady-eleonode-rootford/shernode-homes/nody-mc-nodeface"     | "nody-mc-nodeface"       | "shernode-homes"         | "elder-mc-nodeface"      | "youngest-mc-nodeface"    | "Neos.Neos:Document" |
      | "65901ded4f068dac14ad0dce4f459b29" | "younger"  | "lady-eleonode-rootford/shernode-homes/younger-mc-nodeface"  | "younger-mc-nodeface"    | "shernode-homes"         | "nody-mc-nodeface"       | "youngest-mc-nodeface"    | "Neos.Neos:Document" |
      | "65901ded4f068dac14ad0dce4f459b29" | "youngest" | "lady-eleonode-rootford/shernode-homes/youngest-mc-nodeface" | "youngest-mc-nodeface"   | "shernode-homes"         | "younger-mc-nodeface"    | null                      | "Neos.Neos:Document" |
      | "9a723c057afa02982dae9d0b541739be" | "youngest" | "lady-eleonode-rootford/shernode-homes/youngest-mc-nodeface" | "youngest-mc-nodeface"   | "shernode-homes"         | "nody-mc-nodeface"       | null                      | "Neos.Neos:Document" |
      | "c60c44685475d0e2e4f2b964e6158ce2" | "youngest" | "lady-eleonode-rootford/shernode-homes/youngest-mc-nodeface" | "youngest-mc-nodeface"   | "shernode-homes"         | "nody-mc-nodeface"       | null                      | "Neos.Neos:Document" |
