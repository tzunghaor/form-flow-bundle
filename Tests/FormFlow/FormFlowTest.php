<?php
/**
 * Author: tzunghaor_at_primandras.hu
 * Created: 2017-10-27
 */

namespace Tzunghaor\FormFlowBundle\Tests\FormFlow;


use ArrayObject;
use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\RecursiveValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tzunghaor\FormFlowBundle\Entity\FormFlowStoredState;
use Tzunghaor\FormFlowBundle\Exception\AlreadyFinishedException;
use Tzunghaor\FormFlowBundle\Exception\StepNotFoundException;
use Tzunghaor\FormFlowBundle\FormFlow\FormFlow;
use Tzunghaor\FormFlowBundle\Definition\FormFlowConfig;
use Tzunghaor\FormFlowBundle\Definition\FormFlowDefinitionInterface;
use Tzunghaor\FormFlowBundle\FormFlow\Step;
use Tzunghaor\FormFlowBundle\Definition\StepConfig;
use Tzunghaor\FormFlowBundle\Tests\TestHelper\DummyDispatcher;
use Tzunghaor\FormFlowBundle\Tests\TestHelper\DummyStorage;
use Tzunghaor\FormFlowBundle\Storage\StorageInterface;

class FormFlowTest extends TestCase
{
    const FLOW_NAME = 'test-flow';
    const INSTANCE_ID = 'test-instance';

    /** @var PHPUnit_Framework_MockObject_MockObject|ValidatorInterface */
    private $mockValidator;

    /** @var PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface */
    private $mockFormFactory;

    /** @var PHPUnit_Framework_MockObject_MockObject|SerializerInterface */
    private $mockSerializer;

    /** @var StorageInterface */
    private $mockStorage;

    /** @var EventDispatcherInterface */
    private $mockDispatcher;

    /** @var FormFlowConfig */
    private $flowConfig;

    public function setUp()
    {
        parent::setUp();

        $this->flowConfig = (new FormFlowConfig())->setDataClass(ArrayObject::class);

        $this->mockStorage = new DummyStorage();
        $this->mockFormFactory = $this->getMockBuilder(FormFactory::class)->disableOriginalConstructor()->getMock();
        $this->mockDispatcher = new DummyDispatcher();
        $this->mockValidator = $this->getMockBuilder(RecursiveValidator::class)->disableOriginalConstructor()->getMock();
        $this->mockSerializer = $this->getMockBuilder(SerializerInterface::class)->disableOriginalConstructor()->getMock();

        $this->mockSerializer->method('serialize')->willReturn('{}');
    }

    /**
     * Tests canSaveAndProceedCanSaveAndProceed
     *
     * @dataProvider dataProviderCanSaveAndProceed
     *
     * @param array $stepsIn
     * @param FormInterface $currentForm
     * @param bool $isValid
     * @param bool $expected
     *
     * @throws StepNotFoundException
     */
    public function testCanSaveAndProceed(array $stepsIn, FormInterface $currentForm, bool $isValid, bool $expected)
    {
        $flow = $this->createFlowWithValidation($stepsIn, true, $isValid);

        $this->mockFormFactory->method('create')->willReturn($currentForm);

        // tested method
        $result = $flow->canProceedAndSave();

        $this->assertSame($expected, $result);
    }

    public function dataProviderCanSaveAndProceed()
    {
        $notSubmittedForm = $this->createMockNotSubmittedForm();
        $invalidForm = $this->createMockInvalidForm();
        $validForm = $this->createMockValidForm();

        $stepsIncomplete = [
            ['name' => 'first', 'state' => Step::STATE_VALID],
            ['name' => 'second', 'state' => Step::STATE_ACCESSIBLE],
        ];

        $stepsComplete = [
            ['name' => 'first', 'state' => Step::STATE_VALID],
        ];

        // steps, form, valid, expected
        return [
            // not submitted form => cannot proceed
            [$stepsIncomplete, $notSubmittedForm, true, false],
            // submitted but invalid form => cannot proceed
            [$stepsIncomplete, $invalidForm, true, false],
            // submitted and valid form, but flow data invalid, but not last step  => can proceed
            [$stepsIncomplete, $validForm, false, true],
            // submitted and valid form, flow data valid, not last step  => can proceed
            [$stepsIncomplete, $validForm, true, true],
            // submitted and valid form, but flow data invalid, last step  => cannot proceed
            [$stepsComplete, $validForm, false, false],
            // submitted and valid form, flow data valid, last step  => can proceed
            [$stepsComplete, $validForm, true, true],
        ];
    }

