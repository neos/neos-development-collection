/**
 *  TypoScript Fixture 06
 *
 *  This fixture serves for testing variables
 *
 */
namespace: default = T3_TYPO3_TypoScript
 
object1 = Text
object1.$message = "Hello world"
object1.value = $message

object2 = Text
object2 {
	$message = "Hello world"
	value = $message
}

object3 = Text
object3.$a = 'I didn\'t have'
object3.$b = 'a coffee yet!'
object3.value = "$a $b"

object4 = Text
object4.$firstName = "Kasper"
object4.$lastNamePart1 = "Skår"
object4.$lastNamePart2 = "høj"
object4.value = "Hello, $firstName $lastNamePart1$lastNamePart2!"

/*

object5 = Text
object5.$open = '<strong>'
object5.$close = '</strong>'
object5.value = "That's strong!"
object5.processors.1.wrap($open, $close)

*/