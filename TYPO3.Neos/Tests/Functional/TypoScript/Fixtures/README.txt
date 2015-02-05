The fixture setup we are testing is as follows:

- we have a fixed node structure
- we have a fixed list of node types (see Configuration/Testing/Settings.yaml)
- we have various TypoScript files

Node Structure:

- home (page) (title: Home)
  - teaser (section)
    - ___ (TextWithHeadline)
      - title: 'Welcome to this example'
      - text: 'This is our exemplary rendering test. Check it out and play around!'
  - main (section)
    - ___ (TextWithHeadline)
      - title: 'Do you love TYPO3 Flow?'
      - text: 'If you do, make sure to post your opinion about it on Twitter!'
    - ___ (HTML)
      - source: '[TWITTER WIDGET]'
    - ___ (ThreeColumn)
      - left (section)
        - ___ (TextWithHeadline)
          - title: 'Documentation'
          - text: 'We're still improving our docs, but check them out nevertheless! Our presentations are also worth a visit'
        - ___ (HTML)
          - source: '[SLIDESHARE]'
      - center (section)
        - ___ (TextWithHeadline)
          - title: 'Development Process'
          - text: 'We're spending lots of thought into our infrastructure, you can profit from that, too!'
      - right (section)
  - sidebar (section)
    - ___ (TextWithHeadline)
      - title: 'Last Commits'
      - text: 'Below, you'll see the most recent activity'
    - ___ (HTML)
      - source: '[COMMIT WIDGET]'

  - about-us (page) (title: About Us)
    - history (page) (title: History)

  - products (page) (title: Products)
    - frameworks (page) (title: Frameworks)
      - typo3-flow (page) (title: TYPO3 Flow)
    - cms (page) (title: CMS)
      - typo3-neos (page) (title: TYPO3 Neos)


That is, we want to simulate the following layout with it:


      +-------------------------------------------+
      | +--------------------------------------+  |
      | |      WELCOME TO THIS EXAMPLE         |  |
      | |This is our exemplary rendering test..|  |
      | +--------------------------------------+  |
      +---------------------------------+---------+
      | +----------------------------+  |+-------+|
      | |  DO YOU LOVE TYPO3 Flow?   |  ||LAST   ||
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

