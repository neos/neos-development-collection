@contentrepository
Feature: As a user of the CR I want to export the event stream
  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    Vendor.Site:HomePage':
      superTypes:
        Neos.Neos:Site: true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"

  Scenario: Import the event stream
    Then I expect exactly 0 events to be published on stream with prefix "ContentStream:cs-identifier"
    Given using the following events.jsonl:
      """
      {"identifier":"9f64c281-e5b0-48d9-900b-288a8faf92a9","type":"RootNodeAggregateWithNodeWasCreated","payload":{"contentStreamId":"cs-imported-identifier","nodeAggregateId":"acme-site-sites","nodeTypeName":"Neos.Neos:Sites","coveredDimensionSpacePoints":[[]],"nodeAggregateClassification":"root"},"metadata":[]}
      {"identifier":"1640ebbf-7ffe-4526-b0f4-7575cefabfab","type":"NodeAggregateWithNodeWasCreated","payload":{"contentStreamId":"cs-imported-identifier","nodeAggregateId":"acme-site","nodeTypeName":"Vendor.Site:HomePage","originDimensionSpacePoint":[],"coveredDimensionSpacePoints":[[]],"parentNodeAggregateId":"acme-site-sites","nodeName":"acme-site","initialPropertyValues":{"title":{"value":"My Site","type":"string"},"uriPathSegment":{"value":"my-site","type":"string"}},"nodeAggregateClassification":"regular","succeedingNodeAggregateId":null},"metadata":[]}
      """
    And I import the events.jsonl into "cs-identifier"
    Then I expect exactly 3 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 0 is of type "ContentStreamWasCreated" with payload:
      | Key                         | Expected                      |
      | contentStreamId             | "cs-identifier"               |
    And event at index 1 is of type "RootNodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                      |
      | contentStreamId             | "cs-identifier"               |
      | nodeAggregateId             | "acme-site-sites"             |
      | nodeTypeName                | "Neos.Neos:Sites"             |
    And event at index 2 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                      |
      | contentStreamId             | "cs-identifier"               |
      | nodeAggregateId             | "acme-site"                   |
      | nodeTypeName                | "Vendor.Site:HomePage"        |

  Scenario: Import the event stream into a specific content stream
    Then I expect exactly 0 events to be published on stream with prefix "ContentStream:cs-imported-identifier"
    Given using the following events.jsonl:
      """
      {"identifier":"9f64c281-e5b0-48d9-900b-288a8faf92a9","type":"RootNodeAggregateWithNodeWasCreated","payload":{"contentStreamId":"cs-imported-identifier","nodeAggregateId":"acme-site-sites","nodeTypeName":"Neos.Neos:Sites","coveredDimensionSpacePoints":[[]],"nodeAggregateClassification":"root"},"metadata":[]}
      {"identifier":"1640ebbf-7ffe-4526-b0f4-7575cefabfab","type":"NodeAggregateWithNodeWasCreated","payload":{"contentStreamId":"cs-imported-identifier","nodeAggregateId":"acme-site","nodeTypeName":"Vendor.Site:HomePage","originDimensionSpacePoint":[],"coveredDimensionSpacePoints":[[]],"parentNodeAggregateId":"acme-site-sites","nodeName":"acme-site","initialPropertyValues":{"title":{"value":"My Site","type":"string"},"uriPathSegment":{"value":"my-site","type":"string"}},"nodeAggregateClassification":"regular","succeedingNodeAggregateId":null},"metadata":[]}
      """
    And I import the events.jsonl
    Then I expect exactly 3 events to be published on stream with prefix "ContentStream:cs-imported-identifier"
    And event at index 0 is of type "ContentStreamWasCreated" with payload:
      | Key                         | Expected                      |
      | contentStreamId             | "cs-imported-identifier"      |
    And event at index 1 is of type "RootNodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                      |
      | contentStreamId             | "cs-imported-identifier"      |
      | nodeAggregateId             | "acme-site-sites"             |
      | nodeTypeName                | "Neos.Neos:Sites"             |
    And event at index 2 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                      |
      | contentStreamId             | "cs-imported-identifier"      |
      | nodeAggregateId             | "acme-site"                   |
      | nodeTypeName                | "Vendor.Site:HomePage"        |

  Scenario: Import faulty event stream with explicit "ContentStreamWasCreated" does not duplicate content-stream
    see issue https://github.com/neos/neos-development-collection/issues/4298

    Given using the following events.jsonl:
      """
      {"identifier":"5f2da12d-7037-4524-acb0-d52037342c77","type":"ContentStreamWasCreated","payload":{"contentStreamId":"cs-imported-identifier"},"metadata":[]}
      {"identifier":"9f64c281-e5b0-48d9-900b-288a8faf92a9","type":"RootNodeAggregateWithNodeWasCreated","payload":{"contentStreamId":"cs-imported-identifier","nodeAggregateId":"acme-site-sites","nodeTypeName":"Neos.Neos:Sites","coveredDimensionSpacePoints":[[]],"nodeAggregateClassification":"root"},"metadata":[]}
      {"identifier":"1640ebbf-7ffe-4526-b0f4-7575cefabfab","type":"NodeAggregateWithNodeWasCreated","payload":{"contentStreamId":"cs-imported-identifier","nodeAggregateId":"acme-site","nodeTypeName":"Vendor.Site:HomePage","originDimensionSpacePoint":[],"coveredDimensionSpacePoints":[[]],"parentNodeAggregateId":"acme-site-sites","nodeName":"acme-site","initialPropertyValues":{"title":{"value":"My Site","type":"string"},"uriPathSegment":{"value":"my-site","type":"string"}},"nodeAggregateClassification":"regular","succeedingNodeAggregateId":null},"metadata":[]}
      """
    And I import the events.jsonl

    And I expect the following errors to be logged
      | Skipping explicit content stream creation event. The export format should not contain "ContentStreamWasCreated". |

    Then I expect exactly 3 events to be published on stream with prefix "ContentStream:cs-imported-identifier"
    And event at index 0 is of type "ContentStreamWasCreated" with payload:
      | Key                         | Expected                      |
      | contentStreamId             | "cs-imported-identifier"      |
    And event at index 1 is of type "RootNodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                      |
      | contentStreamId             | "cs-imported-identifier"      |
      | nodeAggregateId             | "acme-site-sites"             |
      | nodeTypeName                | "Neos.Neos:Sites"             |
    And event at index 2 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                      |
      | contentStreamId             | "cs-imported-identifier"      |
      | nodeAggregateId             | "acme-site"                   |
      | nodeTypeName                | "Vendor.Site:HomePage"        |
