<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

spl_autoload_register(function ($class) {
    require_once str_replace("\\", "/", __DIR__ . "/" . \substr($class, 10) . ".php");
});