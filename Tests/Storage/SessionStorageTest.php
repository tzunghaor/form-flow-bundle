<?php


namespace Tzunghaor\FormFlowBundle\Tests\Storage;


use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Tzunghaor\FormFlowBundle\Entity\FormFlowStoredState;
use Tzunghaor\FormFlowBundle\Exception\FlowInstanceNotFoundException;
use Tzunghaor\FormFlowBundle\Storage\SessionStorage;

/**
 * Test SessionStorage
 */
class SessionStorageTest extends TestCase
{
    public function testSave()
    {
        $flowState = new FormFlowStoredState();
        $flowState->setInstanceId('test-instance');

        /** @var PHPUnit_Framework_MockObject_MockObject|SessionInterface $mockSession */
        $mockSession = $this->createMock(SessionInterface::class);
        $mockSession->expects($this->once())->method('set')->with('testpref_test-instance', $flowState);

        $storage = new SessionStorage($mockSession, 'testpref_');

        $storage->save($flowState);
    }

    /**
     * @throws FlowInstanceNotFoundException
     */
    public function testLoadCaseOk()
    {
        $flowState = new FormFlowStoredState();

        /** @var PHPUnit_Framework_MockObject_MockObject|SessionInterface $mockSession */
        $mockSession = $this->createMock(SessionInterface::class);
        $mockSession->method('has')->willReturn(true);
        $mockSession->expects($this->once())->method('get')->with('testpref_test-instance')->willReturn($flowState);

        $storage = new SessionStorage($mockSession, 'testpref_');

        $loadedState = $storage->load('test-instance');
        $this->assertEquals($flowState, $loadedState);
    }

    /**
     * @throws FlowInstanceNotFoundException
     */
    public function testLoadCaseErrorNotFound()
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|SessionInterface $mockSession */
        $mockSession = $this->createMock(SessionInterface::class);
        $mockSession->method('has')->willReturn(false);

        $storage = new SessionStorage($mockSession, 'testpref_');

        $this->expectException(FlowInstanceNotFoundException::class);
        $storage->load('test-instance');
    }
}