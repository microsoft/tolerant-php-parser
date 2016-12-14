<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

trait FunctionHeader {
    /** @var Token */
    public $functionKeyword;
    /** @var Token */
    public $byRefToken;
    /** @var null | Name */
    public $name;
    /** @var Token */
    public $openParen;
    /** @var DelimitedList[] */
    public $parameters;
    /** @var Token */
    public $closeParen;
}