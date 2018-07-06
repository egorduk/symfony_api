<?php

namespace Btc\FrontendApiBundle\Classes;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Bundle\FrameworkBundle\Validator\ConstraintValidatorFactory;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validation;

/**
 * Trait for exercising Symfony2 validators.
 * Works with Annotation style mapping of validators.
 */
trait SpecValidatorTrait
{
    /** @var array An array of validators to pass into ConstraintValidatorFactory */
    private $validatorsToInit;

    /** @var Validation Constructed instance of validation */
    private $validator;

    /**
     * @param \Symfony\Component\Validator\ConstraintViolationList $violations
     * @param string                                               $message    - Expected violation error message
     *
     * @throws \Exception - if message was not found
     */
    private function shouldHaveViolation(ConstraintViolationList $violations, $message)
    {
        $were = [];

        foreach ($violations as $violation) {
            $m = $were[] = $violation->getMessage();

            if ($m === $message) {
                return;
            }
        }

        throw new \Exception("The message '{$message}' was expected, but it was not in violation list:\n\t".implode("\n\t", $were));
    }

    /**
     * @param \Symfony\Component\Validator\ConstraintViolationList $violations
     * @param int                                                  $num
     * @param string                                               $message    - Expected violation error message
     *
     * @throws \Exception - if message was not found
     */
    private function shouldHaveNumViolation(ConstraintViolationList $violations, $num, $message)
    {
        $were = [];
        $n = 0;

        foreach ($violations as $violation) {
            $m = $were[] = $violation->getMessage();

            if ($m === $message) {
                ++$n;
            }
        }

        if ($n !== $num) {
            throw new \Exception("The message '{$message}' was expected $num times, but it was only $n times in violation list:\n\t".implode("\n\t", $were));
        }
    }

    private function shouldNotHaveViolation(ConstraintViolationList $violations, $message)
    {
        foreach ($violations as $violation) {
            $m = $violation->getMessage();

            if ($m === $message) {
                throw new \Exception("The message '{$message}' was not expected, but it was found in violation list");
            }
        }
    }

    /** loads annotation registry from autoload file */
    private function loadAnnotationRegistry()
    {
        $loader = require __DIR__.'/../../../../vendor/autoload.php';

        AnnotationRegistry::registerLoader([$loader, 'loadClass']);
    }

    /**
     * @return \Symfony\Component\Validator\ConstraintValidatorInterface
     */
    private function stubDummyValidator()
    {
        $prophet = new \Prophecy\Prophet();

        return $prophet
            ->prophesize(ConstraintValidatorInterface::class)
            ->reveal();
    }

    private function initValidator()
    {
        $this->loadAnnotationRegistry();

        $validatorFactory = new ConstraintValidatorFactory(
            new Container(), // Use validatorsToInit array and assign values as instances, to avoid calling container
            $this->validatorsToInit ?: []
        );

        $this->validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->setConstraintValidatorFactory($validatorFactory)
            ->getValidator();
    }
}
