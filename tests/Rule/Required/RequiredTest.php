<?php

declare(strict_types=1);

namespace Yiisoft\Validator\Tests\Rule\Required;

use Yiisoft\Validator\Rule\Required\Required;
use Yiisoft\Validator\RuleInterface;
use Yiisoft\Validator\Tests\Rule\AbstractRuleTest;

/**
 * @group t2
 */
final class RequiredTest extends AbstractRuleTest
{
    public function optionsDataProvider(): array
    {
        return [
            [
                new Required(),
                [
                    'message' => [
                        'message' => 'Value cannot be blank.',
                    ],
                    'skipOnEmpty' => false,
                    'skipOnError' => false,
                ],
            ],
        ];
    }

    protected function getRule(): RuleInterface
    {
        return new Required();
    }
}
