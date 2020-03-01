<?php
/**
 * Author: tzunghaor_at_primandras.hu
 * Created: 2017-10-30
 */

namespace Tzunghaor\FormFlowBundle\Storage;


use Doctrine\ORM\EntityManagerInterface;
use Tzunghaor\FormFlowBundle\Entity\FormFlowStoredState;
use Tzunghaor\FormFlowBundle\Exception\FlowInstanceNotFoundException;

class DoctrineStorage implements StorageInterface
{
    /** @var  EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Saves the form flow state to the storage backend
     *
     * @param FormFlowStoredState $state
     */
    public function save(FormFlowStoredState $state)
    {
        $this->entityManager->merge($state);
        $this->entityManager->flush();
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
        $storedState = $this->entityManager->find(FormFlowStoredState::class, $instanceId);

        if ($storedState === null) {
            throw new FlowInstanceNotFoundException('Form flow instance not found in doctrine storage: instanceId=' . $instanceId);
        }

        return $storedState;
    }
}