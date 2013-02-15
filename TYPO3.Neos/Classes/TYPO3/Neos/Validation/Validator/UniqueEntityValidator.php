<?php
namespace TYPO3\Neos\Validation\Validator;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow,
	TYPO3\Flow\Reflection\ObjectAccess,
	TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException;

/**
 * Validator for uniqueness of entities.
 *
 * @api
 * @Flow\Scope("singleton")
 * @TODO Replace this one with the one in Flow once it's merged
 */
class UniqueEntityValidator extends \TYPO3\Flow\Validation\Validator\AbstractValidator {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Checks if the given property ($object) is a unique entity depending on it's identity properties.
	 *
	 * @param object $object The object that should be validated
	 * @return void
	 */
	protected function isValid($object) {
		$classSchema = $this->reflectionService->getClassSchema($object);
		if ($classSchema->getModelType() !== \TYPO3\Flow\Reflection\ClassSchema::MODELTYPE_ENTITY) {
			throw new InvalidValidationOptionsException('The object supplied for the UniqueEntityValidator must be an entity.', 1358454270);
		}

		$identityProperties = $classSchema->getIdentityProperties();
		if (count($identityProperties) === 0) {
			throw new InvalidValidationOptionsException('The object supplied for the UniqueEntityValidator must have at least one identity property.', 1358459831);
		}

		$query = $this->persistenceManager->createQueryForType($classSchema->getClassName());

		$constraints = array($query->logicalNot($query->equals('Persistence_Object_Identifier', $this->persistenceManager->getIdentifierByObject($object))));
		foreach ($identityProperties as $propertyName => $propertyType) {
			array_push($constraints, $query->equals($propertyName, ObjectAccess::getProperty($object, $propertyName)));
		}

		if ($query->matching($query->logicalAnd($constraints))->count() > 0) {
			$this->addError('Another entity with the same unique identifiers already exists', 1355785874);
		}
	}

}
?>