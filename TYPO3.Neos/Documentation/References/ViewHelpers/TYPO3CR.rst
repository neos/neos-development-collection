.. _`Content Repository ViewHelper Reference`:

Content Repository ViewHelper Reference
=======================================

This reference was automatically generated from code on 2016-06-09


.. _`Content Repository ViewHelper Reference: PaginateViewHelper`:

PaginateViewHelper
------------------

This ViewHelper renders a Pagination of nodes.

:Implementation: TYPO3\\TYPO3CR\\ViewHelpers\\Widget\\PaginateViewHelper




Arguments
*********

* ``as`` (string): Variable name for the result set

* ``parentNode`` (TYPO3\TYPO3CR\Domain\Model\NodeInterface, *optional*): The parent node of the child nodes to show (instead of specifying the specific node set)

* ``nodes`` (array, *optional*): The specific collection of nodes to use for this paginator (instead of specifying the parentNode)

* ``nodeTypeFilter`` (string, *optional*): A node type (or more complex filter) to filter for in the results

* ``configuration`` (array, *optional*): Additional configuration

* ``widgetId`` (string, *optional*): Unique identifier of the widget instance



