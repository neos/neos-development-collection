The fixture setup we are testing is as follows:

- we have a fixed node structure
- we have a fixed list of content types (see Configuration/Testing/Settings.yaml)
- we have various TypoScript files

Node Structure:

- home (page) (title: Home)
  - teaser (section)
    - ___ (TextWithHeadline)
      - headline: 'Welcome to this example'
      - text: 'This is our exemplary rendering test. Check it out and play around!'
  - main (section)
    - ___ (TextWithHeadline)
      - headline: 'Do you love FLOW3?'
      - text: 'If you do, make sure to post your opinion about it on Twitter!'
    - ___ (HTML)
      - source: '[TWITTER WIDGET]'
    - ___ (ThreeColumn)
      - left (section)
        - ___ (TextWithHeadline)
          - headline: 'Documentation'
          - text: 'We're still improving our docs, but check them out nevertheless! Our presentations are also worth a visit'
        - ___ (HTML)
          - source: '[SLIDESHARE]'
      - center (section)
        - ___ (TextWithHeadline)
          - headline: 'Development Process'
          - text: 'We're spending lots of thought into our infrastructure, you can profit from that, too!'
      - right (section)
  - sidebar (section)
    - ___ (TextWithHeadline)
      - headline: 'Last Commits'
      - text: 'Below, you'll see the most recent activity'
    - ___ (HTML)
      - source: '[COMMIT WIDGET]'


That is, we want to simulate the following layout with it:


      +-------------------------------------------+
      | +--------------------------------------+  |
      | |      WELCOME TO THIS EXAMPLE         |  |
      | |This is our exemplary rendering test..|  |
      | +--------------------------------------+  |
      +---------------------------------+---------+
      | +----------------------------+  |+-------+|
      | |  DO YOU LOVE FLOW3?        |  ||LAST   ||
      | |                            |  ||COMMITS||
      | |  If you do, make sure ...  |  ||       ||
      | +----------------------------+  ||lorem i||
      | +----------------------------+  |+-------+|
      | |      [TWITTER WIDGET]      |  |         |
      | +----------------------------+  |+-------+|
      | +----------------------------+  ||[COMMIT||
      | |+--------++-------++-------+|  ||WIDGET]||
      | ||+------+||+-----+||       ||  ||       ||
      | |||DOCUM.||||Devel|||       ||  |+-------+|
      | |||...   ||||proc.|||       ||  |         |
        ||+------+||+-----+||       ||
        ||        ||       ||       ||
        ||+------+||       ||       ||
        |||Slide-|||       ||       ||
        |||share |||       ||       ||
        ||+------+||       ||       ||
        ||        ||       ||       ||
        |+--------++-------++-------+|
        +----------------------------+

