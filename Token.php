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
    const Error = 0;
}

