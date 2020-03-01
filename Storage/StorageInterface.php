<?php
/**
 * Author: tzunghaor_at_primandras.hu
 * Created: 2017-04-14
 */
namespace Tzunghaor\FormFlowBundle\Storage;


use Tzunghaor\FormFlowBundle\Entity\FormFlowStoredState;
use Tzunghaor\FormFlowBundle\Exception\FlowInstanceNotFoundException;

interface StorageInterface
{
    /**
     * Saves the form flow state to the storage backend
     *
     * @param FormFlowStoredState $state
     */
    public function save(FormFlowStoredState $state);

    /**
     * Fills the form flow from storage backend
     *
     * @param string $instanceId
     *
     * @return FormFlowStoredState
     *
     * @throws FlowInstanceNotFoundException if the given instance is not found in storage
     */
    public function load(string $instanceId): FormFlowStoredState;
}