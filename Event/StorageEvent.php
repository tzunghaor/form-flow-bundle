<?php


namespace Tzunghaor\FormFlowBundle\Event;


use Symfony\Component\EventDispatcher\Event;

class StorageEvent extends Event
{
    const NAME_SAVE = 'save';

    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return object the flow's data
     */
    public function getData()
    {
        return $this->data;
    }
}