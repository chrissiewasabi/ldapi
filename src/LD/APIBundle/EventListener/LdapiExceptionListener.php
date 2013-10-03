<?php
namespace LD\APIBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * LdapiExceptionListener
 */
class LdapiExceptionListener
{
    /**
     * onKernelException
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
     *
     * @return Response
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $data = array();
        $data['code'] = $exception->getCode();
        $data['file'] = $exception->getFile();
        $data['line'] = $exception->getLine();
        $data['message'] = $exception->getMessage();
        $data['trace'] = $exception->getTraceAsString();

        $response = new Response();
        $response->setContent(json_encode($data));
        $response->headers->set('Content-type', 'application/json');

        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
        } else {
            $response->setStatusCode(500);
        }

        // Send the modified response object to the event
        $event->setResponse($response);
    }
}