<?php
namespace Tzunghaor\FormFlowBundle\Tests\TestHelper;


use Tzunghaor\FormFlowBundle\Entity\FormFlowStoredState;
use Tzunghaor\FormFlowBundle\Exception\FlowInstanceNotFoundException;
use Tzunghaor\FormFlowBundle\Storage\StorageInterface;

/**
 * FormFlow Storage for tests
 */
class DummyStorage implements StorageInterface
{
    /**
     * @var array|FormFlowStoredState[]
     */
    private $storage = [];

    /**
     * Saves the form flow state to the storage backend
     *
     * @param FormFlowStoredState $state
     */
    public function save(FormFlowStoredState $state)
    {
        $this->storage[$state->getInstanceId()] = $state;
    }

    /**
     * Fills the form flow from storage backend
     *
     * @param string $instanceId
     *
     * @return FormFlowStoredState
     *
     * @throws FlowInstanceNotFoundException if the given instance is not found in storage
     */
    public function load(string $instanceId): FormFlowStoredState
    {
        if (array_key_exists($instanceId, $this->storage)) {
            return $this->storage[$instanceId];
        }

        throw new FlowInstanceNotFoundException();
    }
}