<?php
namespace Tzunghaor\FormFlowBundle\FormFlow;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Tzunghaor\FormFlowBundle\Definition\FormFlowConfig;
use Tzunghaor\FormFlowBundle\Exception\StepNotFoundException;

/**
 * This class can be used in views rendering the form flow
 */
class FormFlowView
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $instanceId;
    /**
     * @var FormView
     */
    private $formView;
    /**
     * @var Step
     */
    private $currentStep;
    /**
     * @var array
     */
    private $viewVariables;
    /**
     * @var array|Step[]
     */
    private $steps;
    /**
     * @var bool
     */
    private $canGoBack;
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;
    /**
     * @var FormFlowConfig
     */
    private $formFlowConfig;

    /**
     * @param FormFlow $formFlow
     * @param FormFlowConfig $formFlowConfig
     * @param FormFactoryInterface $formFactory
     * @param bool $canGoBack
     *
     * @throws StepNotFoundException
     */
    public function __construct(FormFlow $formFlow, FormFlowConfig $formFlowConfig, FormFactoryInterface $formFactory, bool $canGoBack)
    {
        $this->name = $formFlow->getName();
        $this->instanceId = $formFlow->getInstanceId();
        $this->formView = $formFlow->getCurrentForm()->createView();
        $this->currentStep = $formFlow->getCurrentStep();
        $this->steps = $formFlow->getSteps();
        $this->viewVariables = $this->createViewVariables($formFlowConfig, $this->currentStep, $formFlow->getData());
        $this->canGoBack = $canGoBack;
        $this->formFactory = $formFactory;
        $this->formFlowConfig = $formFlowConfig;
    }

    /**
     * Gets the view template name of the current step
     *
     * @return string
     */
    public function getView(): string
    {
        return $this->currentStep->getView();
    }

    /**
     * Gets the view template variables to be used in the current step's view
     *
     * @return array
     */
    public function getViewVariables(): array
    {
        return $this->viewVariables;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Step
     */
    public function getCurrentStep(): Step
    {
        return $this->currentStep;
    }

    public function getCurrentStepName(): string
    {
        return $this->currentStep->getName();
    }

    /**
     * @return array|Step[]
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * @return string
     */
    public function getInstanceId(): string
    {
        return $this->instanceId;
    }

    /**
     * @param string $stepName defaults to current step
     *
     * @return array
     */
    public function getRouteParameters(string $stepName = ''): array
    {
        if ($stepName === '') {
            $stepName = $this->getCurrentStepName();
        }

        return $this->formFlowConfig->getRouteParameters($this->getName(), $this->getInstanceId(), $stepName);
    }

    /**
     * Gets the form view of the "Next" button to be used in the template
     *
     * @param array|null $extraOptions additional form options
     *
     * @return FormView
     */
    public function getForwardSubmitButton(array $extraOptions = null): FormView
    {
        $submitName = $this->formFlowConfig->getSubmitName();

        $defaultOptions = [
            'label' => 'tzunghaor.form-flow.forward-submit',
            'attr' => ['name' => $submitName, 'value' => FormFlow::SUBMIT_ACTION_FORWARD],
        ];
        $options = is_array($extraOptions) ? array_merge($defaultOptions, $extraOptions) : $defaultOptions;

        return $this->formFactory->create(SubmitType::class, null, $options)->createView();
    }

    /**
     * Gets the form view of th "Back" button to be used in the template
     *
     * @param array|null $extraOptions additional form options
     *
     * @return FormView
     */
    public function getBackSubmitButton(array $extraOptions = null): FormView
    {
        $submitName = $this->formFlowConfig->getSubmitName();

        $defaultOptions = [
            'label' => 'tzunghaor.form-flow.back-submit',
            'attr' => ['name' => $submitName, 'value' => FormFlow::SUBMIT_ACTION_BACK, 'formnovalidate' => ''],
            'disabled' => !$this->canGoBack,
        ];
        $options = is_array($extraOptions) ? array_merge($defaultOptions, $extraOptions) : $defaultOptions;

        return $this->formFactory->create(SubmitType::class, null, $options)->createView();
    }

    /**
     * Creates view variables for the given step
     *
     * @param FormFlowConfig $flowConfig
     * @param Step $step
     * @param object $data form flow data
     *
     * @return array
     */
    private function createViewVariables(FormFlowConfig $flowConfig, Step $step, $data): array
    {
        $viewVariables = $step->getViewVariables($data);

        if ($flowConfig->getFlowViewVariable() !== '') {
            $viewVariables[$flowConfig->getFlowViewVariable()] = $this;
        }

        if ($flowConfig->getFormViewVariable() !== '') {
            $viewVariables[$flowConfig->getFormViewVariable()] = $this->formView;
        }

        return $viewVariables;
    }
}