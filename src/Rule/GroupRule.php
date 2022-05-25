<?php

declare(strict_types=1);

namespace Yiisoft\Validator\Rule;

use Closure;
use Yiisoft\Validator\Rule\Trait\RuleNameTrait;
use Yiisoft\Validator\Rule\Trait\HandlerClassNameTrait;
use Yiisoft\Validator\ParametrizedRuleInterface;
use Yiisoft\Validator\RulesDumper;

/**
 * Validates a single value for a set of custom rules.
 */
abstract class GroupRule implements ParametrizedRuleInterface
{
    use HandlerClassNameTrait;
    use RuleNameTrait;

    public function __construct(
        public string $message = 'This value is not a valid.',
        public bool $skipOnEmpty = false,
        public bool $skipOnError = false,
        public ?Closure $when = null,
    ) {
    }

    /**
     * Return custom rules set
     */
    abstract public function getRuleSet(): array;

    public function getOptions(): array
    {
        return (new RulesDumper())->asArray($this->getRuleSet());
    }
}
