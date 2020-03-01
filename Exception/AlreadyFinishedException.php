<?php
/**
 * Author: tzunghaor_at_primandras.hu
 * Created: 2017-11-01
 */

namespace Tzunghaor\FormFlowBundle\Exception;

/**
 * Thrown when trying to finish/submit data to an already finished flow instance.
 */
class AlreadyFinishedException extends FormFlowException
{

}