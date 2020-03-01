<?php
/**
 * Author: tzunghaor_at_primandras.hu
 * Created: 2018-08-19
 */

namespace Tzunghaor\FormFlowBundle\Definition;

use Tzunghaor\FormFlowBundle\Definition\FormFlowConfig;
use Tzunghaor\FormFlowBundle\Definition\StepConfig;

/**
 * You need to provide your form-flow definition in a class that implements this interface
 */
interface FormFlowDefinitionInterface
{
    /**
     * This method should return the form flow configuration
     *
     * @return FormFlowConfig
     */
    function loadFlowConfig(): FormFlowConfig;

    /**
     * This method should return the form steps' configuration
     *
     * @return array|StepConfig[]
     */
    function loadStepConfigs(): array;
}