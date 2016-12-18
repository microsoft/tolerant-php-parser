<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;


use PhpParser\NodeKind;

class Expression extends Node {
    public function __construct(int $kind) {
        parent::__construct($kind);
    }

}