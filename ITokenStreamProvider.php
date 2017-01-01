<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser;

interface ITokenStreamProvider {
    function scanNextToken() : Token;

    function getCurrentPosition() : int;

    function setCurrentPosition(int $pos);

    function getEndOfFilePosition() : int;
}