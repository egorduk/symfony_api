<?php namespace Btc;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Bundle\FrameworkBundle\Validator\ConstraintValidatorFactory;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Validator\Validation;

/**
 * Trait for exercising Symfony2 validators.
 *
 * Works with Annotation style mapping of validators
 *
 * @package Btc\CoreBundle\SpecHelpers
 */
trait SpecValidationTrait
{
    /** @var array An array of validators to pass into ConstraintValidatorFactory */
    private $validatorsToInit;

    /** @var Validation Constructed instance of validation */
    private $validator;

    /**
     * @param string $message Expected error message
     * @param \Symfony\Component\Validator\ConstraintViolationList $violations
     * @return true on success
     * @throws \Exception if message was not found
     */
    private function expectMessageInViolations($message, $violations)
    {
        foreach ($violations as $violation) {
            if ($violation->getMessage() == $message) {
                return true;
            }
        }
        throw new \Exception("Could not find message: '$message' in violations");
    }

    /** loads annotation registry from autoload file */
    private function loadAnnotationRegistry()
    {
        $loader = require __DIR__ . '/../../vendor/autoload.php';
        AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
    }

    /**
     * @return \Symfony\Component\Validator\ConstraintValidatorInterface
     */
    private function stubDummyValidator()
    {
        $prophet = new \Prophecy\Prophet();

        return $prophet->prophesize('Symfony\Component\Validator\ConstraintValidatorInterface')
            ->reveal();
    }

    private function initValidator()
    {
        $this->loadAnnotationRegistry();

        $validatorFactory = new ConstraintValidatorFactory(
            new Container(), // Use validatorsToInit array and assign values as instances, to avoid calling container
            $this->validatorsToInit ? : []
        );

        $this->validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->setConstraintValidatorFactory($validatorFactory)
            ->getValidator();
    }
} 
