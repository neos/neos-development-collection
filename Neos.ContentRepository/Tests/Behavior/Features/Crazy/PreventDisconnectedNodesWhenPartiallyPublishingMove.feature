Feature: Prevent disconnected nodes when partially publishing a move

  Let's say we have the following Node structure in the beginning:

  |-- site
  . |-- cr
  . | |-- subpage
  . |   |-- nested
  . |-- other

  Now, user-demo moves /site/cr/subpage underneath /site/other/ in the user workspace. This means in the user workspace the following
  status exists:

  |-- site
  . |-- cr
  .   |-- subpage   SHADOW NODE in user-demo
  .     |-- nested  SHADOW NODE in user-demo
  . |-- other
  .   |-- subpage   user-demo
  .     |-- nested  user-demo

  Now, let's assume user-demo forgets about this (thus not publishing), and a few weeks later needs to do
  a text change on *subpage*.

  |-- site
  . |-- cr
  .   |-- subpage   live + SHADOW NODE in user-demo <--- (2) ... and this is published as well; removing the subpage in live
  .     |-- nested  live + SHADOW NODE in user-demo
  . |-- other
  .   |-- subpage   user-demo <-- (1) only this is published.
  .     |-- nested  user-demo

  This leads to the following result:

  |-- site
  . |-- cr
  .   |-- [NODE DOES NOT EXIST ANYMORE]
  .     |-- nested  live + SHADOW NODE in user-demo   <-- !!BUG!!
  . |-- other
  .   |-- subpage
  .     |-- nested  user-demo

  The first "nested" node (marked with !!BUG!!) is NOT visible anymore in live, because the parent does not exist
  anymore. It's hard to detect this as user-demo, because user-demo sees the moved nested node.

  PROPOSED CHANGE: It must be FORBIDDEN to publish only the "subpage" but NOT its moved children, because that would
  directly lead to disconnected and unreachable children.

  ------------------------ Scenario 1A --------------------------------

  Now, let's assume user-demo forgets about this (thus not publishing), and a few weeks later needs to do
  a text change on *nested*.

  |-- site
  . |-- cr
  .   |-- subpage   live + SHADOW NODE in user-demo
  .     |-- nested  live + SHADOW NODE in user-demo <--- (2) ... and this is published as well; removing the subpage in live
  . |-- other
  .   |-- subpage   user-demo
  .     |-- nested  user-demo <-- (1) only this is published.

  This leads to the following result:

  |-- site
  . |-- cr
  .   |-- subpage   live + SHADOW NODE in user-demo
  . |-- other
  .   |-- subpage   user-demo !!BUG!! - This Node does NOT exist in LIVE
  .     |-- nested  live + user-demo

  The "nested" node (marked with !!BUG!!) is NOT visible in live, because the parent does not exist
  yet. It's hard to detect this as user-demo, because user-demo sees the moved parent.
