<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\AssertionFailedError;

if (PHP_VERSION_ID >= 70100) {
    // PHPUnit 7 requires a return type of void, which is impossible in php 7.0
    class CallbackTestListener implements TestListener {
        private $cb;
        public function __construct(Closure $cb) {
            $this->cb = $cb;
        }
        use TestListenerDefaultImplementation;
        // php 7.1 does not support param type widening.
        function addFailure(Test $test, AssertionFailedError $e, float $time): void {
            ($this->cb)($test);
        }
    }
} else {
    class CallbackTestListener implements TestListener {
        private $cb;
        public function __construct(Closure $cb) {
            $this->cb = $cb;
        }
        use TestListenerDefaultImplementation;
        function addFailure(Test $test, AssertionFailedError $e, $time) {
            ($this->cb)($test);
        }
    }
}
