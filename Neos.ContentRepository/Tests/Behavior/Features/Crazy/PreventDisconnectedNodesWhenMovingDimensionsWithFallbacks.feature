Feature: Prevent disconnected Node Variants when moving a document, in a setup with dimensions and with fallbacks

  To understand this tests, you first should have a look at PreventDisconnectedNodesWhenMovingDimensionsWithoutFallbacks
  Those are basically the same tests, but without any configured fallback nodes and therefore simpler.

Given I have the following content dimensions:
| Identifier | Default | Presets                                                                                        |
| language   | de      | de=de; ch=ch,de; at=at,de |
