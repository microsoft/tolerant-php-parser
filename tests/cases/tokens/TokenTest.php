<?php

use Microsoft\PhpParser\Token;
use Microsoft\PhpParser\TokenKind;
use Microsoft\PhpParser\TokenStringMaps;
use PHPUnit\Framework\TestCase;

class TokenTest extends TestCase
{

    private function doMatch(array $expected, callable $isAMatch)
    {
        $expr = [];
        foreach ($this->getConstants() as $kind) {
            if ($isAMatch($kind)) {
                $expr[] = $kind;
            }
        }
        sort($expected);
        sort($expr);
        $this->assertEquals($expected, $expr);
    }

    public function testIsReserved()
    {
        $reserved = array_values(TokenStringMaps::RESERVED_WORDS);
        $this->doMatch($reserved, function(int $kind){
            return TokenKind::isReserved($kind);
        });
    }

    public function testIsKeyword()
    {
        $keywords = array_values(TokenStringMaps::KEYWORDS);

        $this->doMatch($keywords, function(int $kind){
            return TokenKind::isKeyword($kind);
        });
    }

    public function testIsNameOrKeywordOrReserved()
    {
        $tokenKinds = array_merge([TokenKind::Name], TokenStringMaps::KEYWORDS, TokenStringMaps::RESERVED_WORDS);
        $this->doMatch(array_values($tokenKinds), function(int $kind){
            return TokenKind::isNameOrKeywordOrReserved($kind);
        });
    }

    public function testIsNameOrStaticOrReservedWord()
    {
        $tokenKinds = array_merge([TokenKind::Name, TokenKind::StaticKeyword], TokenStringMaps::RESERVED_WORDS);

        foreach ($tokenKinds as $kind) {
            $this->assertTrue(TokenKind::StaticKeyword === $kind || TokenKind::Name === $kind || TokenKind::isReserved($kind),
                    "{$this->getTokenName($kind)} should be a name, static or a reserved word.");
        }
    }

    public function testIsParameterType()
    {
        $tokenKinds = [
            TokenKind::ArrayKeyword,
            TokenKind::CallableKeyword,
            TokenKind::BoolReservedWord,
            TokenKind::FloatReservedWord,
            TokenKind::IntReservedWord,
            TokenKind::StringReservedWord,
            TokenKind::ObjectReservedWord
        ];

        foreach ($tokenKinds as $kind) {
            $this->assertTrue(TokenKind::isParameterTypeDeclaration($kind),
                    "{$this->getTokenName($kind)} should be a parameter type.");
        }
    }

    public function testIsReturnTypeDeclaration()
    {
         $tokenKinds = [
            TokenKind::ArrayKeyword,
            TokenKind::CallableKeyword,
            TokenKind::BoolReservedWord,
            TokenKind::FloatReservedWord,
            TokenKind::IntReservedWord,
            TokenKind::StringReservedWord,
            TokenKind::ObjectReservedWord,
            TokenKind::VoidReservedWord,
        ];

        foreach ($tokenKinds as $kind) {
            $this->assertTrue(TokenKind::VoidReservedWord === $kind || TokenKind::isParameterTypeDeclaration($kind),
                    "{$this->getTokenName($kind)} should be a return type");
        }
    }

    private function getTokenName(int $kind): string
    {
        return Token::getTokenKindNameFromValue($kind) . " ($kind)";
    }

    private function getConstants(): array
    {
        return (new ReflectionClass(TokenKind::class))->getConstants();
    }
}