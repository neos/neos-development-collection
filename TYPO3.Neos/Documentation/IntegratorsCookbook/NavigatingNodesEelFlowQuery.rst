=======================================
Navigating Nodes with Eel and FlowQuery
=======================================

(Sebastian H.)

Finding the closest node on the rootline having a layout set:

* q(node).parents('[layout]').first()

Get the siblings of a node:

* q(node).siblings()

Get the children of a node:

* q(node).children()
