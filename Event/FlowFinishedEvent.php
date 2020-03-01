<?php
/**
 * Author: tzunghaor_at_primandras.hu
 * Created: 2017-07-01
 */

namespace Tzunghaor\FormFlowBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Dispatched when the last step is successfully submitted, validated and processed
 */
class FlowFinishedEvent extends Event
{
    const NAME = 'finished';
    /**
     * @var string
     */
    private $instanceId;
    /**
     * @var object the flow's data
     */
    private $data;

    public function __construct(string $instanceId, $data)
    {
        $this->instanceId = $instanceId;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getInstanceId(): string
    {
        return $this->instanceId;
    }

    /**
     * @return object
     */
    public function getData()
    {
        return $this->data;
    }
}