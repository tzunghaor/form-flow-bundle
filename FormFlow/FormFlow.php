<?php
/**
 * Author: tzunghaor_at_primandras.hu
 * Created: 2017-04-14
 */

namespace Tzunghaor\FormFlowBundle\FormFlow;


use DomainException;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tzunghaor\FormFlowBundle\Definition\FormFlowConfig;
use Tzunghaor\FormFlowBundle\Definition\FormFlowDefinitionInterface;
use Tzunghaor\FormFlowBundle\Definition\StepConfig;
use Tzunghaor\FormFlowBundle\Entity\FormFlowStoredState;
use Tzunghaor\FormFlowBundle\Event\FlowFinishedEvent;
use Tzunghaor\FormFlowBundle\Event\StorageEvent;
use Tzunghaor\FormFlowBundle\Exception\AlreadyFinishedException;
use Tzunghaor\FormFlowBundle\Exception\FlowInstanceNotFoundException;
use Tzunghaor\FormFlowBundle\Exception\StepNotFoundException;
use Tzunghaor\FormFlowBundle\Storage\StorageInterface;

class FormFlow
{
    /** The flow is active */
    const STATE_ACTIVE = 'active';
    /** The flow is finished, modifying data is not allowed */
    const STATE_FINISHED = 'finished';

    /** Submit action forward: the usual thing */
    const SUBMIT_ACTION_FORWARD = 'forward';
    /** Submit action backward: the submitted data is only saved, but not validated */
    const SUBMIT_ACTION_BACK = 'back';

    /** @var string name used to distinguish this form flow class from others */
    private $name;

    /** @var FormFlowDefinitionInterface */
    private $definition;
    /** @var StepConfig[] */
    private $stepConfigs;

    /** @var string one of self::STATE_* constants */
    private $state = self::STATE_ACTIVE;

    /** @var FormFlowConfig */
    private $flowConfig;
    /** @var array|Step[] */
    private $steps;

    /** @var object data submitted by the user */
    private $flowData;
    /** @var string id that identifies this form flow session */
    private $instanceId = '';
    /** @var FormFlowNavigator */
    private $navigator;

    /** @var FormInterface form of the current step */
    private $currentForm;

    /** @var string the requested action, one of self::SUBMIT_ACTION_* constants */
    private $submitAction = self::SUBMIT_ACTION_FORWARD;

    /** @var FormFactoryInterface */
    private $formFactory;
    /** @var EventDispatcherInterface */
    private $eventDispatcher;
    /** @var StorageInterface the flow data/state is stored here between requests */
    private $storage;
    /** @var ValidatorInterface */
    private $validator;
    /** @var SerializerInterface */
    private $serializer;

