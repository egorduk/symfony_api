<?php

namespace Btc\FrontendApiBundle\Service\Listener;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class RestAccessDeniedListener implements EventSubscriberInterface
{
    private $isDebugMode;

    public function __construct($isDebugMode = 0)
    {
        $this->isDebugMode = $isDebugMode;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($this->isDebugMode) {
            return;
        }

        $exception = $event->getException();

        if (!$exception instanceof AccessDeniedException) {
            return;
        }

        $responseData = [
            'status' => $exception->getCode(),
            'error' => RestCodeError::ACCESS_DENIED,
        ];

        $event->setResponse(new JsonResponse($responseData, Response::HTTP_OK, ['X-Status-Code' => $responseData['status']]));
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', -1],
        ];
    }
}
