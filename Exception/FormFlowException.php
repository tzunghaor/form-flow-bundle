<?php
/**
 * Author: tzunghaor_at_primandras.hu
 * Created: 2017-11-05
 */

namespace Tzunghaor\FormFlowBundle\Exception;

use Tzunghaor\FormFlowBundle\FormFlow\FormFlow;

/**
 * Thrown on form flow specific errors.
 *
 * Usually not this exception is thrown, but one of its subclasses.
 */
class FormFlowException extends \Exception
{
    /** @var FormFlow */
    private $flow;

    public function __construct(FormFlow $flow, $message="", $code=0, \Exception $previous=null)
    {
        $this->flow = $flow;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return FormFlow
     */
    public function getFlow(): FormFlow
    {
        return $this->flow;
    }

}