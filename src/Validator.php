<?php

declare(strict_types=1);

namespace Yiisoft\Validator;

use InvalidArgumentException;
use JetBrains\PhpStorm\Pure;
use Yiisoft\Validator\DataSet\ArrayDataSet;
use Yiisoft\Validator\DataSet\ScalarDataSet;
use Yiisoft\Validator\Rule\Callback\Callback;
use function is_array;
use function is_object;

/**
 * Validator validates {@link DataSetInterface} against rules set for data set attributes.
 */
final class Validator implements ValidatorInterface
{
    public const PARAMETER_PREVIOUS_RULES_ERRORED = 'previousRulesErrored';

    private RuleValidatorStorage $storage;

    public function __construct()
    {
        $this->storage = new RuleValidatorStorage();
    }

    /**
     * @param DataSetInterface|mixed|RulesProviderInterface $data
     * @param RuleInterface[][] $rules
     * @psalm-param iterable<string, RuleInterface[]> $rules
     */
    public function validate($data, iterable $rules = []): Result
    {
        $data = $this->normalizeDataSet($data);
        if ($data instanceof RulesProviderInterface) {
            $rules = $data->getRules();
        }

        $context = new ValidationContext($data);
        $result = new Result();

        foreach ($rules as $attribute => $attributeRules) {
            $tempRule = is_array($attributeRules) ? $attributeRules : [$attributeRules];
            $attributeRules = $this->normalizeRules($tempRule);

            if (is_int($attribute)) {
                $validatedData = $data->getData();
                $validatedContext = $context->withAttribute((string)$attribute);
            } else {
                $validatedData = $data->getAttributeValue($attribute);
                $validatedContext = $context;
            }

            $tempResult = $this->validateInternal(
                $validatedData,
                $attributeRules,
                $validatedContext
            );

            foreach ($tempResult->getErrors() as $error) {
                $result->addError($error->getMessage(), $error->getParameters());
//                $result->addError($error->getMessage(), [$attribute, ...$error->getValuePath()]);
            }
        }

        if ($data instanceof PostValidationHookInterface) {
            $data->processValidationResult($result);
        }

        return $result;
    }

    #[Pure]
    private function normalizeDataSet($data): DataSetInterface
    {
        if ($data instanceof DataSetInterface) {
            return $data;
        }

        if (is_object($data) || is_array($data)) {
            return new ArrayDataSet((array)$data);
        }

        return new ScalarDataSet($data);
    }

    public function validateInternal($value, iterable $rules, ValidationContext $context): Result
    {
        $compoundResult = new Result();
        foreach ($rules as $rule) {
            $ruleValidator = $this->storage->getValidator(get_class($rule));
            $ruleResult = $ruleValidator->validate($value, $rule, $this, $context);
            if ($ruleResult->isValid()) {
                continue;
            }

            $context->setParameter(self::PARAMETER_PREVIOUS_RULES_ERRORED, true);

            foreach ($ruleResult->getErrors() as $error) {
                $compoundResult->addError($error->getMessage(), $error->getParameters());
            }
        }
        return $compoundResult;
    }

    private function normalizeRules(array $rules): iterable
    {
        foreach ($rules as $rule) {
            yield $this->normalizeRule($rule);
        }
    }

    private function normalizeRule($rule): RuleInterface
    {
        if (is_callable($rule)) {
            return new Callback($rule);
        }

        if (!$rule instanceof RuleInterface) {
            throw new InvalidArgumentException(sprintf(
                'Rule should be either an instance of %s or a callable, %s given.',
                RuleInterface::class,
                gettype($rule)
            ));
        }

        return $rule;
    }
}
