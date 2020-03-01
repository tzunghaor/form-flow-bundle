<?php
/**
 * Author: tzunghaor_at_primandras.hu
 * Created: 2018-09-01
 */

namespace Tzunghaor\FormFlowBundle\FormFlow;


use Tzunghaor\FormFlowBundle\Exception\NavigationException;

/**
 * This class finds out which step is the current step
 */
class FormFlowNavigator
{
    /** move forward */
    const DIRECTION_FORWARD = 1;
    /** move backward */
    const DIRECTION_BACKWARD = 2;

    /** Navigation successfully ended in the requested step */
    const NAV_SUCCESS = 1;
    /** Navigation resulted in finishing the form flow */
    const NAV_FINISHED = 2;
    /** Navigation ended on a different step than requested (step is skipped, or unable to enter) */
    const NAV_FALLBACK = 3;

    /** @var int current index in $this->steps */
    private $currentStepIndex;

    public function __construct(int $currentStepIndex)
    {
        $this->currentStepIndex = $currentStepIndex;
    }

    /**
     * @return int the current step index
     */
    public function getCurrentStepIndex(): int
    {
        return $this->currentStepIndex;
    }

    /**
     * @param array|Step[] $steps
     *
     * @return bool true if there is no steps after the current, or they are all skippable.
     */
    public function hasStepsLeft(array $steps): bool
    {
        for ($stepIndex = $this->currentStepIndex + 1; $stepIndex < count($steps); $stepIndex++) {
            if (!$steps[$stepIndex]->isSkipped()) {

                return true;
            }
        }

        return false;
    }

    /**
     * Moves from the current step to the next accessible step in the given direction
     *
     * @param int $direction
     * @param array|Step[] $steps
     *
     * @return int one of self::NAV_* constants
     */
    public function proceedInDirection(int $direction, array $steps): int
    {
        switch ($direction) {
            case self::DIRECTION_BACKWARD:
                $desiredStepIndex = $this->currentStepIndex - 1;
                break;

            case self::DIRECTION_FORWARD:
            default:
                $desiredStepIndex = $this->currentStepIndex + 1;
                break;
        }

        return $this->setCurrentStepIndex($desiredStepIndex, $steps, $direction);
    }

    /**
     * Sets $this->currentStepIndex to $stepIndex, or if it is not accessible, then to the closest step which is
     * accessible and not skipped.
     * Except: if $desiredStepIndex points beyond the last step and all steps are OK, then $this->currentStepIndex
     * won't point to a valid step after calling this method, and it returns
     *
     * @param int $desiredStepIndex The desired step index
     * @param array|Step[] $steps
     * @param int $direction one of self::DIRECTION_* constants: which direction is preferred if $stepIndex is not
     *   accessible
     *
     * @return int one of self::NAV_* constants
     *
     * @throws NavigationException When according to the steps config and the current state there is no accessible
     *  step.
     * @see self::NAV_FINISHED
     *
     */
    public function setCurrentStepIndex(int $desiredStepIndex, array $steps, int $direction = self::DIRECTION_FORWARD): int
    {
        // some trivial sanitizing
        if ($desiredStepIndex < 0) {
            $desiredStepIndex = 0;
        } elseif ($desiredStepIndex > count($steps)) {
            $desiredStepIndex = count($steps);
        }

        // Check whether $stepIndex is really accessible
        $lastAccessibleStepIndex = -1; // = index of last step before $stepIndex that can be entered
        for ($stepIndex = 0; $stepIndex <= $desiredStepIndex; $stepIndex++) {
            // after the last step, meaning flow is finished
            if ($stepIndex >= count($steps)) {
                break;
            }

            $step = $steps[$stepIndex];

            if ($step->isAccessible()) {
                $lastAccessibleStepIndex = $stepIndex;
            }

            if (!$step->isDone()) {

                break;
            }
        }

        // $desiredStepIndex >= count($steps) tries to finish flow: that is a special case, it is handled later
        if ($desiredStepIndex < count($steps) && ($lastAccessibleStepIndex >= 0)) {
            if ($lastAccessibleStepIndex === $desiredStepIndex) {
                $this->currentStepIndex = $lastAccessibleStepIndex;

                return self::NAV_SUCCESS;
            } elseif ($direction === self::DIRECTION_BACKWARD) {
                $this->currentStepIndex = $lastAccessibleStepIndex;

                return self::NAV_FALLBACK;
            }
        }

        return $this->forwardToStep($desiredStepIndex, $lastAccessibleStepIndex, $steps);
    }

    /**
     * Finds the first step starting from $lastAccessibleStepIndex + 1 that is accessible, and sets it as current.
     * Usable only when direction == self::DIRECTION_FORWARD and $lastAccessibleStepIndex < $desiredStepIndex.
     *
     * @param int $desiredStepIndex
     * @param int $lastAccessibleStepIndex fallback if no step is accessible step is found
     * @param array|Step[] $steps
     *
     * @return int one of self::NAV_* constants
     *
     * @throws NavigationException
     */
    protected function forwardToStep(int $desiredStepIndex, int $lastAccessibleStepIndex, array $steps): int
    {
        $stepIndexCandidate = $lastAccessibleStepIndex + 1;
        // find the closest accessible step
        do {
            if ($stepIndexCandidate >= count($steps)) {
                $this->currentStepIndex = $stepIndexCandidate;

                return self::NAV_FINISHED;
            }

            $stepCandidateIsAccessible = true;

            $step = $steps[$stepIndexCandidate];

            if ($step->isSkipped()) {
                $stepCandidateIsAccessible = false;
            } elseif (!$step->isAccessible()) {
                return $this->enterLastAccessibleStep($lastAccessibleStepIndex);
            }

            if (!$stepCandidateIsAccessible) {
                $stepIndexCandidate ++;
            }
        } while (!$stepCandidateIsAccessible);

        $this->currentStepIndex = $stepIndexCandidate;

        return $stepIndexCandidate === $desiredStepIndex ? self::NAV_SUCCESS : self::NAV_FALLBACK;
    }

    /**
     * Used in navigation when the desired step cannot be entered
     *
     * @param int $lastAccessibleStepIndex the last accessible step index, negative if there is none
     *
     * @return int one of self::NAV_* constants
     *
     * @throws NavigationException
     */
    private function enterLastAccessibleStep(int $lastAccessibleStepIndex): int
    {
        if ($lastAccessibleStepIndex < 0) {

            throw new NavigationException('There is no accessible step.');
        }

        $this->currentStepIndex = $lastAccessibleStepIndex;

        return self::NAV_FALLBACK;
    }
}