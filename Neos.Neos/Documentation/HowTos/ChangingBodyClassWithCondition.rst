========================================
Changing the Body Class with a condition
========================================

In some cases there is the need to define different body classes based on a certain condition.

It can for example be that if a page has sub pages then we want to add a body class tag for this.

TypoScript code::

    page {
        bodyTag {
            attributes.class = ${q(node).children().count() > 1 ? 'has-subpages' : ''}
        }
    }

First of all we add the part called `bodyTag` to the TypoScript page object. Then inside we
add the `attributes.class`.

Then we add a FlowQuery that checks if the current node has any children.
If the condition is true then the class "has-subpages" is added to the body tag on all
pages that have any children.

An other example could be that we want to check if the current page is of type page.

TypoScript code::

    page {
        bodyTag {
            attributes.class = ${q(node).filter('[instanceof Neos.Neos:Page]') != '' ? 'is-page' : ''}
        }
    }
