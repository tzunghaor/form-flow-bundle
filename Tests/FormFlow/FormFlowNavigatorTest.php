<?php
/**
 * Author: tzunghaor_at_primandras.hu
 * Created: 2018-09-09
 */

namespace Tzunghaor\FormFlowBundle\Tests\FormFlow;


use PHPUnit\Framework\TestCase;
use Tzunghaor\FormFlowBundle\Exception\NavigationException;
use Tzunghaor\FormFlowBundle\FormFlow\FormFlowNavigator;
use Tzunghaor\FormFlowBundle\Tests\TestHelper\TestHelper;

class FormFlowNavigatorTest extends TestCase
{
    /**
     * @dataProvider hasStepsLeftProvider
     *
     * @param array $steps
     * @param int $currentStepIndex
     * @param bool $expected
     */
    public function testHasStepsLeft(array $steps, int $currentStepIndex, bool $expected)
    {
        $navigator = new FormFlowNavigator($currentStepIndex);
        $this->assertEquals($expected, $navigator->hasStepsLeft($steps));
    }

    /**
     * @return array
     */
    public function hasStepsLeftProvider(): array
    {
        $helper = new TestHelper($this);

        // typical initial flow state -> one step left
        $testCases[] = [
            [
                $helper->createMockStep(false, false, true),
                $helper->createMockStep(false, false, true),
            ],
            0,
            true
        ];

        // one valid step, one skipped -> no step left
        $testCases[] = [
            [
                $helper->createMockStep(true, false, true),
                $helper->createMockStep(false, true, false),
            ],
            1,
            false
        ];

        return $testCases;
    }

    /**
     * @dataProvider setCurrentStepIndexProvider
     * 
     * @param array $steps
     * @param int $desiredIndex
     * @param int $direction
     * @param int $expectedResult
     * @param int $expectedStepIndex
     */
    public function testSetCurrentStepIndex(
        array $steps,
        int $desiredIndex,
        int $direction,
        int $expectedResult,
        int $expectedStepIndex
    ) {
        $navigator = new FormFlowNavigator(0);
        $result = $navigator->setCurrentStepIndex($desiredIndex, $steps, $direction);

        $this->assertEquals($expectedResult, $result, 'Wrong navigation result');
        $this->assertEquals($expectedStepIndex, $navigator->getCurrentStepIndex(), 'Wrong current index');
    }

