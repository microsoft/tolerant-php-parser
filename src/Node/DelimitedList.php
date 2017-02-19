<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser\Node;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Token;

abstract class DelimitedList extends Node {
    /** @var Token[]|Node[] */
    public $children;

    public function getValues() {
        $i = 0;
        foreach ($this->children as $value) {
            if ($i++ % 2 == 1) {
                yield $value;
            }
        }
    }

    public function addElement($node) {
        if ($node === null) {
            return;
        }
        if ($this->children === null) {
            $this->children = [];
        }
        $this->children[] = $node;
    }
}
