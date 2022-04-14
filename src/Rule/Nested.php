<?php

declare(strict_types=1);

namespace Yiisoft\Validator\Rule;

use Attribute;
use InvalidArgumentException;
use Traversable;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Validator\FormatterInterface;
use Yiisoft\Validator\ParametrizedRuleInterface;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule;
use Yiisoft\Validator\RuleInterface;
use Yiisoft\Validator\RuleSet;
use Yiisoft\Validator\ValidationContext;
use function is_array;
use function is_object;

/**
 * Can be used for validation of nested structures.
 *
 * For example, we have an inbound request with the following structure:
 *
 * ```php
 * $request = [
 *     'author' => [
 *         'name' => 'Dmitry',
 *         'age' => 18,
 *     ],
 * ];
 * ```
 *
 * So to make validation we can configure it like this:
 *
 * ```php
 * $rule = new Nested([
 *     'author' => new Nested([
 *         'name' => [new HasLength(min: 3)],
 *         'age' => [new Number(min: 18)],
 *     )];
 * ]);
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Nested extends Rule
{
    public function __construct(
        /**
         * @var Rule[][]
         */
        private iterable $rules = [],
        private bool $throwErrorWhenPropertyPathIsNotFound = false,
        private string $propertyPathIsNotFoundMessage = 'Property path "{path}" is not found.',
        ?FormatterInterface $formatter = null,
        bool $skipOnEmpty = false,
        bool $skipOnError = false,
        $when = null
    ) {
        parent::__construct(formatter: $formatter, skipOnEmpty: $skipOnEmpty, skipOnError: $skipOnError, when: $when);
        $this->initRules($rules);
    }

    private function initRules(iterable $rules): void
    {
        $rules = $rules instanceof Traversable ? iterator_to_array($rules) : $rules;
        if (empty($rules)) {
            throw new InvalidArgumentException('Rules must not be empty.');
        }

        if ($this->checkRules($rules)) {
            $message = sprintf('Each rule should be an instance of %s.', RuleInterface::class);
            throw new InvalidArgumentException($message);
        }

        $this->rules = $rules;
    }

    /**
     * @see $rules
     */
    public function rules(iterable $value): self
    {
        $new = clone $this;
        $new->initRules($value);

        return $new;
    }

    /**
     * @see $message
     */
    public function message(string $value): self
    {
        $new = clone $this;
        $new->message = $value;

        return $new;
    }

    /**
     * @see $throwErrorWhenPropertyPathIsNotFound
     */
    public function throwErrorWhenPropertyPathIsNotFound(bool $value): self
    {
        $new = clone $this;
        $new->throwErrorWhenPropertyPathIsNotFound = $value;

        return $new;
    }

    protected function validateValue($value, ?ValidationContext $context = null): Result
    {
        $result = new Result();
        if (!is_object($value) && !is_array($value)) {
            $message = sprintf('Value should be an array or an object. %s given.', gettype($value));
            $result->addError($message);

            return $result;
        }

        $value = (array) $value;

        foreach ($this->rules as $valuePath => $rules) {
            if ($this->throwErrorWhenPropertyPathIsNotFound && !ArrayHelper::pathExists($value, $valuePath)) {
                $message = $this->formatMessage($this->propertyPathIsNotFoundMessage, ['path' => $valuePath]);
                $result->addError($message);

                continue;
            }

            $rules = is_array($rules) ? $rules : [$rules];
            $ruleSet = new RuleSet($rules);
            $validatedValue = ArrayHelper::getValueByPath($value, $valuePath);
            $itemResult = $ruleSet->validate($validatedValue);
            if ($itemResult->isValid()) {
                continue;
            }

            foreach ($itemResult->getErrors() as $error) {
                $errorValuePath = is_int($valuePath) ? [$valuePath] : explode('.', $valuePath);
                if ($error->getValuePath()) {
                    $errorValuePath = array_merge($errorValuePath, $error->getValuePath());
                }

                $result->addError($error->getMessage(), $errorValuePath);
            }
        }

        return $result;
    }

    private function checkRules(array $rules): bool
    {
        return array_reduce(
            $rules,
            function (bool $carry, $rule) {
                return $carry || (is_array($rule) ? $this->checkRules($rule) : !$rule instanceof RuleInterface);
            },
            false
        );
    }

    public function getOptions(): array
    {
        return $this->fetchOptions($this->rules);
    }

    private function fetchOptions(iterable $rules): array
    {
        $result = [];
        foreach ($rules as $attribute => $rule) {
            if (is_array($rule)) {
                $result[$attribute] = $this->fetchOptions($rule);
            } elseif ($rule instanceof ParametrizedRuleInterface) {
                $result[$attribute] = $rule->getOptions();
            } elseif ($rule instanceof RuleInterface) {
                // Just skip the rule that doesn't support parametrizing
                continue;
            } else {
                throw new InvalidArgumentException(sprintf(
                    'Rules should be an array of rules that implements %s.',
                    ParametrizedRuleInterface::class,
                ));
            }
        }

        return $result;
    }
}
