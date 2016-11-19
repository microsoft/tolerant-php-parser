<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class ClassBaseClause extends Node {
    /** @var Token */
    public $extendsKeyword;

    /** @var Name */
    public $baseClass;

    public function __construct() {
        parent::__construct(NodeKind::ClassBaseClause);
    }
}