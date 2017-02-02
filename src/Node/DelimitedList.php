<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser\Node;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Token;

class DelimitedList extends Node {
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

    public function addToken($node) {
        if ($node === null) {
            return;
        }
        if (!isset($this->children)) {
            $this->children = array();
        }
        $this->children[] = $node;
    }
}
