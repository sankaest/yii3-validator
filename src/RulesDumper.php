<?php

declare(strict_types=1);

namespace Yiisoft\Validator;

use InvalidArgumentException;
use function is_array;

/**
 * RulesDumper allows to get an array of rule names and corresponding settings from a set of rules.
 * The array is usually passed to the client to use it in client-side validation.
 *
 * * @see RuleInterface
 */
final class RulesDumper
{
    /**
     * Return all attribute rules as array.
     *
     * For example:
     *
     * ```php
     * [
     *    'amount' => [
     *        [
     *            'number',
     *            'integer' => true,
     *            'max' => 100,
     *            'notANumberMessage' => 'Value must be an integer.',
     *            'tooBigMessage' => 'Value must be no greater than 100.'
     *        ],
     *        ['callback'],
     *    ],
     *    'name' => [
     *        [
     *            'hasLength',
     *            'max' => 20,
     *            'message' => 'Value must contain at most 20 characters.'
     *        ],
     *    ],
     * ]
     * ```
     *
     * @param iterable $ruleSetMap
     *
     * @return array
     */
    public function asArray(iterable $ruleSetMap, bool $dumpRuleName): array
    {
        return $this->fetchOptions($ruleSetMap, $dumpRuleName);
    }

    private function fetchOptions(iterable $rules, bool $dumpRuleName): array
    {
        $result = [];
        foreach ($rules as $attribute => $rule) {
            if (is_array($rule)) {
                $result[$attribute] = $this->fetchOptions($rule, $dumpRuleName);
            } elseif ($rule instanceof ParametrizedRuleInterface) {
                if ($dumpRuleName) {
                    $result[$attribute] = array_merge([$rule->getName()], $rule->getOptions());
                } else {
                    $result[$attribute] = $rule->getOptions();
                }
            } else {
                // or ?
                // $result[$attribute] = [get_class($rule)];

                throw new InvalidArgumentException(sprintf(
                    'Rules should be an array of rules that implements %s.',
                    RuleInterface::class,
                ));
            }
        }

        return $result;
    }
}