    /**
     * @dataProvider dataProviderHandleRequestCaseOk
     *
     * @param array $stepsIn
     * @param Request $request
     * @param FormInterface $currentForm - mockFormFactory will be set up to return this
     * @param string $expectedStep
     * @param array $expectedStepStates
     *
     * @throws StepNotFoundException
     * @throws AlreadyFinishedException
     */
    public function testHandleRequestCaseOk(array $stepsIn, Request $request, FormInterface $currentForm, string $expectedStep, array $expectedStepStates)
    {
        $flow = $this->createFlowWithValidation($stepsIn, isset($stepsIn[0]['state']), true);

        $this->mockFormFactory->method('create')->willReturn($currentForm);

        // tested method
        $flow->handleRequest($request);

        $stepStates = array_map(function (Step $step) {return $step->getState();}, $flow->getSteps());
        $this->assertSame($expectedStep, $flow->getCurrentStepName());
        $this->assertSame($expectedStepStates, $stepStates);
    }

    public function dataProviderHandleRequestCaseOk(): array
    {
        $stepsNoInfo = [
            ['name' => 'first'],
            ['name' => 'second'],
            ['name' => 'third'],
        ];

        $stepsSecondAccessible = [
            ['name' => 'first', 'state' => Step::STATE_VALID],
            ['name' => 'second', 'state' => Step::STATE_ACCESSIBLE],
            ['name' => 'third', 'state' => Step::STATE_BLOCKED],
        ];

        $stepsThirdAccessible = [
            ['name' => 'first', 'state' => Step::STATE_VALID],
            ['name' => 'second', 'state' => Step::STATE_VALID],
            ['name' => 'third', 'state' => Step::STATE_ACCESSIBLE],
        ];

        $noInfoGetRequest = new Request();
        $noInfoGetRequest->setMethod(Request::METHOD_GET);

        $thirdStepGetRequest = new Request(['step' => 'third', 'instanceId' => self::INSTANCE_ID]);
        $thirdStepGetRequest->setMethod(Request::METHOD_GET);

        $firstStepPostRequest = new Request(['step' => 'first', 'instanceId' => self::INSTANCE_ID]);
        $firstStepPostRequest->setMethod(Request::METHOD_POST);

        $notSubmittedForm = $this->createMockNotSubmittedForm();
        $invalidForm = $this->createMockInvalidForm();
        $validForm = $this->createMockValidForm();

        // stepsIn, request, currentForm, expectedStep, expectedStepStates
        return [
            // GET - no info in request => start from beginning
            'no info get' =>
                [$stepsNoInfo, $noInfoGetRequest, $notSubmittedForm, 'first',
                    [Step::STATE_ACCESSIBLE, Step::STATE_BLOCKED, Step::STATE_BLOCKED]],
            // GET - requested step is not accessible => go to last accessible step
            'inaccessible get' =>
                [$stepsSecondAccessible, $thirdStepGetRequest, $notSubmittedForm, 'second',
                    [Step::STATE_VALID, Step::STATE_ACCESSIBLE, Step::STATE_BLOCKED]],
            // GET - requested step is accessible => go to it
            'accessible get' =>
                [$stepsThirdAccessible, $thirdStepGetRequest, $notSubmittedForm, 'third',
                    [Step::STATE_VALID, Step::STATE_VALID, Step::STATE_ACCESSIBLE]],
            // POST - form invalid => current step and after are invalidated
            'invalid form post' =>
                [$stepsSecondAccessible, $firstStepPostRequest, $invalidForm, 'first',
                    [Step::STATE_ACCESSIBLE, Step::STATE_BLOCKED, Step::STATE_BLOCKED]],
            // POST - form valid => current step is validated, after are invalidated
            'valid form post' =>
                [$stepsThirdAccessible, $firstStepPostRequest, $validForm, 'first',
                    [Step::STATE_VALID, Step::STATE_ACCESSIBLE, Step::STATE_BLOCKED]],
        ];
    }

