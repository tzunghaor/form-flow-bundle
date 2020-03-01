<?php
/**
 * Author: tzunghaor_at_primandras.hu
 * Created: 2017-10-29
 */

namespace Tzunghaor\FormFlowBundle\FormFlow;

use Tzunghaor\FormFlowBundle\Definition\StepConfig;

/**
 * Information about a form flow step: config, state
 */
class Step
{
    /** The initial state, we don't know anything yet about this step */
    const STATE_INIT = 'init';
    /** The step is accessible, but has no valid data yet */
    const STATE_ACCESSIBLE = 'accessible';
    /** The step is not accessible, has no valid data yet, cannot be skipped */
    const STATE_BLOCKED = 'blocked';
    /** The step is submitted and valid */
    const STATE_VALID = 'valid';
    /** The step is not accessible, but can be skipped */
    const STATE_SKIP = 'skip';
    /** The step is not accessible, but can be skipped, and has valid data */
    const STATE_SKIP_VALID = 'skip_valid';
    /** The step is not accessible, has valid data, cannot be skipped */
    const STATE_BLOCKED_VALID = 'blocked_valid';

    /** @var StepConfig */
    private $config;

    /** @var string */
    private $name = '';

    /** @var string one of STATE_* constants */
    private $state = self::STATE_INIT;

    /** @var array Optional array of errors displayable to the user. */
    private $userErrors = [];

    public function __construct(string $stepName, StepConfig $config, string $state = null)
    {
        $this->name = $stepName;
        $this->setConfig($config);
        $this->setState($state);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string|null $state
     * @return Step
     */
    private function setState(string $state = null): Step
    {
        $this->state = $state ?? self::STATE_INIT;

        return $this;
    }

    /**
     * @return bool whether the step has valid submitted data
     */
    public function isValid(): bool
    {
        return in_array($this->getState(), [self::STATE_VALID, self::STATE_SKIP_VALID, self::STATE_BLOCKED_VALID], true);
    }

    /**
     * @param bool $valid whether the step has valid submitted data
     *
     * @return string
     */
    public function getValidityChangedState(bool $valid): string
    {
        if ($this->isSkipped()) {
            return $valid ? self::STATE_SKIP_VALID : self::STATE_SKIP;
        } elseif ($this->isAccessible()) {
            return $valid ? self::STATE_VALID : self::STATE_ACCESSIBLE;
        } else {
            return $valid ? self::STATE_BLOCKED_VALID : self::STATE_BLOCKED;
        }
    }

    /**
     * @return bool whether the step can be skipped
     */
    public function isSkipped(): bool
    {
        return in_array($this->getState(), [self::STATE_SKIP, self::STATE_SKIP_VALID], true);
    }

    /**
     * @return bool whether the step can be accessed
     */
    public function isAccessible(): bool
    {
        return in_array($this->getState(), [self::STATE_ACCESSIBLE, self::STATE_VALID], true);
    }

    /**
     * @return bool whether we can go past this step
     */
    public function isDone(): bool
    {
        return in_array($this->getState(), [self::STATE_VALID, self::STATE_SKIP, self::STATE_SKIP_VALID], true);
    }

    /**
     * @param bool   $canReach true if user can get to this step
     * @param object $data form data
     *
     * @return string new state
     */
    public function getUpdatedState(bool $canReach, $data): string
    {
        if ($this->shouldSkip($data)) {
            return $this->isValid() ? self::STATE_SKIP_VALID : self::STATE_SKIP;
        }

        if ($canReach && $this->canEnter($data)) {
            return $this->isValid() ? self::STATE_VALID : self::STATE_ACCESSIBLE;
        }

        return $this->isValid() ? self::STATE_BLOCKED_VALID : self::STATE_BLOCKED;
    }

    /**
     * @param bool $canReach true if user can get to this step
     *
     * @return bool true if user can go past this step
     */
    public function canGoPast(bool $canReach): bool
    {
        return $canReach && ($this->isSkipped() ||  $this->getState() === self::STATE_VALID);
    }

    /**
     * @param StepConfig $config
     * @return Step
     */
    private function setConfig(StepConfig $config): Step
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->config->getLabel();
    }

    /**
     * @return string
     */
    public function getFormClass(): string
    {
        return $this->config->getFormClass();
    }

    /**
     * @return string
     */
    public function getView(): string
    {
        return $this->config->getView();
    }

    /**
     * @param mixed $data form flow data
     *
     * @return array
     */
    public function getViewVariables($data): array
    {
        $staticVariables = $this->config->getViewVariables();

        $viewVariablesCallable = $this->config->getViewVariablesCallable();

        if (is_callable($viewVariablesCallable)) {

            return array_merge($staticVariables, $viewVariablesCallable($data));
        }

        return $staticVariables;
    }

    /**
     * @param mixed $data form flow data
     *
     * @return array
     */
    public function getFormOptions($data): array
    {
        $staticOptions = $this->config->getFormOptions();
        $formOptionsCallable = $this->config->getFormOptionsCallable();

        if (is_callable($formOptionsCallable)) {

            return array_merge($staticOptions, $formOptionsCallable($data));
        }

        return $staticOptions;
    }

    /**
     * @return array Optional array of errors displayable to the user.
     * Usually filled after calling getUpdatedState()
     */
    public function getUserErrors(): array
    {
        return $this->userErrors;
    }

    /**
     * @param mixed $data form flow data
     *
     * @return bool whether the step can be skipped based on the config
     */
    private function shouldSkip($data): bool
    {
        $skipCallable = $this->config->getSkipConditionCallable();

        if (is_callable($skipCallable) && true === $skipCallable($data)) {

            return true;
        }

        return false;
    }

    /**
     * @param mixed $data form flow data
     *
     * @return bool whether the step can be entered based on the enter condition
     */
    private function canEnter($data): bool
    {
        $enterCallable = $this->config->getEnterErrorsCallable();

        if (!is_callable($enterCallable)) {

            return true;
        }

        $this->userErrors = $enterCallable($data);

        return count($this->userErrors) === 0;
    }
}
