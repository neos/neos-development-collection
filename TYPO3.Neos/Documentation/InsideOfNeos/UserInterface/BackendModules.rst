=========================
Backend Module Principles
=========================

For backend modules (that is, every module except the *content* area), we use
the following guiding principles in addition to the already-existing principles:

* It should be possible to write backend modules only with PHP, without JavaScript involved
* Some features might be only available to the user if he has JavaScript enabled
* In order to introduce rich behavior, use the technique of *progressive enhancement*

Progressive Enhancement
=======================

As we want to use progressive enhancement heavily, we need to define some rules
as a basis for that.

First, you should always think about the non-javascript functionality, and develop
the feature without JavaScript enabled. This helps to get the client-server communication
function correctly.

For most parts, you should not rely at all on any server state, but instead use
URI parameters to encode required state. This makes the server-side code a lot easier
and progressive enhancement more predictable.

Furthermore, if you reload certain parts of the user interface using AJAX, make
sure to *always update the browser's URI* using History Management: In case there
is an error, the user can just re-load the page and will get pretty much the
same User Interface state. This fulfills our UI goal of "predictable UI behavior".

Connecting JavaScript code to the HTML content
----------------------------------------------

In order to connect JavaScript code to HTML content, we (of course) rely on CSS
selectors for finding the correct DOM nodes. However, we do *not* want to use
CSS class attributes, as they change more frequently. Instead, we'd like to use
special data-attributes to connect the JavaScript code to the user interface.

.. note:: In a nutshell:

   * **CSS classes** are used for the **visible styling** only
   * **HTML5 Data Attributes** are used for **connecting the JavaScript** code to HTML

We use the following data attributes for that:

* ``data-area`` is used to search for DOM nodes, for later usage in JavaScript.

  As an example, use ``<div class="foo" data-area="actionBar"></div>`` in the HTML
  and match it using ``$('[data-area=actionBar]')`` in JavaScript.
* ``data-json`` is used for transferring server-side state to the JavaScript as JSON.

  Example: We need the full URI parameters which have been used for the current rendering
  as array/object on the client side. Thus, the server side stores them inside
  ``<div style="display:none" data-json="uriParameters">{foo: 'bar'}</div>``.

  The JavaScript code then accesses them at a central place using ``JSON.parse($('[data-json=uriParameters]').text())``
  and makes them available using some public API.

* ``data-type`` is used to mark that certain parts of the website contain a client-side template
  language like handlebars.

  As an example for the action bar, we use the following code here::

     <button>
        Edit
        <span class="js" data-type="handlebars">
           {{#if multipleSelectionActive}} {{numberOfSelectedElements}} elements{{/if}}
        </span>
     </button>

   Then, on the client side in JavaScript, we use the handlebars template accordingly.


Adjusting the UI if JavaScript is (in-)active
---------------------------------------------

Often, you want to hide or show some controls depending on whether JavaScript
is enabled or disabled. By default, every DOM element is visible no matter whether
JavaScript is enabled or not.

If you want to show a DOM element only if JavaScript is **enabled**, use the CSS
class ``js``.

If you want to show a DOM element only if JavaScript is **disabled**, use the CSS
class ``nojs``.