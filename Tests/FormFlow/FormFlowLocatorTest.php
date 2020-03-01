<?php


namespace Tzunghaor\FormFlowBundle\Tests\FormFlow;


use PHPUnit\Framework\TestCase;
use Tzunghaor\FormFlowBundle\Exception\FlowNotFoundException;
use Tzunghaor\FormFlowBundle\FormFlow\FormFlow;
use Tzunghaor\FormFlowBundle\FormFlow\FormFlowLocator;

class FormFlowLocatorTest extends TestCase
{
    public function testGetFormFlowFound()
    {
        $flow = $this->createMock(FormFlow::class);
        $flow->method('getName')->willReturn('test-flow');

        $locator = new FormFlowLocator();
        $locator->addFormFlow($flow);

        $foundFlow = $locator->getFormFlow('test-flow');

        $this->assertEquals($flow, $foundFlow);
    }

    public function testGetFormFlowNotFound()
    {
        $flow = $this->createMock(FormFlow::class);
        $flow->method('getName')->willReturn('test-flow');

        $locator = new FormFlowLocator();
        $locator->addFormFlow($flow);

        $this->expectException(FlowNotFoundException::class);
        $locator->getFormFlow('other-flow');
    }
}