    /**
     * Tests that handleRequest throws AlreadyFinishedException if called with an already finished flow instance
     *
     * @throws Exception
     */
    public function testHandleRequestCaseAlreadyFinishedException()
    {
        $flow = $this->createFlowWithValidation([['name' => 'first']], true, true, false);
        $this->mockFormFactory->method('create')->willReturn($this->createMockNotSubmittedForm());

        $this->expectException(AlreadyFinishedException::class);

        // tested method
        // the request does not matter: createFlowWithValidation sets up mock store to return a finished state
        $flow->handleRequest(new Request(['instanceId' => self::INSTANCE_ID]));
    }

    /**
     * @dataProvider dataProviderProceedToRequestedStepAndSave
     *
     * @param array $stepsIn
     * @param Request $request
     * @param bool $expectFinish
     * @param string $expectedStep
     *
     * @throws Exception
     */
    public function testProceedToRequestedStepAndSave(
        array $stepsIn, Request $request, bool $expectFinish, string $expectedStep = null
    ) {
        $flow = $this->createFlowWithValidation($stepsIn, isset($stepsIn[0]['state']), true);

        $this->mockFormFactory->method('create')->willReturn($this->createMockValidForm());
        $flow->handleRequest($request);
        $beforeStep = $flow->getCurrentStepName();

        // tested method
        $flow->proceedToRequestedStepAndSave();

        $savedState = $this->mockStorage->load(self::INSTANCE_ID);

        if ($expectedStep !== null) {
            $this->assertEquals($expectedStep, $flow->getCurrentStepName());
        }

        $this->assertEquals($expectFinish ? FormFlow::STATE_FINISHED : FormFlow::STATE_ACTIVE, $flow->getState());

        $this->assertCount($expectFinish ? 1 : 0,
            $this->mockDispatcher->getDispatchedEvents('tzunghaor_formflow.finished'));
        $this->assertCount($expectFinish ? 1 : 0,
            $this->mockDispatcher->getDispatchedEvents('tzunghaor_formflow.test-flow.finished'));

        $this->assertCount(1, $this->mockDispatcher->getDispatchedEvents('tzunghaor_formflow.save'));
        $this->assertCount(1, $this->mockDispatcher->getDispatchedEvents('tzunghaor_formflow.test-flow.save'));
        $this->assertCount(1,
            $this->mockDispatcher->getDispatchedEvents('tzunghaor_formflow.test-flow.save.' . $beforeStep));

        $this->assertEquals($expectFinish ? FormFlow::STATE_FINISHED : FormFlow::STATE_ACTIVE, $savedState->getState());
        $this->assertEquals(self::FLOW_NAME, $savedState->getFlowName());
        $this->assertEquals('{}', $savedState->getData());
    }

