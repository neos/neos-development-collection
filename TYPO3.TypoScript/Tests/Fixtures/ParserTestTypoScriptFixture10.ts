/**
 *  TypoScript Fixture 10
 *
 *  This fixture serves for testing the processors syntax
 *
 */
namespace: default = T3_TYPO3_TypoScript
 
object1 = Text
object1.value = "Hello world!"
object1.value << 1.wrap('<strong>', '</strong>')

object2 = Text
object2.value = "Bumerang"
object2.value << 1.wrap('ein ', ';')
object2.value << 3.wrap('War ', '')
object2.value << 2.wrap('einmal (vielleicht auch zweimal) ', '')

object3 = Text
object3.$one = "1"
object3.$sevenEightNine = "789"
object3 {
	value = "345"
	value << 1.wrap('2', "6")
	value << 2.wrap($one, "$sevenEightNine ...")
}

object4 = ContentArray
object4.10 = Text
object4.10.value = "cc"
object4.10.value << 1.wrap('su', 'ess')
