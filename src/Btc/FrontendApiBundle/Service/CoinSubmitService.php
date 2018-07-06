<?php

namespace Btc\FrontendApiBundle\Service;

use Btc\FrontendApiBundle\Entity\CoinSubmit;
use Btc\FrontendApiBundle\Events\CoinSubmissionEvent;
use Btc\FrontendApiBundle\Exception\Rest\InvalidFormException;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormFactoryInterface;
use Btc\FrontendApiBundle\Form\CoinSubmitType;
use Btc\FrontendApiBundle\Exception\Rest\NotValidDataException;
use Btc\FrontendApiBundle\Repository\CoinSubmissionRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CoinSubmitService extends RestService
{

    private $em;
    private $entityClass;
    private $formFactory;
    private $repository;
    private $ed;

    public function __construct(EntityManager $em,
                                $entityClass,
                                FormFactoryInterface $formFactory,
                                CoinSubmissionRepository $repository,
                                EventDispatcherInterface $ed
    )
    {
        $this->em = $em;
        $this->entityClass = $entityClass;
        $this->formFactory = $formFactory;
        $this->repository = $repository;
        $this->ed = $ed;
        parent::__construct($em, $entityClass);

    }

    public function processForm(Request $request)
    {
        $form = $this->formFactory->create(new CoinSubmitType(), $this->createEntity(), [
            'csrf_protection' => false
        ]);

        $parameters = $request->request->all();
        unset($parameters['_format']);  // TODO: instead of allow_extra_fields >= 2.6
        if (empty($parameters['isListingToken'])) $parameters['isListingToken'] = 'false';
        $form->submit($parameters);

        if ($form->isValid()) {
            $data = $form->getData();

            if (!$data instanceof CoinSubmit) {
                return null;
            }
            $data = $this->repository->save($data);

            $this->ed->dispatch(
                CoinSubmissionEvent::EVENT,
                new CoinSubmissionEvent($data)
            );

            return $data;
        }

        throw new NotValidDataException();
    }
}