    public function dataProviderProceedToRequestedStepAndSave(): array
    {
        $stepsSecondAccessible = [
            ['name' => 'first', 'state' => Step::STATE_VALID],
            ['name' => 'second', 'state' => Step::STATE_ACCESSIBLE],
            ['name' => 'third', 'state' => Step::STATE_ACCESSIBLE],
        ];

        $stepsSecondSkipped = [
            ['name' => 'first', 'state' => Step::STATE_VALID],
            ['name' => 'second', 'state' => Step::STATE_SKIP, 'skip' => true],
            ['name' => 'third', 'state' => Step::STATE_ACCESSIBLE],
        ];

        $stepsLastSkipped = [
            ['name' => 'first', 'state' => Step::STATE_ACCESSIBLE],
            ['name' => 'second', 'state' => Step::STATE_SKIP, 'skip' => true],
        ];

        $requestFirstForward = new Request(['step' => 'first', 'submit' => 'forward', 'instanceId' => self::INSTANCE_ID]);
        $requestFirstBackward = new Request(['step' => 'first', 'submit' => 'back', 'instanceId' => self::INSTANCE_ID]);
        $requestSecondForward = new Request(['step' => 'second', 'submit' => 'forward', 'instanceId' => self::INSTANCE_ID]);
        $requestSecondBackward = new Request(['step' => 'second', 'submit' => 'back', 'instanceId' => self::INSTANCE_ID]);
        $requestThirdForward = new Request(['step' => 'third', 'submit' => 'forward', 'instanceId' => self::INSTANCE_ID]);
        $requestThirdBackward = new Request(['step' => 'third', 'submit' => 'back', 'instanceId' => self::INSTANCE_ID]);

        // stepsIn, request, expectFinish, expectedStep
        return [
            'forward ok' =>
                [$stepsSecondAccessible, $requestSecondForward, false, 'third'],
            'forward with skip' =>
                [$stepsSecondSkipped, $requestFirstForward, false, 'third'],
            'forward finish' =>
                [$stepsSecondSkipped, $requestThirdForward, true, null],
            'forward from skipped finish' =>
                [$stepsSecondSkipped, $requestSecondForward, true, null],
            'forward skip finish' =>
                [$stepsLastSkipped, $requestFirstForward, true, null],
            'backward from first' =>
                [$stepsLastSkipped, $requestFirstBackward, false, 'first'],
            'backward ok' =>
                [$stepsSecondAccessible, $requestSecondBackward, false, 'first'],
            'backward skip ok' =>
                [$stepsSecondSkipped, $requestThirdBackward, false, 'first'],
        ];
    }

    /**
     * @throws AlreadyFinishedException
     * @throws StepNotFoundException
     */
    public function testGetRouteParameters()
    {
        $this->flowConfig = (new FormFlowConfig())
            ->setDataClass(ArrayObject::class)
            ->setFlowNameParam('aaa_flow')
            ->setInstanceIdParam('bbb_instance')
            ->setStepParam('ccc_step')
        ;

        $stepsIn = [
            ['name' => 'first'],
        ];

        $this->mockFormFactory->method('create')->willReturn($this->createMockNotSubmittedForm());

        $flow = $this->createFlowWithValidation($stepsIn, false, true);
        // enforce instance id with request
        $flow->handleRequest(new Request(['bbb_instance' => self::INSTANCE_ID]));

        // tested method
        $routeParams = $flow->getRouteParameters();

        $this->assertEquals(self::FLOW_NAME, $routeParams['aaa_flow']);
        $this->assertEquals(self::INSTANCE_ID, $routeParams['bbb_instance']);
        $this->assertEquals('first', $routeParams['ccc_step']);
    }

    /**
     * @throws StepNotFoundException
     * @throws AlreadyFinishedException
     */
    public function testCreateView()
    {
        $stepsIn = [
            ['name' => 'first'],
        ];

        $this->mockFormFactory->method('create')->willReturn($this->createMockNotSubmittedForm());

        $flow = $this->createFlowWithValidation($stepsIn, false, true);
        // enforce instance id with request
        $flow->handleRequest(new Request(['instanceId' => self::INSTANCE_ID]));

        // tested method
        $view = $flow->createView();

        $this->assertEquals(self::FLOW_NAME, $view->getName());
        $this->assertEquals(self::INSTANCE_ID, $view->getInstanceId());
    }

    // ------------------------- helper functions

