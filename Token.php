<?php

namespace PhpParser;
/**
 * 
 */
class Token
{
    // TODO optimize memory - ideally this would be a struct of 4 ints
    public $kind;
    public $fullStart;
    public $start;
    public $length;

    public function __construct($kind, $fullStart, $start, $length)
    {
        $this->kind = $kind;
        $this->fullStart = $fullStart;
        $this->start = $start;
        $this->length = $length;
    }

    public function getTriviaForToken(string $document) : string {
        return substr($document, $this->fullStart, $this->start - $this->fullStart);
    }

    public function getTextForToken(string $document) : string {
        return substr($document, $this->start, $this->length - ($this->start - $this->fullStart));
    }

    public function getFullTextForToken(string $document) : string {
        return substr($document, $this->fullStart, $this->length);
    }
}

// TODO enum equivalent?
class TokenKind
{
    const Unknown = 0;
    const EndOfFileToken = 1;
    const SingleLineComment = 2;
    const Newline = 3;
    const DelimitedComment = 4;
    const CompoundDivideAssignment = 6;
    const DivideOperator = 7;
    const Whitespace = 8;
}

