<?php
/**
 * Author: tzunghaor_at_primandras.hu
 * Created: 2017-07-09
 */

namespace Tzunghaor\FormFlowBundle\Definition;

/**
 * Holds configuration of a single form flow step
 */
class StepConfig
{
    /** @var string identifier of the step within the flow */
    private $name = '';

    /** @var string label of the step (translation key) */
    private $label = '';

    /** @var string name of form class */
    private $formClass = '';

    /** @var string name of view to render the step */
    private $view = '@TzunghaorFormFlow/Default/index.html.twig';

    /**
     * @var array parameters to be passed to view when rendering
     *
     * These variables can be overwritten by @see $viewVariablesCallable.
     */
    private $viewVariables = [];

    /**
     * @var null|callable method that generates view parameters
     *
     * public function viewParametersCallable(AbstractFormFlow $flow): array
     */
    private $viewVariablesCallable = null;

    /**
     * @var array options to be used when creating the form
     *
     * These options can be overwritten by @see $formOptionsCallable
     */
    private $formOptions = [];

    /**
     * @var null|callable method that generates form options
     *
     * public function formOptionsCallable(AbstractFormFlow $flow): array
     */
    private $formOptionsCallable = null;

    /**
     * @var null|callable a method that should return true when this step should be skipped.
     *
     * function skipConditionCallable(FlowData $flow): bool
     */
    private $skipConditionCallable = null;

    /**
     * @var null|callable A method that returns an array of custom reasons (displayable to user) why they cannot enter
     * this step. It should return an empty array if there is no special reason to not enter this step.
     * You can use this to block entering certain steps if conditions outside the scope of data validation are not met
     * (e.g. some steps are not accessible if the user is not logged in).
     *
     * function enterErrorsCallable(FlowData $flow): string[]
     */
    private $enterErrorsCallable = null;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return StepConfig
     */
    public function setName(string $name): StepConfig
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        if ('' === $this->label) {

            return $this->getName();
        }

        return $this->label;
    }

    /**
     * @param string $label
     * @return StepConfig
     */
    public function setLabel(string $label): StepConfig
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormClass(): string
    {
        return $this->formClass;
    }

    /**
     * @param string $formClass
     * @return StepConfig
     */
    public function setFormClass(string $formClass): StepConfig
    {
        $this->formClass = $formClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getView(): string
    {
        return $this->view;
    }

    /**
     * @param string $view
     * @return StepConfig
     */
    public function setView(string $view): StepConfig
    {
        $this->view = $view;

        return $this;
    }

    /**
     * @return array
     */
    public function getViewVariables(): array
    {
        return $this->viewVariables;
    }

    /**
     * @param array $viewVariables
     * @return StepConfig
     */
    public function setViewVariables(array $viewVariables): StepConfig
    {
        $this->viewVariables = $viewVariables;

        return $this;
    }

    /**
     * @return callable|null
     */
    public function getViewVariablesCallable()
    {
        return $this->viewVariablesCallable;
    }

    /**
     * @param callable|null $viewVariablesCallable
     * @return StepConfig
     */
    public function setViewVariablesCallable($viewVariablesCallable = null)
    {
        $this->viewVariablesCallable = $viewVariablesCallable;

        return $this;
    }

    /**
     * @return array
     */
    public function getFormOptions(): array
    {
        return $this->formOptions;
    }

    /**
     * @param array $formOptions
     * @return StepConfig
     */
    public function setFormOptions(array $formOptions): StepConfig
    {
        $this->formOptions = $formOptions;

        return $this;
    }

    /**
     * @return callable|null
     */
    public function getFormOptionsCallable()
    {
        return $this->formOptionsCallable;
    }

    /**
     * @param callable|null $formOptionsCallable
     * @return StepConfig
     */
    public function setFormOptionsCallable($formOptionsCallable = null)
    {
        $this->formOptionsCallable = $formOptionsCallable;

        return $this;
    }

    /**
     * @return callable|null
     */
    public function getSkipConditionCallable()
    {
        return $this->skipConditionCallable;
    }

    /**
     * @param callable|null $skipConditionCallable function skipConditionCallable(FlowData $flow): bool
     * @return StepConfig
     */
    public function setSkipConditionCallable($skipConditionCallable = null)
    {
        $this->skipConditionCallable = $skipConditionCallable;

        return $this;
    }

    /**
     * @return callable|null
     */
    public function getEnterErrorsCallable()
    {
        return $this->enterErrorsCallable;
    }

    /**
     * @param callable|null $enterErrorsCallable function enterErrorsCallable(FlowData $flow): string[]
     * @return StepConfig
     */
    public function setEnterErrorsCallable($enterErrorsCallable = null)
    {
        $this->enterErrorsCallable = $enterErrorsCallable;

        return $this;
    }
}
