// This fixture contains the following features:

#  - single line comments
#  - empty lines
#  - namespace declarations
#  - value assignments: object type and literal

  namespace: cms = F3_TYPO3_TypoScript 
test = cms:Text
 	test.value= "Hello world!" 

secondTest = cms:Text
secondTest.value = 23
