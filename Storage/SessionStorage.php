<?php
/**
 * Author: tzunghaor_at_primandras.hu
 * Created: 2017-04-14
 */

namespace Tzunghaor\FormFlowBundle\Storage;


use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Tzunghaor\FormFlowBundle\Entity\FormFlowStoredState;
use Tzunghaor\FormFlowBundle\Exception\FlowInstanceNotFoundException;

class SessionStorage implements StorageInterface
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var string SessionStorage will prefix all session key with this
     */
    private $sessionKeyPrefix;

    public function __construct(SessionInterface $session, $sessionKeyPrefix = 'TzunghaorFormFlow_')
    {
        $this->session = $session;
        $this->sessionKeyPrefix = $sessionKeyPrefix;
    }

    /**
     * Saves the form flow to the storage backend
     *
     * @param FormFlowStoredState $state
     */
    public function save(FormFlowStoredState $state)
    {
        $this->session->set($this->sessionKeyPrefix . $state->getInstanceId(), $state);
    }

    /**
     * Loads the form flow with the given instance id from storage backend
     *
     * @param string $instanceId
     *
     * @return FormFlowStoredState
     *
     * @throws FlowInstanceNotFoundException
     */
    public function load(string $instanceId): FormFlowStoredState
    {
        $key = $this->sessionKeyPrefix . $instanceId;

        if (!$this->session->has($key)) {
            throw new FlowInstanceNotFoundException('Form flow instance not found in session storage: instanceId=' . $instanceId);
        }

        return $this->session->get($key);
    }
}