======================================
Creating your first TYPO3 Neos website
======================================

You can use the site kickstarter in the installation to create a new site.

CSS and JavaScript Requirements
===============================

* the `<body>` tag is not allowed to have a CSS style with `position:relative`,
  as this breaks the positions of modal dialogs we show at various places.
  *Zurb Foundation* is one well-known framework which sets this as default, so
  if you use it, then fix the error with `body { position: static }`.