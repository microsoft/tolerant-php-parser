<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class Function_ extends Node {
    /** @var Token */
    public $functionKeyword;
    /** @var Token */
    public $byRefToken;
    /** @var null | Name */
    public $name;
    /** @var Token */
    public $openParen;
    /** @var Parameter[] */
    public $parameters;
    /** @var Token */
    public $closeParen;
    /** @var null | Name */
    public $returnTypeOpt;

    public function __construct() {
        parent::__construct(NodeKind::FunctionNode);
    }
}