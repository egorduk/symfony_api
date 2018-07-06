<?php

namespace Btc\FrontendApiBundle\Service\Listener;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Doctrine\DBAL\DBALException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Btc\FrontendApiBundle\Exception\Rest\UserBlockedException;
/**
 * Handler for DBAL exceptions.
 */
class DBALExceptionListener implements EventSubscriberInterface
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

        if (!$exception instanceof DBALException && !$exception instanceof \InvalidArgumentException) {
            return;
        }

        if ($exception instanceof UserBlockedException) {
            $responseData = [
                'status' => Response::HTTP_BAD_REQUEST,
                'error' => RestCodeError::USER_BLOCKED,
            ];
        } else {
            $responseData = [
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'error' => RestCodeError::UNKNOWN_ERROR,
            ];
        }


        $event->setResponse(new JsonResponse($responseData, Response::HTTP_OK, ['X-Status-Code' => $responseData['status']]));
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', -1],
        ];
    }
}
