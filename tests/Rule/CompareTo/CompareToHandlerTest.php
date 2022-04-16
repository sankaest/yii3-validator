<?php

declare(strict_types=1);

namespace Yiisoft\Validator\Tests\Rule\CompareTo;

use Yiisoft\Validator\Error;
use Yiisoft\Validator\Rule\CompareTo\CompareTo;
use Yiisoft\Validator\Rule\CompareTo\CompareToHandler;
use Yiisoft\Validator\Rule\RuleHandlerInterface;
use Yiisoft\Validator\Tests\Rule\AbstractRuleValidatorTest;

final class CompareToHandlerTest extends AbstractRuleValidatorTest
{
    public function failedValidationProvider(): array
    {
        $value = 100;
        $messageEqual = 'Value must be equal to "{value}".';
        $messageNotEqual = 'Value must not be equal to "{value}".';
        $messageGreaterThan = 'Value must be greater than "{value}".';
        $messageGreaterOrEqualThan = 'Value must be greater than or equal to "{value}".';
        $messageLessThan = 'Value must be less than "{value}".';
        $messageLessOrEqualThan = 'Value must be less than or equal to "{value}".';

        return [
            [new CompareTo($value), 101, [new Error($messageEqual, ['value' => $value])]],

            [new CompareTo($value, operator: '==='), $value + 1, [new Error($messageEqual, ['value' => $value])]],

            [new CompareTo($value, operator: '!='), $value, [new Error($messageNotEqual, ['value' => $value])]],
            [new CompareTo($value, operator: '!='), (string)$value, [new Error($messageNotEqual, ['value' => $value])]],
            [new CompareTo($value, operator: '!='), (float)$value, [new Error($messageNotEqual, ['value' => $value])]],

            [new CompareTo($value, operator: '!=='), $value, [new Error($messageNotEqual, ['value' => $value])]],
            [new CompareTo($value, operator: '!=='), (string)$value, [new Error($messageNotEqual, ['value' => $value])]],
            [new CompareTo($value, operator: '!=='), (float)$value, [new Error($messageNotEqual, ['value' => $value])]],

            [new CompareTo($value, operator: '>'), $value, [new Error($messageGreaterThan, ['value' => $value])]],
            [new CompareTo($value, operator: '>'), $value - 1, [new Error($messageGreaterThan, ['value' => $value])]],

            [new CompareTo($value, operator: '>='), $value - 1, [new Error($messageGreaterOrEqualThan, ['value' => $value])]],

            [new CompareTo($value, operator: '<'), $value, [new Error($messageLessThan, ['value' => $value])]],
            [new CompareTo($value, operator: '<'), $value + 1, [new Error($messageLessThan, ['value' => $value])]],

            [new CompareTo($value, operator: '<='), $value + 1, [new Error($messageLessOrEqualThan, ['value' => $value])]],
        ];
    }

    public function passedValidationProvider(): array
    {
        $value = 100;

        return [
            [new CompareTo($value), $value],
            [new CompareTo($value), (string)$value],

            [new CompareTo($value, operator: '==='), $value],
            [new CompareTo($value, operator: '==='), (string)$value],
            [new CompareTo($value, operator: '==='), (float)$value],

            [new CompareTo($value, operator: '!='), $value + 0.00001],
            [new CompareTo($value, operator: '!='), false],

            [new CompareTo($value, operator: '!=='), false],

            [new CompareTo($value, operator: '>'), $value + 1],

            [new CompareTo($value, operator: '>='), $value],
            [new CompareTo($value, operator: '>='), $value + 1],
            [new CompareTo($value, operator: '<'), $value - 1],

            [new CompareTo($value, operator: '<='), $value],
            [new CompareTo($value, operator: '<='), $value - 1],
        ];
    }

    public function customErrorMessagesProvider(): array
    {
        return [
            [
                new CompareTo(100, message: 'Custom error'),
                101,
                [new Error('Custom error', ['value' => 100])],
            ],
        ];
    }

    protected function getValidator(): RuleHandlerInterface
    {
        return new CompareToHandler();
    }
}
