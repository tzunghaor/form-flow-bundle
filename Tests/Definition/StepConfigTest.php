<?php
/**
 * Author: tzunghaor_at_primandras.hu
 * Created: 2017-11-05
 */

namespace Tzunghaor\FormFlowBundle\Tests\Definition;


use PHPUnit\Framework\TestCase;
use Tzunghaor\FormFlowBundle\Definition\StepConfig;

class StepConfigTest extends TestCase
{
    public function testLabel()
    {
        $stepConfig = new StepConfig();
        $stepConfig->setName('test name');

        // step name is the fallback label
        $this->assertEquals('test name', $stepConfig->getLabel());

        $stepConfig->setName('test name 2');

        $this->assertEquals('test name 2', $stepConfig->getLabel());

        $stepConfig->setLabel('test label');

        $this->assertEquals('test label', $stepConfig->getLabel());
    }
}