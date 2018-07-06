<?php

namespace Btc\FrontendApiBundle\Service\Listener;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;


class JsonRequestTransformerListener
{

    const FORMAT_COEFF = 10;

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $FORMAT_SIZE = 1024 * 1024;

        if (!$this->isJsonRequest($request)) {
            return;
        }

        $content = $request->getContent();

        if (empty($content)) {
            return;
        }

        $fileSize = strlen($content);

        if ($fileSize > (intval(ini_get('upload_max_filesize')) * $FORMAT_SIZE) ||
            $fileSize * self::FORMAT_COEFF > (intval(ini_get('memory_limit')) * $FORMAT_SIZE)
        ) {
            $event->setResponse(new JsonResponse([
                'status' => Response::HTTP_BAD_REQUEST,
                'error' => RestCodeError::NOT_UPLOADED_FILE,
            ], Response::HTTP_BAD_REQUEST, ['X-Status-Code' => Response::HTTP_BAD_REQUEST]));
        }

        if (!$this->transformJsonBody($request)) {
            $event->setResponse(new JsonResponse([
                'status' => Response::HTTP_BAD_REQUEST,
                'error' => RestCodeError::INCORRECT_DATA,
            ], Response::HTTP_BAD_REQUEST, ['X-Status-Code' => Response::HTTP_BAD_REQUEST]));
        }
    }

    private function isJsonRequest(Request $request)
    {
        return 'json' === $request->getContentType();
    }

    private function transformJsonBody(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            return false;
        }

        if (null === $data) {
            return true;
        }

        $request->request->replace($data);

        return true;
    }
}
