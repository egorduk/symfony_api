<?php

namespace Btc\FrontendApiBundle\Service\Listener;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Handler for RestException and HttpException.
 */
class RestExceptionListener implements EventSubscriberInterface
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

        if (!$exception instanceof RestException && !$exception instanceof HttpException) {
            return;
        } elseif ($exception instanceof AccessDeniedHttpException) {
            $responseData = [
                'status' => Response::HTTP_FORBIDDEN,
                'error' => RestCodeError::ACCESS_DENIED,
            ];

            $event->setResponse(new JsonResponse($responseData, Response::HTTP_OK, ['X-Status-Code' => $responseData['status']]));

            return;
        }

        $responseData = [
            'status' => $exception->getCode() === 0 ? Response::HTTP_BAD_REQUEST : $exception->getCode(),
            'error' => $exception->getCode() === 0 ? RestCodeError::INCORRECT_DATA : $exception->getMessage(),
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
