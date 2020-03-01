<?php
/**
 * Author: tzunghaor_at_primandras.hu
 * Created: 2017-11-05
 */

namespace Tzunghaor\FormFlowBundle\Tests\EventListener;


use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Tzunghaor\FormFlowBundle\EventListener\AlreadyFinishedExceptionListener;
use Tzunghaor\FormFlowBundle\Exception\AlreadyFinishedException;
use Tzunghaor\FormFlowBundle\FormFlow\FormFlow;

class AlreadyFinishedExceptionListenerTest extends TestCase
{
    /**
     * A lot of mocking to test a simple method.
     */
    public function testOnKernelException()
    {
        /** @var KernelInterface|\PHPUnit_Framework_MockObject_MockObject $kernel */
        $kernel = $this->getMockBuilder(KernelInterface::class)->getMock();
        /** @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject $router */
        $router = $this->getMockBuilder(RouterInterface::class)->getMock();

        $listener = new AlreadyFinishedExceptionListener($router);

        /** @var FormFlow|\PHPUnit_Framework_MockObject_MockObject $flow */
        $flow = $this->getMockBuilder(FormFlow::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFinishedRoute', 'getRouteParameters'])
            ->getMockForAbstractClass()
        ;

        $flow->method('getFinishedRoute')->willReturn('test-route');
        $flow->method('getRouteParameters')->willReturn(['test-param']);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('test-route', ['test-param'])
            ->willReturn('test-url')
        ;

        $exception = new AlreadyFinishedException($flow, "test message");
        $event = new GetResponseForExceptionEvent(
            $kernel, Request::create('/'), HttpKernelInterface::MASTER_REQUEST, $exception
        );

        $listener->onKernelException($event);

        /** @var RedirectResponse $response */
        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('test-url', $response->getTargetUrl());
    }
}