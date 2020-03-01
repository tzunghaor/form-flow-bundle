<?php


namespace Tzunghaor\FormFlowBundle\Tests\FormFlow;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Tzunghaor\FormFlowBundle\FormFlow\FormFlow;
use Tzunghaor\FormFlowBundle\Definition\FormFlowConfig;
use Tzunghaor\FormFlowBundle\FormFlow\FormFlowView;
use Tzunghaor\FormFlowBundle\FormFlow\Step;

/**
 * Test for FormFlowView
 */
class FormFlowViewTest extends TestCase
{
    /**
     * @throws \Tzunghaor\FormFlowBundle\Exception\StepNotFoundException
     */
    public function test()
    {
        $mockStep = $this->createMock(Step::class);
        $mockStep->method('getName')->willReturn('first');
        $mockStep->method('getView')->willReturn('test-view');
        $mockStep->method('getViewVariables')->willReturn(['var' => 'test']);

        $mockFormView = $this->createMock(FormView::class);
        $mockForm = $this->createMock(FormInterface::class);
        $mockForm->method('createView')->willReturn($mockFormView);

        /** @var PHPUnit_Framework_MockObject_MockObject|FormFlow $mockFormFlow */
        $mockFormFlow = $this->createMock(FormFlow::class);
        $mockFormFlow->method('getName')->willReturn('test-flow');
        $mockFormFlow->method('getCurrentStep')->willReturn($mockStep);
        $mockFormFlow->method('getCurrentForm')->willReturn($mockForm);
        $mockFormFlow->method('getSteps')->willReturn(['first', 'second']);
        $mockFormFlow->method('getInstanceId')->willReturn('test-instance');

        $lastFormFactoryArguments = null;

        /** @var PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface $mockFormFactory */
        $mockFormFactory = $this->createMock(FormFactoryInterface::class);
        $mockFormFactory->method('create')->willReturnCallback(
            function ($type, $data, $options) use (&$lastFormFactoryArguments, $mockForm) {
                $lastFormFactoryArguments = ['type' => $type, 'data' => $data, 'options' => $options];

                return $mockForm;
            }
        );

        $flowConfig = new FormFlowConfig();
        $flowConfig
            ->setFlowViewVariable('aaa_flow')
            ->setFormViewVariable('bbb_form')
            ->setInstanceIdParam('ccc_instance')
            ->setStepParam('ddd_step')
            ->setSubmitName('eee_submit')
            ->setFlowNameParam('fff_flow')
        ;

        $view = new FormFlowView($mockFormFlow, $flowConfig, $mockFormFactory, false);

        $expectedViewVariables = ['var' => 'test', 'aaa_flow' => $view, 'bbb_form' => $mockFormView];

        $this->assertEquals('test-view', $view->getView());
        $this->assertEquals($expectedViewVariables, $view->getViewVariables());
        $this->assertEquals('test-flow', $view->getName());
        $this->assertEquals($mockStep, $view->getCurrentStep());
        $this->assertEquals('first', $view->getCurrentStepName());
        $this->assertEquals(['first', 'second'], $view->getSteps());
        $this->assertEquals('test-instance', $view->getInstanceId());

        $expectedRouteParamsDefault = ['fff_flow' => 'test-flow', 'ccc_instance' => 'test-instance', 'ddd_step' => 'first'];
        $this->assertEquals($expectedRouteParamsDefault, $view->getRouteParameters());

        $expectedRouteParamsSecond = ['fff_flow' => 'test-flow', 'ccc_instance' => 'test-instance', 'ddd_step' => 'second'];
        $this->assertEquals($expectedRouteParamsSecond, $view->getRouteParameters('second'));

        $lastFormFactoryArguments = null;
        $this->assertEquals($mockFormView, $view->getForwardSubmitButton(['extra' => 'aaa']));
        $this->assertEquals(SubmitType::class, $lastFormFactoryArguments['type']);
        $expectedOptionsForward = [
            'label' => 'tzunghaor.form-flow.forward-submit',
            'attr' => ['name' => 'eee_submit', 'value' => FormFlow::SUBMIT_ACTION_FORWARD],
            'extra' => 'aaa',
        ];
        $this->assertEquals($expectedOptionsForward, $lastFormFactoryArguments['options']);

        $lastFormFactoryArguments = null;
        $this->assertEquals($mockFormView, $view->getBackSubmitButton(['extra' => 'bbb']));
        $this->assertEquals(SubmitType::class, $lastFormFactoryArguments['type']);
        $expectedOptionsBack = [
            'label' => 'tzunghaor.form-flow.back-submit',
            'attr' => ['name' => 'eee_submit', 'value' => FormFlow::SUBMIT_ACTION_BACK, 'formnovalidate' => ''],
            'extra' => 'bbb',
            'disabled' => true,
        ];
        $this->assertEquals($expectedOptionsBack, $lastFormFactoryArguments['options']);
    }
}