    /**
     * @return array
     */
    public function setCurrentStepIndexProvider(): array
    {
        $helper = new TestHelper($this);
        
        // nothing is submitted => set step index ends always on first step
        $steps1 = [
            $helper->createMockStep(false, false, true),
            $helper->createMockStep(false, false, false),
        ];
        $steps1Cases = [
            [$steps1, 0, FormFlowNavigator::DIRECTION_FORWARD, FormFlowNavigator::NAV_SUCCESS, 0],
            [$steps1, 1, FormFlowNavigator::DIRECTION_FORWARD, FormFlowNavigator::NAV_FALLBACK, 0],
            [$steps1, 2, FormFlowNavigator::DIRECTION_FORWARD, FormFlowNavigator::NAV_FALLBACK, 0],
            [$steps1, 3, FormFlowNavigator::DIRECTION_FORWARD, FormFlowNavigator::NAV_FALLBACK, 0],
            [$steps1, 0, FormFlowNavigator::DIRECTION_BACKWARD, FormFlowNavigator::NAV_SUCCESS, 0],
            [$steps1, 1, FormFlowNavigator::DIRECTION_BACKWARD, FormFlowNavigator::NAV_FALLBACK, 0],
            [$steps1, 2, FormFlowNavigator::DIRECTION_BACKWARD, FormFlowNavigator::NAV_FALLBACK, 0],
        ];

        // cannot enter second step => only first step is accessible
        $steps2 = [
            $helper->createMockStep(true, false, true),
            $helper->createMockStep(true, false, false),
        ];
        $steps2Cases = [
            [$steps2, 0, FormFlowNavigator::DIRECTION_FORWARD, FormFlowNavigator::NAV_SUCCESS, 0],
            [$steps2, 1, FormFlowNavigator::DIRECTION_FORWARD, FormFlowNavigator::NAV_FALLBACK, 0],
            [$steps2, 0, FormFlowNavigator::DIRECTION_BACKWARD, FormFlowNavigator::NAV_SUCCESS, 0],
            [$steps2, 1, FormFlowNavigator::DIRECTION_BACKWARD, FormFlowNavigator::NAV_FALLBACK, 0],
        ];

        // Second step should be skipped
        $steps3 = [
            $helper->createMockStep(true, false, true), // valid
            $helper->createMockStep(false, true, false), // skipped
            $helper->createMockStep(false, false, true), // invalid accessible
        ];
        $steps3Cases = [
            [$steps3, 0, FormFlowNavigator::DIRECTION_FORWARD, FormFlowNavigator::NAV_SUCCESS, 0],
            [$steps3, 1, FormFlowNavigator::DIRECTION_FORWARD, FormFlowNavigator::NAV_FALLBACK, 2],
            [$steps3, 2, FormFlowNavigator::DIRECTION_FORWARD, FormFlowNavigator::NAV_SUCCESS, 2],
            [$steps3, 0, FormFlowNavigator::DIRECTION_BACKWARD, FormFlowNavigator::NAV_SUCCESS, 0],
            [$steps3, 1, FormFlowNavigator::DIRECTION_BACKWARD, FormFlowNavigator::NAV_FALLBACK, 0],
            [$steps3, 2, FormFlowNavigator::DIRECTION_BACKWARD, FormFlowNavigator::NAV_SUCCESS, 2],
        ];

        // Second step should be skipped, fourth cannot be accessed
        $steps4 = [
            $helper->createMockStep(true, false, true), // valid
            $helper->createMockStep(true, true, false), // skipped
            $helper->createMockStep(true, false, true), // valid
            $helper->createMockStep(false, false, false), // blocked
            $helper->createMockStep(false, true, false), // skipped
        ];
        $steps4Cases = [
            [$steps4, 0, FormFlowNavigator::DIRECTION_FORWARD, FormFlowNavigator::NAV_SUCCESS, 0],
            [$steps4, 1, FormFlowNavigator::DIRECTION_FORWARD, FormFlowNavigator::NAV_FALLBACK, 2],
            [$steps4, 2, FormFlowNavigator::DIRECTION_FORWARD, FormFlowNavigator::NAV_SUCCESS, 2],
            [$steps4, 3, FormFlowNavigator::DIRECTION_FORWARD, FormFlowNavigator::NAV_FALLBACK, 2],
            [$steps4, 4, FormFlowNavigator::DIRECTION_FORWARD, FormFlowNavigator::NAV_FALLBACK, 2],
            [$steps4, 5, FormFlowNavigator::DIRECTION_FORWARD, FormFlowNavigator::NAV_FALLBACK, 2],
            [$steps4, 6, FormFlowNavigator::DIRECTION_FORWARD, FormFlowNavigator::NAV_FALLBACK, 2],

            [$steps4, 0, FormFlowNavigator::DIRECTION_BACKWARD,FormFlowNavigator::NAV_SUCCESS, 0],
            [$steps4, 1, FormFlowNavigator::DIRECTION_BACKWARD, FormFlowNavigator::NAV_FALLBACK, 0],
            [$steps4, 2, FormFlowNavigator::DIRECTION_BACKWARD, FormFlowNavigator::NAV_SUCCESS, 2],
            [$steps4, 3, FormFlowNavigator::DIRECTION_BACKWARD, FormFlowNavigator::NAV_FALLBACK, 2],
            [$steps4, 4, FormFlowNavigator::DIRECTION_BACKWARD, FormFlowNavigator::NAV_FALLBACK, 2],
            [$steps4, 5, FormFlowNavigator::DIRECTION_BACKWARD, FormFlowNavigator::NAV_FALLBACK, 2],
        ];

        return array_merge($steps1Cases, $steps2Cases, $steps3Cases, $steps4Cases);
    }

    /**
     * Tests that correct Exception is thrown if no steps can be accessed
     */
    public function testStucked()
    {
        $helper = new TestHelper($this);
        $steps = [
            $helper->createMockStep(false, true, false),
            $helper->createMockStep(false, false, false),
        ];

        $navigator = new FormFlowNavigator(0);

        $this->expectException(NavigationException::class);
        $navigator->setCurrentStepIndex(1, $steps, FormFlowNavigator::DIRECTION_FORWARD);
    }
}