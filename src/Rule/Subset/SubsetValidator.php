<?php

declare(strict_types=1);

namespace Yiisoft\Validator\Rule\Subset;

use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\ValidationContext;

final class SubsetValidator
{
    public static function getConfigClassName(): string
    {
        return Subset::class;
    }

    public function validate(mixed $value, object $config, ?ValidationContext $context = null): Result
    {
        $result = new Result();

        if (!is_iterable($value)) {
            $result->addError($config->iterableMessage);
            return $result;
        }

        if (!ArrayHelper::isSubset($value, $config->values, $config->strict)) {
            $values = is_array($config->values) ? $config->values : iterator_to_array($config->values);
            $valuesString = '"' . implode('", "', $values) . '"';

            $result->addError($config->subsetMessage, ['values' => $valuesString]);
        }

        return $result;
    }
}
