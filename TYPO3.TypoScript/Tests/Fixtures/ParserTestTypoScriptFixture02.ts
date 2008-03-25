//
// TypoScript Fixture 02
//
// This fixture is used to check the following features:
//
//  - using the default namespace
//  - block comments
//  - quote escaping in literals

namespace: default = F3_TYPO3_TypoScript

myObject = Text
myObject.value = 'Sorry, we\'re closed.'

/* A block comment starts here.
myObject.value = "This should not be parsed."
*/anotherObject = Text
anotherObject.value = "And I said: \"Hooray\""

/**
 * A traditional block comment
 *
 */

 /* This block comment has a leading and a trailing whitespace
    before and after the comment sign.
 */ 
 
 kaspersObject = Text
 kaspersObject.value = "The end of this line is a backslash\\"
 