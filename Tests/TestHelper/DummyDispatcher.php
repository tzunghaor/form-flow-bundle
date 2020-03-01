<?php


namespace Tzunghaor\FormFlowBundle\Tests\TestHelper;


use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EventDispatcher for tests - simply stores the dispatched events
 */
class DummyDispatcher implements EventDispatcherInterface
{
    private $dispatchedEvents = [];

    public function dispatch($eventName, Event $event = null)
    {
        $this->dispatchedEvents[$eventName][] = $event;
    }

    /**
     * @param string $eventName
     *
     * @return array
     */
    public function getDispatchedEvents($eventName): array
    {
        if (array_key_exists($eventName, $this->dispatchedEvents)) {
            return $this->dispatchedEvents[$eventName];
        }

        return [];
    }

    public function addListener($eventName, $listener, $priority = 0)
    {
        // DummyDispatcher does not care
    }

    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        // DummyDispatcher does not care
    }

    public function removeListener($eventName, $listener)
    {
        // DummyDispatcher does not care
    }

    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        // DummyDispatcher does not care
    }

    public function getListeners($eventName = null)
    {
        // DummyDispatcher does not care
    }

    public function getListenerPriority($eventName, $listener)
    {
        // DummyDispatcher does not care
    }

    public function hasListeners($eventName = null)
    {
        // DummyDispatcher does not care
    }
}