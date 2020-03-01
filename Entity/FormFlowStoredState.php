<?php
/**
 * Author: tzunghaor_at_primandras.hu
 * Created: 2017-10-30
 */

namespace Tzunghaor\FormFlowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * This entity holds the data needed to restore flow state across requests.
 *
 * @ORM\Entity
 * @ORM\Table(name="tzunghaor_form_flow_stored_state")
 *
 * You don't have to create the table if you don't use the doctrine storage backend, but this entity will be passed
 * to other backends too.
 */
class FormFlowStoredState
{
    /**
     * @ORM\Column(type="string")
     * @ORM\Id
     *
     * @var string
     */
    private $instanceId;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $flowName;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $state;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $data;

    /**
     * @ORM\Column(type="json_array")
     *
     * @var array
     */
    private $stepStates;

    /**
     * @return string
     */
    public function getInstanceId(): string
    {
        return $this->instanceId;
    }

    /**
     * @param string $instanceId
     *
     * @return FormFlowStoredState
     */
    public function setInstanceId(string $instanceId): FormFlowStoredState
    {
        $this->instanceId = $instanceId;

        return $this;
    }

    /**
     * @return string
     */
    public function getFlowName(): string
    {
        return $this->flowName;
    }

    /**
     * @param string $flowName
     *
     * @return FormFlowStoredState
     */
    public function setFlowName(string $flowName): FormFlowStoredState
    {
        $this->flowName = $flowName;

        return $this;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state
     * @return FormFlowStoredState
     */
    public function setState(string $state): FormFlowStoredState
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @param string $data
     *
     * @return FormFlowStoredState
     */
    public function setData(string $data): FormFlowStoredState
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getStepStates(): array
    {
        return $this->stepStates;
    }

    /**
     * @param array $stepStates
     *
     * @return FormFlowStoredState
     */
    public function setStepStates(array $stepStates): FormFlowStoredState
    {
        $this->stepStates = $stepStates;

        return $this;
    }
}