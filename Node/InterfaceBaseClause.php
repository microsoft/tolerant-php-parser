<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class InterfaceBaseClause extends Node {
    /** @var Token */
    public $extendsKeyword;

    /** @var DelimitedList | null */
    public $interfaceNameList;

    public function __construct() {
        parent::__construct(NodeKind::InterfaceBaseClause);
    }
}