<?php
/**
 * Author: tzunghaor_at_primandras.hu
 * Created: 2018-08-30
 */

namespace Tzunghaor\FormFlowBundle\FormFlow;

use Tzunghaor\FormFlowBundle\Exception\FlowNotFoundException;

/**
 * Finds a form flow by its name
 */
class FormFlowLocator
{
    /** @var array|FormFlow[] */
    private $formFlows = [];

    /**
     * @param FormFlow $flow
     */
    public function addFormFlow(FormFlow $flow)
    {
        $this->formFlows[$flow->getName()] = $flow;
    }

    /**
     * @param string $name
     *
     * @return FormFlow
     *
     * @throws FlowNotFoundException
     */
    public function getFormFlow(string $name): FormFlow
    {
        if (!array_key_exists($name, $this->formFlows)) {
            throw new FlowNotFoundException('Form flow does not exist: ' . $name);
        }

        return $this->formFlows[$name];
    }
}