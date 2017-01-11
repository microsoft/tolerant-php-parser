<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

spl_autoload_register(function ($class) {
    if (\file_exists($filepath = __DIR__ . "/" . \substr($class, 10) . ".php")) {
        require_once $filepath;
    }
});