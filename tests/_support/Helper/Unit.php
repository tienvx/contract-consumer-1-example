<?php

namespace App\Tests\Helper;

use PhpPact\Consumer\InteractionBuilder;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Unit extends \Codeception\Module
{
    public function verifyPacts(InteractionBuilder $mockService)
    {
        $hasException = false;
        try {
            $mockService->verify();
        } catch(\Exception $e) {
            $hasException = true;
        }

        $this->assertFalse($hasException, 'We expect the pacts to validate');
    }
}
