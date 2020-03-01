<?php


namespace Tzunghaor\FormFlowBundle\Tests\Storage;


use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Tzunghaor\FormFlowBundle\Entity\FormFlowStoredState;
use Tzunghaor\FormFlowBundle\Exception\FlowInstanceNotFoundException;
use Tzunghaor\FormFlowBundle\Storage\DoctrineStorage;

/**
 * Test for DoctrineStorage
 */
class DoctrineStorageTest extends TestCase
{
    public function testSave()
    {
        $flowState = new FormFlowStoredState();

        /** @var PHPUnit_Framework_MockObject_MockObject|EntityManagerInterface $mockEntityManager */
        $mockEntityManager = $this->createMock(EntityManagerInterface::class);
        $mockEntityManager->expects($this->once())->method('merge')->with($flowState);
        $mockEntityManager->expects($this->once())->method('flush');

        $storage = new DoctrineStorage($mockEntityManager);

        $storage->save($flowState);
    }

    /**
     * @throws FlowInstanceNotFoundException
     */
    public function testLoadCaseOk()
    {
        $flowState = new FormFlowStoredState();

        /** @var PHPUnit_Framework_MockObject_MockObject|EntityManagerInterface $mockEntityManager */
        $mockEntityManager = $this->createMock(EntityManagerInterface::class);
        $mockEntityManager->expects($this->once())->method('find')
            ->with(FormFlowStoredState::class, 'test-instance')->willReturn($flowState);

        $storage = new DoctrineStorage($mockEntityManager);

        $loadedState = $storage->load('test-instance');
        $this->assertEquals($flowState, $loadedState);
    }

    /**
     * @throws FlowInstanceNotFoundException
     */
    public function testLoadCaseErrorNotFound()
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|EntityManagerInterface $mockEntityManager */
        $mockEntityManager = $this->createMock(EntityManagerInterface::class);
        $mockEntityManager->expects($this->once())->method('find')
            ->with(FormFlowStoredState::class, 'test-instance')->willReturn(null);

        $storage = new DoctrineStorage($mockEntityManager);

        $this->expectException(FlowInstanceNotFoundException::class);
        $storage->load('test-instance');
    }
}