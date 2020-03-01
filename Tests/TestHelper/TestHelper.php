<?php
/**
 * Author: tzunghaor_at_primandras.hu
 * Created: 2018-09-09
 */

namespace Tzunghaor\FormFlowBundle\Tests\TestHelper;


use PHPUnit\Framework\TestCase;
use Tzunghaor\FormFlowBundle\FormFlow\Step;

class TestHelper
{
    /**
     * @var TestCase
     */
    private $testCase;

    public function __construct(TestCase $testCase)
    {
        $this->testCase = $testCase;
    }

    /**
     * Creates a mock Step that would return the specified values
     *
     * @param bool $valid
     * @param bool $skipped
     * @param bool $accessible
     *
     * @return Step|\PHPUnit_Framework_MockObject_MockObject
     */
    public function createMockStep(bool $valid, bool $skipped, bool $accessible)
    {
        $step = $this->testCase->getMockBuilder(Step::class)
            ->disableOriginalConstructor()
            ->setMethods(['isValid', 'isSkipped', 'isAccessible', 'isDone'])
            ->getMock()
        ;

        $step->method('isValid')->willReturn($valid);
        $step->method('isSkipped')->willReturn($skipped);
        $step->method('isAccessible')->willReturn($accessible);
        // per definition only valid and skipped steps are done, so let's don't complicate the data providers
        $step->method('isDone')->willReturn($skipped || ($valid && $accessible));

        return $step;
    }
}