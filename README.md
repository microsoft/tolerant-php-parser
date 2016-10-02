# Tolerant PHP Parser
This is the _**start**_ of a PHP parser designed, from the beginning, for IDE usage scenarios.

## Design Goals
* Error tolerant design (in IDE scenarios, code is, by definition, incomplete)
* Performant (should be able to parse several MB of source code per second,
 to leave room for other features). 
  * Memory-efficient data structures
  * Allow for incremental parsing in the future
* Adheres to [PHP language spec](https://github.com/php/php-langspec),
supports both PHP5 and PHP7 grammars
* Generated AST provides properties necessary for semantic and transformational
operations, which also need to be performant 
([< 100 ms UI response time](https://www.computer.org/csdl/proceedings/afips/1968/5072/00/50720267.pdf),
so each language server operation should be < 50 ms to leave room for all the
 other stuff going on in parallel.)
* Simple and maintainable over time - parsers have a tendency to get *really*
 confusing, really fast, so readability and debug-ability is high priority.
* Written in PHP - make it as easy as possible for the PHP community to contribute

## Approach
This approach borrows heavily from the designs of Roslyn and TypeScript. However,
it will likely need to be adapted because PHP doesn't necessarily offer the 
same runtime characteristics as .NET and JS.

To ensure a sufficient level of correctness at every step of the way, the
parser should be developed using the following incremental approach:

* [ ] **Iteration 1:** Write lexer that does not support PHP grammar, but supports EOF 
and Unknown tokens. Write tests for all invariants.
* [ ] **Iteration 2:** Support PHP lexical grammar, lots of tests
* [ ] **Iteration 3:** Write a parser that does not support PHP grammar, but produces tree of 
Error Nodes. Write tests for all invariants.
* [ ] **Iteration 4:** Support PHP syntactic grammar, lots of tests
* [ ] **Iteration 5:** Real-world validation of correctness - benchmark against other parsers 
(investigate any instance of disagreement)
* [ ] **Iteration 6:** Real-world validation of performance - benchmark against large 
PHP applications
* [ ] **Iteration 7:** Performance optimization

This approach, however, makes a few assumptions that we should validate upfront, if possible,
in order to minimize potential risk:
* [ ] **Assumption 1:** This approach will work on a wide range of user development environment configurations.
* [ ] **Assumption 2:** PHP can be sufficiently optimized to support aforementioned parser performance goals.
* [ ] **Assumption 3:** PHP 7 grammar is a superset of PHP5 grammar.
* [ ] **Assumption 4:** The PHP grammar described in `php/php-langspec` is complete.
* Anything else?

## Lexer
The lexer produces tokens out PHP, based on the following lexical grammar:
https://github.com/php/php-langspec/blob/master/spec/19-grammar.md

### Tokens
Tokens take the following form:
```
Token: {
    Kind: Id, // the classification of the token
    FullStart: 0, // the start of the token, including trivia
    Start: 3, // the start of the token, excluding trivia
    Length: 6 // the length of the token from
}
```

### Helper functions
In order to be as efficient as possible, we do not store full content in memory.
Instead, each token is uniquely defined by four integers, and we take advantage of helper
functions to extract further information.
* `GetTriviaForToken`
* `GetFullTextForToken`
* `GetTextForToken`

### Invariants
In order to ensure that the parser evolves in a healthy manner over time, 
we define and continuously test the set of invariants defined below:
* The sum of the lengths of all of the tokens is equivalent to the length of the document
* The Start of every token is always greater than or equal to the FullStart of every token.
* A token's content exactly matches the range of the file its span specifies.
* `GetTriviaForToken` + `GetTextForToken` == `GetFullTextForToken`
* concatenating `GetFullTextForToken` for each token returns the document
* `GetTriviaForToken` returns a string of length equivalent to `(Start - FullStart)`
* `GetFullTextForToken` returns a string of length equivalent to `Length`
* `GetTextForToken` returns a string of length equivalent to `Length - (Start - FullStart)`

## Parser
### Invariants
* The tree contains every token
* span of any node is sum of spans of child nodes and tokens
* The tree length exactly matches the file length
* Every leaf node of the tree is a token


## Open Questions
Some open Qs:
  * need some examples of large PHP applications to help benchmark
  * would PHP 5 provide sufficient perf?
  * what sort of data structures do we need? Ideally we'd throw everything into a struct. Anything better?


## Real world validation strategy
* benchmark against other parsers (investigate any instance of disagreement)
* perf benchmarks (should be able to get semantic information )