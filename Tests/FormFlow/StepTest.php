<?php
/**
 * Author: tzunghaor_at_primandras.hu
 * Created: 2017-11-05
 */

namespace Tzunghaor\FormFlowBundle\Tests\FormFlow;


use ArrayObject;
use PHPUnit\Framework\TestCase;
use Tzunghaor\FormFlowBundle\FormFlow\Step;
use Tzunghaor\FormFlowBundle\Definition\StepConfig;

class StepTest extends TestCase
{
    /**
     * Tests that setConfig correctly copies config values
     */
    public function testSetConfig()
    {
        $stepConfig = new StepConfig();
        $stepConfig
            ->setName('test name in config')
            ->setLabel('test label')
            ->setFormClass('test form class')
            ->setFormOptions(['static' => 'test form option'])
            ->setFormOptionsCallable(function ($data) {
                return ['callable' => $data['attr'] . ' form option'];
            })
            ->setView('test view')
            ->setViewVariables(['static' => 'test view variable'])
            ->setViewVariablesCallable(function ($data) {
                return ['callable' => $data['attr'] . ' view variable'];
            });

        $step = new Step('test name in constructor', $stepConfig);

        $data = new ArrayObject(['attr' => 'data-test']);
        $expectedFormOptions = [
            'static' => 'test form option',
            'callable' => 'data-test form option',
        ];
        $expectedViewVariables = [
            'static' => 'test view variable',
            'callable' => 'data-test view variable',
        ];

        $this->assertEquals('test name in constructor', $step->getName());
        $this->assertEquals('test label', $step->getLabel());
        $this->assertEquals('test form class', $step->getFormClass());
        $this->assertEquals($expectedFormOptions, $step->getFormOptions($data));
        $this->assertEquals('test view', $step->getView());
        $this->assertEquals($expectedViewVariables, $step->getViewVariables($data));
    }

    /**
     * @dataProvider shouldSkipProvider
     *
     * @param bool $hasCallable whether skip callable is set
     * @param mixed $callableReturn return value of skip callable
     * @param string $expectedState
     */
    public function testSkipCallable(bool $hasCallable, $callableReturn, string $expectedState)
    {
        /** @var StepConfig|\PHPUnit_Framework_MockObject_MockObject $stepConfig */
        $stepConfig = $this->createMock(StepConfig::class);
        $skipCallable = $hasCallable ? function () use ($callableReturn) {
            return $callableReturn;
        } : null;

        $stepConfig->method('getSkipConditionCallable')->willReturn($skipCallable);
        $step = new Step('1', $stepConfig);

        $this->assertEquals($expectedState, $step->getUpdatedState(true, new ArrayObject()));
    }

    public function shouldSkipProvider()
    {
        return [
            [false, null, Step::STATE_ACCESSIBLE], // no callback => should not skip
            [true, false, Step::STATE_ACCESSIBLE], // callback returns false => should not skip
            [true, null, Step::STATE_ACCESSIBLE],  // callback returns invalid type => should not skip
            [true, true, Step::STATE_SKIP],   // callback returns true => should skip
        ];
    }

    /**
     * @dataProvider enterErrorsProvider
     *
     * @param bool $hasCallable whether enter callable is set
     * @param mixed $callableReturn return value of enter callable
     * @param string $expectedState
     */
    public function testEnterErrors(bool $hasCallable, $callableReturn, string $expectedState)
    {
        /** @var StepConfig|\PHPUnit_Framework_MockObject_MockObject $stepConfig */
        $stepConfig = $this->createMock(StepConfig::class);
        $enterCallable = $hasCallable ? function () use ($callableReturn) {
            return $callableReturn;
        } : null;

        $stepConfig->method('getEnterErrorsCallable')->willReturn($enterCallable);
        $step = new Step('1', $stepConfig);

        $this->assertEquals($expectedState, $step->getUpdatedState(true, new ArrayObject()));
    }

    public function enterErrorsProvider()
    {
        return [
            [false, null, Step::STATE_ACCESSIBLE], // no callback => can enter
            [true, ['something'], Step::STATE_BLOCKED], // callback returns non-empty array => cannot enter
            [true, [], Step::STATE_ACCESSIBLE],   // callback returns empty array => can enter
        ];
    }

    /**
     * @dataProvider issersProvider
     *
     * @param string $status
     * @param bool $expectedAccessible
     * @param bool $expectedValid
     * @param bool $expectedSkipped
     * @param bool $expectedDone
     */
    public function testIssers(
        string $status,
        bool $expectedAccessible,
        bool $expectedValid,
        bool $expectedSkipped,
        bool $expectedDone
    ) {
        $step = new Step('test', new StepConfig(), $status);

        $this->assertEquals($expectedAccessible, $step->isAccessible(), 'isAccessible incorrect');
        $this->assertEquals($expectedValid, $step->isValid(), 'isValid incorrect');
        $this->assertEquals($expectedSkipped, $step->isSkipped(), 'isSkipped incorrect');
        $this->assertEquals($expectedDone, $step->isDone(), 'isDone incorrect');
    }

    public function issersProvider(): array
    {
        return [
            [Step::STATE_INIT, false, false, false, false],
            [Step::STATE_ACCESSIBLE, true, false, false, false],
            [Step::STATE_VALID, true, true, false, true],
            [Step::STATE_SKIP, false, false, true, true],
            [Step::STATE_SKIP_VALID, false, true, true, true],
            [Step::STATE_BLOCKED, false, false, false, false],
            [Step::STATE_BLOCKED_VALID, false, true, false, false],
        ];
    }
}