    /**
     * Creates the FormFlow test subject with $this->mockServices
     *
     * @param array $stepsIn
     *
     * @return FormFlow
     */
    private function createFlow(array $stepsIn): FormFlow
    {
        $definition = new class ($stepsIn, $this->flowConfig) implements FormFlowDefinitionInterface {
            /**
             * @var array
             */
            private $stepsIn;
            /**
             * @var FormFlowConfig
             */
            private $flowConfig;

            public function __construct(array $stepsIn, FormFlowConfig $flowConfig)
            {
                $this->stepsIn = $stepsIn;
                $this->flowConfig = $flowConfig;
            }

            function loadFlowConfig(): FormFlowConfig
            {
                return $this->flowConfig;
            }

            function loadStepConfigs(): array
            {
                $stepConfigs = [];
                foreach ($this->stepsIn as $stepIn) {
                    $stepConfig = (new StepConfig())->setName($stepIn['name']);
                    if (isset($stepIn['skip']) && $stepIn['skip']) {
                        $stepConfig->setSkipConditionCallable(function () { return true; });
                    }
                    $stepConfigs[] = $stepConfig;
                }
                return $stepConfigs;
            }
        };

        return new FormFlow(self::FLOW_NAME, $definition, $this->mockStorage, $this->mockFormFactory,
            $this->mockDispatcher, $this->mockValidator, $this->mockSerializer);
    }

    /**
     * @param array $stepsIn
     * @param bool $isStored
     * @param bool $isValid if false, the mockValidator will report error for the whole flow data
     * @param bool $isActive the stored state is active - usable only with $isStored=true
     *
     * @return FormFlow
     */
    private function createFlowWithValidation(array $stepsIn, bool $isStored, bool $isValid = true, bool $isActive = true): FormFlow
    {
        if ($isValid) {
            $validationList = new ConstraintViolationList([]);
        } else {
            $mockViolation = $this->getMockBuilder(ConstraintViolation::class)->disableOriginalConstructor()->getMock();
            $validationList = new ConstraintViolationList([$mockViolation]);
        }

        if ($isStored) {
            $storedState = new FormFlowStoredState();
            $storedState
                ->setFlowName(self::FLOW_NAME)
                ->setInstanceId(self::INSTANCE_ID)
                ->setState($isActive ? FormFlow::STATE_ACTIVE : FormFlow::STATE_FINISHED)
                ->setData('{}')
                ->setStepStates(array_column($stepsIn, 'state', 'name'))
            ;
            $this->mockStorage->save($storedState);
        }

        $this->mockValidator->method('validate')->willReturn($validationList);

        return $this->createFlow($stepsIn);
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject a mock form that claims that it is not submitted
     */
    private function createMockNotSubmittedForm()
    {
        $notSubmittedForm = $this->getMockBuilder(FormInterface::class)->setMethods(['isSubmitted', 'isValid'])
            ->getMockForAbstractClass();
        $notSubmittedForm->method('isSubmitted')->willReturn(false);

        return $notSubmittedForm;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject a mock form that claims that it submitted but not valid
     */
    private function createMockInvalidForm()
    {
        $invalidForm = $this->getMockBuilder(FormInterface::class)->setMethods(['isSubmitted', 'isValid'])
            ->getMockForAbstractClass();
        $invalidForm->method('isSubmitted')->willReturn(true);
        $invalidForm->method('isValid')->willReturn(false);

        return $invalidForm;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject a mock form that claims that it is submitted and valid
     */
    private function createMockValidForm()
    {
        $mockFormConfig = $this->getMockBuilder(FormConfigInterface::class)->getMock();
        $validForm = $this->getMockBuilder(FormInterface::class)->setMethods(['isSubmitted', 'isValid', 'getConfig'])
            ->getMockForAbstractClass();
        $validForm->method('isSubmitted')->willReturn(true);
        $validForm->method('isValid')->willReturn(true);
        $validForm->method('getConfig')->willReturn($mockFormConfig);

        return $validForm;
    }
}