    public function __construct(
        string $name,
        FormFlowDefinitionInterface $definition,
        StorageInterface $storage,
        FormFactoryInterface $formFactory,
        EventDispatcherInterface $eventDispatcher,
        ValidatorInterface $validator,
        SerializerInterface $serializer
    )
    {
        $this->name = $name;
        $this->definition = $definition;
        $this->storage = $storage;
        $this->formFactory = $formFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->validator = $validator;
        $this->serializer = $serializer;

        $this->navigator = new FormFlowNavigator(0);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return FormFlowDefinitionInterface
     */
    protected function getDefinition(): FormFlowDefinitionInterface
    {
        return $this->definition;
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
     *
     * @return FormFlow
     */
    protected function setState(string $state): FormFlow
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Returns the object holding the flow data
     *
     * @return object
     */
    public function getData()
    {
        if ($this->flowData === null) {
            $flowDataClass = $this->getFlowConfig()->getDataClass();
            $this->flowData = new $flowDataClass();
        }

        return $this->flowData;
    }

    /**
     * @return object copy of flow data containing only data from non-skipped valid steps (you have to set up Groups
     *                in your data class to use this cleaning)
     */
    public function getCleanData()
    {
        $validGroups = [];
        foreach ($this->getSteps() as $step) {
            if ($step->isValid() && !$step->isSkipped()) {
                $validGroups[] = $step->getName();
            }
        }
        $serialized = $this->serializer->serialize($this->getData(), 'json', ['groups' => $validGroups]);

        return $this->serializer->deserialize($serialized, $this->getFlowConfig()->getDataClass(), 'json');
    }

    /**
     * Fills the flow according to the request
     * * Determine instance id
     * * Determine current step
     * * Load data from storage and merge newly submitted data into it
     *
     * @param Request $request
     *
     * @throws AlreadyFinishedException
     * @throws StepNotFoundException
     */
    public function handleRequest(Request $request)
    {
        $this->initByRequest($request);

        $currentForm = $this->getCurrentForm();
        $currentForm->handleRequest($request);
        if ($currentForm->isSubmitted()) {
            $submitName = $this->getFlowConfig()->getSubmitName();

            $this->submitAction = $request->get($submitName, self::SUBMIT_ACTION_FORWARD);

            // Non-forward submit is not validated, so it's state is not set to valid
            $valid = $this->submitAction === self::SUBMIT_ACTION_FORWARD && $currentForm->isValid();
            $this->setStepsValidity($valid);
            $this->updateStepStates();
        }
    }

    /**
     * Loads or initializes flow state based on request
     *
     * @param Request $request
     *
     * @throws AlreadyFinishedException
     */
    protected function initByRequest(Request $request)
    {
        $this->instanceId = $request->get($this->getFlowConfig()->getInstanceIdParam(), '');

        try {
            $requestedStepIndex = $this->getStepIndex($request->get($this->getFlowConfig()->getStepParam(), ''));
        } catch (DomainException $e) {
            // it is not really a problem if step in request does not exist, we start the flow from the beginning
            $requestedStepIndex = 0;
        }

        $this->preFillFromStorage($this->getInstanceId());
        if ($this->getState() === self::STATE_FINISHED) {
            throw new AlreadyFinishedException($this, "Cannot handle request for a finished form flow");
        }

        $this->navigator->setCurrentStepIndex(
            $requestedStepIndex, $this->getSteps(), FormFlowNavigator::DIRECTION_FORWARD
        );
    }

    /**
     * Used on form submit - sets current steps validity, and invalidates every step after it
     *
     * @param bool $isCurrentStepValid
     */
    protected function setStepsValidity(bool $isCurrentStepValid)
    {
        $currentIndex = $this->navigator->getCurrentStepIndex();

        $newStates = [];
        $index = 0;
        foreach ($this->getSteps() as $step) {
            if ($index < $currentIndex) {
                $newState = $step->getState();
            } elseif ($index === $currentIndex) {
                $newState = $step->getValidityChangedState($isCurrentStepValid);
            } else {
                $newState = $step->getValidityChangedState(false);
            }
            $newStates[$step->getName()] = $newState;
            $index++;
        }

        $this->createSteps($newStates);
    }

    /**
     * Returns true if it is OK to save the data and proceed to the requested step
     *
     * @return bool
     *
     * @throws StepNotFoundException
     */
    public function canProceedAndSave(): bool
    {
        $currentForm = $this->getCurrentForm();

        if (!$currentForm->isSubmitted()) {
            return false;
        }

        if ($this->getSubmitAction() === self::SUBMIT_ACTION_FORWARD) {

            if (!$currentForm->isValid()) {
                return false;
            }

            if (!$this->navigator->hasStepsLeft($this->getSteps()) && !$this->checkDataValidity()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns true if the whole flow data is valid, considering only validations defined for the data.
     * Does not consider validations in individual step forms.
     *
     * @return bool
     *
     * @throws StepNotFoundException
     */
    public function checkDataValidity(): bool
    {
        $violationList = $this->validator->validate($this->getData());

        if ($violationList->count() > 0) {
            // Map validation errors to the current form
            $violationMapper = new ViolationMapper();
            $form = $this->getCurrentForm();

            foreach ($violationList as $violation) {
                $violationMapper->mapViolation($violation, $form);
            }

            return false;
        }

        return true;
    }

    /**
     * Saves the flow state/date to the storage
     *
     * @param string $stepName
     */
    protected function save(string $stepName)
    {
        $this->dispatchEvent(new StorageEvent($this->getData()), StorageEvent::NAME_SAVE, $stepName);

        $this->storage->save($this->getStoredState());
    }

    /**
     * Tries to proceed to the step according to the request that was set with handleRequest()
     *
     * @return int one of FormFlowNavigator::NAV_* constants
     *
     * @throws AlreadyFinishedException
     * @throws StepNotFoundException
     *
     * @see FormFlow::handleRequest()
     */
    public function proceedToRequestedStepAndSave(): int
    {
        $currentStepName = $this->getCurrentStepName();

        // clear cached form
        $this->currentForm = null;

        $direction = $this->getSubmitAction() === self::SUBMIT_ACTION_BACK ?
            FormFlowNavigator::DIRECTION_BACKWARD : FormFlowNavigator::DIRECTION_FORWARD;
        $navigationResult = $this->navigator->proceedInDirection($direction, $this->getSteps());

        if ($navigationResult === FormFlowNavigator::NAV_FINISHED) {
            if ($this->getState() === self::STATE_FINISHED) {
                throw new AlreadyFinishedException($this, "Tried to finish already finished form flow.");
            }

            $this->setState(self::STATE_FINISHED);
        }

        $this->save($currentStepName);

        if ($navigationResult === FormFlowNavigator::NAV_FINISHED) {
            $this->dispatchEvent(new FlowFinishedEvent($this->getInstanceId(), $this->getData()), FlowFinishedEvent::NAME);
        }

        return $navigationResult;
    }

    /**
     * Returns th current step.
     *
     * @return Step
     *
     * @throws StepNotFoundException
     */
    public function getCurrentStep(): Step
    {
        $steps = $this->getSteps();
        $index = $this->navigator->getCurrentStepIndex();
        if ($index < 0 || $index >= count($steps)) {
            throw new StepNotFoundException($this);
        }

        return $steps[$index];
    }

    /**
     * @return array $stepStates[$stepName] = one of Step::STATE_* constants
     */
    protected function getStepStates(): array
    {
        $stepStates = [];

        foreach ($this->getSteps() as $step) {
            $stepStates[$step->getName()] = $step->getState();
        }

        return $stepStates;
    }

    /**
     * @param array $stepStates $stepName => $stepState
     */
    protected function createSteps(array $stepStates = [])
    {
        $this->steps = [];
        // step number to be displayed as a fallback step name, not the step index
        $stepNumber = 1;
        foreach ($this->getStepConfigs() as $stepConfig) {
            $stepName = empty($stepConfig->getName()) ? $stepNumber : $stepConfig->getName();

            if (array_key_exists($stepName, $stepStates)) {
                $state = $stepStates[$stepConfig->getName()];
            } else {
                $state = null; // init with default state
            }

            $step = new Step($stepName, $stepConfig, $state);
            $this->steps[] = $step;
            $stepNumber++;
        }
    }

    /**
     * Gets the form of the current step.
     *
     * @return FormInterface
     *
     * @throws StepNotFoundException
     */
    public function getCurrentForm(): FormInterface
    {
        if ($this->currentForm === null) {
            $flowConfig = $this->getFlowConfig();
            $step = $this->getCurrentStep();

            $options = [];
            if ($flowConfig->isAutoValidationGroups()) {
                $options['validation_groups'] = $step->getName();
            }

            $this->currentForm = $this->formFactory->create(
                $step->getFormClass(),
                $this->getData(),
                array_merge($options, $this->getCurrentFormOptions())
            );
        }

        return $this->currentForm;
    }

    /**
     * @return string id that identifies this form flow session
     */
    public function getInstanceId(): string
    {
        if ($this->instanceId === '') {
            $this->instanceId = uniqid();
        }

        return $this->instanceId;
    }

    /**
     * @return string one of FormFlow::SUBMIT_ACTION_* constants
     */
    protected function getSubmitAction(): string
    {
        return $this->submitAction;
    }

    /**
     * @return FormFlowConfig
     */
    protected function getFlowConfig(): FormFlowConfig
    {
        if (!$this->flowConfig instanceof FormFlowConfig) {

            $this->flowConfig = $this->getDefinition()->loadFlowConfig();
        }

        return $this->flowConfig;
    }

    /**
     * @return string route name where the user should be redirected after the flow is finished
     *                might be an empty string if no route is configured
     */
    public function getFinishedRoute(): string
    {
        return $this->getFlowConfig()->getFinishedRoute();
    }

    /**
     * @return Step[]
     */
    public function getSteps(): array
    {
        if (!is_array($this->steps)) {
            $this->createSteps();
        }

        return $this->steps;
    }

    /**
     * @return string
     *
     * @throws StepNotFoundException
     */
    public function getCurrentStepName(): string
    {
        return $this->getCurrentStep()->getName();
    }

    /**
     * @param string $stepName
     *
     * @return int
     */
    protected function getStepIndex(string $stepName): int
    {
        foreach ($this->getSteps() as $index => $step) {
            if ($step->getName() === $stepName) {

                return $index;
            }
        }

        throw new DomainException('Step name is not in steps config.');
    }

    /**
     * Gets routing parameters to a specific step
     *
     * @return array
     */
    public function getRouteParameters(): array
    {
        if ($this->getState() !== self::STATE_FINISHED) {
            try {
                $stepName = $this->getCurrentStepName();
            } catch (StepNotFoundException $e) {
                $stepName = '';
            }
        } else {
            $stepName = '';
        }

        return $this->getFlowConfig()->getRouteParameters($this->getName(), $this->getInstanceId(), $stepName);
    }

    /**
     * @return FormFlowView this can be used in views
     *
     * @throws StepNotFoundException
     */
    public function createView(): FormFlowView
    {
        // this is not 100% correct, but why would be the first step skippable?
        $canGoBack = $this->navigator->getCurrentStepIndex() > 0;

        return new FormFlowView($this, $this->getFlowConfig(), $this->formFactory, $canGoBack);
    }

    /**
     * Gets the form options to be used for the current step's form
     *
     * @return array
     *
     * @throws StepNotFoundException
     */
    protected function getCurrentFormOptions(): array
    {
        return $this->getCurrentStep()->getFormOptions($this->getData());
    }

    /**
     * Loads state from storage. If there is no stored state then initializes flow.
     *
     * @param string $instanceId
     */
    public function preFillFromStorage(string $instanceId)
    {
        try {
            $storedState = $this->storage->load($instanceId);
            $this->restoreFromStoredState($storedState);
        } catch (FlowInstanceNotFoundException $e) {
            $this->updateStepStates();
        }
    }

    /**
     * Updates step states
     */
    protected function updateStepStates()
    {
        $data = $this->getData();

        /** @var bool $canReach up to this point the user can access the flow */
        $canReach = true;
        $newSteps = [];
        $index = 0;
        $stepConfigs = $this->getStepConfigs();

        foreach ($this->getSteps() as $step) {
            $newState = $step->getUpdatedState($canReach, $data);
            foreach ($step->getUserErrors() as $errorString) {
                try {
                    $this->getCurrentForm()->addError(new FormError($errorString));
                } catch (StepNotFoundException $e) {
                    // well, then we don't show this message
                }
            }

            $newStep = new Step($step->getName(), $stepConfigs[$index], $newState);
            $canReach = $newStep->canGoPast($canReach);
            $newSteps[] = $newStep;
            $index ++;
        }

        $this->steps = $newSteps;
    }

    /**
     * Returns an object that can be stored in the storage backend and the flow state can be restored from it in
     * the next request.
     *
     * @return FormFlowStoredState
     */
    protected function getStoredState(): FormFlowStoredState
    {
        $normalizedData = $this->serializer->serialize($this->getData(), 'json');

        $storedState = new FormFlowStoredState();
        $storedState
            ->setFlowName($this->getName())
            ->setState($this->getState())
            ->setInstanceId($this->getInstanceId())
            ->setData($normalizedData)
            ->setStepStates($this->getStepStates());

        return $storedState;
    }

    /**
     * Restores the state stored in the storage backend
     *
     * @param FormFlowStoredState $storedState
     *
     * @see FormFlow::getStoredState()
     */
    protected function restoreFromStoredState(FormFlowStoredState $storedState)
    {
        $this->instanceId = $storedState->getInstanceId();
        $this->setState($storedState->getState());
        $this->createSteps($storedState->getStepStates());
        $normalizedData = $storedState->getData();

        $data = $this->serializer->deserialize($normalizedData, $this->getFlowConfig()->getDataClass(), 'json');
        $this->flowData = $data;
    }

    /**
     * @return StepConfig[]
     */
    private function getStepConfigs(): array
    {
        if ($this->stepConfigs === null) {
            $this->stepConfigs = $this->getDefinition()->loadStepConfigs();
        }

        return $this->stepConfigs;
    }

    /**
     * Dispatches the event with multiple names
     * * [namespace].[event]
     * * [namespace].[flowName].[event]
     * * [namespace].[flowName].[event].[stepName]  (only if $stepName is not null)
     *
     * @param Event $event
     * @param string $name the event name part
     * @param string|null $stepName
     */
    private function dispatchEvent(Event $event, string $name, string $stepName = null)
    {
        $namespace = 'tzunghaor_formflow';
        $eventNames = [
            implode('.', [$namespace, $name]),
            implode('.', [$namespace, $this->getName(), $name]),
        ];

        if ($stepName !== null) {
            $eventNames[] = implode('.', [$namespace, $this->getName(), $name, $stepName]);
        }

        foreach ($eventNames as $eventName) {
            $this->eventDispatcher->dispatch($eventName, $event);
        }
    }
}
