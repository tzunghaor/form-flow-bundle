<?php
/**
 * Author: tzunghaor_at_primandras.hu
 * Created: 2017-11-05
 */

namespace Tzunghaor\FormFlowBundle\EventListener;


use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Routing\RouterInterface;
use Tzunghaor\FormFlowBundle\Exception\AlreadyFinishedException;
use Tzunghaor\FormFlowBundle\Definition\FormFlowConfig;

/**
 * Upon AlreadyFinishedException redirects to the finished page if it is set.
 *
 * @see FormFlowConfig::setFinishedRoute()
 */
class AlreadyFinishedExceptionListener
{
    /** @var RouterInterface */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof AlreadyFinishedException) {
            $flow = $exception->getFlow();
            $redirectUrl = '';

            if (($finishedRoute = $flow->getFinishedRoute()) !== '') {
                $redirectUrl = $this->router->generate($finishedRoute, $flow->getRouteParameters());
            }

            if ($redirectUrl !== '') {
                $event->setResponse(new RedirectResponse($redirectUrl));
            }
        }
    }
}