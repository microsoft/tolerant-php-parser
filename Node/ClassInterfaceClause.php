<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class ClassInterfaceClause extends Node {
    /** @var Token */
    public $implementsKeyword;

    /** @var DelimitedList | null */
    public $interfaceNameList;

    public function __construct() {
        parent::__construct(NodeKind::ClassInterfaceClause);
    }
}