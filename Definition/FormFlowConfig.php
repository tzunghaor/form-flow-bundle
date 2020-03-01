<?php
/**
 * Author: tzunghaor_at_primandras.hu
 * Created: 2017-07-01
 */

namespace Tzunghaor\FormFlowBundle\Definition;

/**
 * Holds configuration values of the whole flow (not individual steps).
 */
class FormFlowConfig
{
    /** @var string name of the class that is used to store the form flow data */
    private $dataClass = '';

    /** @var string route name used to generate url to redirect when the form is finished */
    private $finishedRoute = '';

    /** @var string name of post/get/route parameter that contains the form flow's name */
    private $flowNameParam = 'flowName';

    /** @var string name of post/get/route parameter that contains the form flow instance id */
    private $instanceIdParam = 'instanceId';

    /** @var string name of post/get/route parameter that contains the form flow step */
    private $stepParam = 'step';

    /** @var string name of submit buttons */
    private $submitName = 'submit';

    /** @var bool if true, the current form name is used as validation group when validating current form */
    private $autoValidationGroups = false;

    /** @var string name of the view variable of the current flow object  */
    private $flowViewVariable = 'flow';

    /** @var string name of the view variable of the current form view object  */
    private $formViewVariable = 'form';

    /**
     * @return string
     */
    public function getDataClass(): string
    {
        return $this->dataClass;
    }

    /**
     * @param string $dataClass
     * @return FormFlowConfig
     */
    public function setDataClass(string $dataClass): FormFlowConfig
    {
        $this->dataClass = $dataClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getFinishedRoute(): string
    {
        return $this->finishedRoute;
    }

    /**
     * @param string $finishedRoute
     * @return FormFlowConfig
     */
    public function setFinishedRoute(string $finishedRoute): FormFlowConfig
    {
        $this->finishedRoute = $finishedRoute;

        return $this;
    }

    /**
     * @return string
     */
    public function getFlowNameParam(): string
    {
        return $this->flowNameParam;
    }

    /**
     * @param string $flowNameParam
     * @return FormFlowConfig
     */
    public function setFlowNameParam(string $flowNameParam): FormFlowConfig
    {
        $this->flowNameParam = $flowNameParam;

        return $this;
    }

    /**
     * @return string
     */
    public function getInstanceIdParam(): string
    {
        return $this->instanceIdParam;
    }

    /**
     * @param string $instanceIdParam
     * @return FormFlowConfig
     */
    public function setInstanceIdParam(string $instanceIdParam): FormFlowConfig
    {
        $this->instanceIdParam = $instanceIdParam;

        return $this;
    }

    /**
     * @return string
     */
    public function getStepParam(): string
    {
        return $this->stepParam;
    }

    /**
     * @param string $stepParam
     * @return FormFlowConfig
     */
    public function setStepParam(string $stepParam): FormFlowConfig
    {
        $this->stepParam = $stepParam;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubmitName(): string
    {
        return $this->submitName;
    }

    /**
     * @param string $submitName
     *
     * @return FormFlowConfig
     */
    public function setSubmitName(string $submitName): FormFlowConfig
    {
        $this->submitName = $submitName;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAutoValidationGroups(): bool
    {
        return $this->autoValidationGroups;
    }

    /**
     * @param bool $autoValidationGroups
     * @return FormFlowConfig
     */
    public function setAutoValidationGroups(bool $autoValidationGroups): FormFlowConfig
    {
        $this->autoValidationGroups = $autoValidationGroups;

        return $this;
    }

    /**
     * @return string
     */
    public function getFlowViewVariable(): string
    {
        return $this->flowViewVariable;
    }

    /**
     * @param string $flowViewVariable
     * @return FormFlowConfig
     */
    public function setFlowViewVariable(string $flowViewVariable): FormFlowConfig
    {
        $this->flowViewVariable = $flowViewVariable;
        return $this;
    }

    /**
     * @return string
     */
    public function getFormViewVariable(): string
    {
        return $this->formViewVariable;
    }

    /**
     * @param string $formViewVariable
     * @return FormFlowConfig
     */
    public function setFormViewVariable(string $formViewVariable): FormFlowConfig
    {
        $this->formViewVariable = $formViewVariable;
        return $this;
    }

    /**
     * @param string $flowName
     * @param string $instanceId
     * @param string $stepName
     *
     * @return array array of route parameters
     */
    public function getRouteParameters(string $flowName, string $instanceId, string $stepName): array
    {
        $routeParameters = [];

        if ($this->getFlowNameParam() !== '') {
            $routeParameters[$this->getFlowNameParam()] = $flowName;
        }

        if ($this->getInstanceIdParam() !== '') {
            $routeParameters[$this->getInstanceIdParam()] = $instanceId;
        }

        if ($this->getStepParam() !== '' && $stepName !== '') {
            $routeParameters[$this->getStepParam()] = $stepName;
        }

        return $routeParameters;
    }
}