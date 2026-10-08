<?php

namespace Tests\Unit;

use App\Services\QrTokenGenerator;
use PHPUnit\Framework\TestCase;

class QrTokenGeneratorTest extends TestCase
{
    public function test_tokens_are_seven_unambiguous_uppercase_characters(): void
    {
        $generator = new QrTokenGenerator;

        for ($i = 0; $i < 200; $i++) {
            $this->assertMatchesRegularExpression('/^[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{7}$/', $generator->random());
        }
    }
}
