# Tolerant PHP Parser
This is an early prototype of a PHP parser designed, from the beginning, for IDE usage scenarios. There is
still a ton of work to be done, so at this point, this repo mostly serves as 
an experiment and the start of a conversation.

![image](https://cloud.githubusercontent.com/assets/762848/19023070/4ab01c92-889a-11e6-9bb5-ec1a6816aba2.png)

## Ready, set, parse!
* **[Get started](GettingStarted.md)** - learn how to reference the parser from your project, and how to perform
operations on the AST to answer questions about your code.
* **[Syntax Visualizer Tool](playground/client/README.md)** - get a feel for the AST in a more tangible way. 
* **[Current Status](#current-status)** - how much of the grammar is supported? Performance? Memory? API stability?
* **[How it works](#design-goals)** - learn about the architecture and design decisions.
  * [Design Goals](#design-goals)
  * [Approach](#approach)
  * [Syntax Tree Representation](#lexer)
  * [Error Tolerance Strategy](#error-tokens)
  * [Validation Strategy](#real-world-validation-strategy)
* **[Contribute!](#contribute)** - learn how to get involved, check out some pointers to educational commits that'll
help you ramp up on the codebase (even if you've never worked on a parser before), 
and recommended workflows that make it easier to iterate.

## Design Goals
* Error tolerant design (in IDE scenarios, code is, by definition, incomplete)
* Performant (should be able to parse several MB of source code per second,
 to leave room for other features). 
  * Memory-efficient data structures
  * Allow for incremental parsing in the future
* Adheres to [PHP language spec](https://github.com/php/php-langspec),
supports both PHP5 and PHP7 grammars
* Generated AST provides properties (fully representative, etc.) necessary for semantic and transformational
operations, which also need to be performant.
([< 100 ms UI response time](https://www.computer.org/csdl/proceedings/afips/1968/5072/00/50720267.pdf),
so each language server operation should be < 50 ms to leave room for all the
 other stuff going on in parallel.)
* Simple and maintainable over time - parsers have a tendency to get *really*
 confusing, really fast, so readability and debug-ability is high priority.
* Written in PHP - make it as easy as possible for the PHP community to consume and contribute.

## Approach
This approach borrows heavily from the designs of Roslyn and TypeScript. However,
it will likely need to be adapted because PHP doesn't necessarily offer the 
same runtime characteristics as .NET and JS.

To ensure a sufficient level of correctness at every step of the way, the
parser should be developed using the following incremental approach:

* [ ] **Phase 1:** Write lexer that does not support PHP grammar, but supports EOF 
and Unknown tokens. Write tests for all invariants.
* [ ] **Phase 2:** Support PHP lexical grammar, lots of tests
* [ ] **Phase 3:** Write a parser that does not support PHP grammar, but produces tree of 
Error Nodes. Write tests for all invariants.
* [ ] **Phase 4:** Support PHP syntactic grammar, lots of tests
* [ ] **Phase 5:** Real-world validation of correctness - benchmark against other parsers 
(investigate any instance of disagreement)
* [ ] **Phase 6:** Real-world validation of performance - benchmark against large 
PHP applications
* [ ] **Phase 7:** Performance optimization

This approach, however, makes a few assumptions that we should validate upfront, if possible,
in order to minimize potential risk:
* [ ] **Assumption 1:** This approach will work on a wide range of user development environment configurations.
* [ ] **Assumption 2:** PHP can be sufficiently optimized to support aforementioned parser performance goals.
* [ ] **Assumption 3:** PHP 7 grammar is a superset of PHP5 grammar.
* [ ] **Assumption 4:** The PHP grammar described in `php/php-langspec` is complete.
* Anything else?

## Lexer
The lexer produces tokens out PHP, based on the following lexical grammar:
* https://github.com/php/php-langspec/blob/master/spec/19-grammar.md
* http://php.net/manual/en/tokens.php

### Tokens (Model)
Tokens take the following form:
```
Token: {
    Kind: Id, // the classification of the token
    FullStart: 0, // the start of the token, including trivia
    Start: 3, // the start of the token, excluding trivia
    Length: 6 // the length of the token (from FullStart)
}
```

### Tokens (Representation)
> TODO

#### Helper functions
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
* See the code for an up-to-date list...

## Parser
### Node (Model)
Nodes include the following information:
```
Node: {
  Kind: Id,
  Parent: ParentNode,
  Children: List<Children>
}
```

### Node (Representation)
> TODO - discerning between Model and Representation
(Model == How we will intaract with it, Representation == underlying data structures)

### Abstract Syntax Tree
An example tree is below. The tree Nodes (represented by tokens), and Tokens (represented by squares)
![image](https://cloud.githubusercontent.com/assets/762848/19092929/e10e60aa-8a3d-11e6-8b90-51eabe5d1d8e.png)

Below, we define a set of invariants. This set of invariants provides a consistent foundation that makes it
easier to confidently reason about the tree as we continue to build up our understanding.

For instance, the following properties hold true about every Node (N) and Token (T).
```
POS(N) -> POS(FirstChild(N))
POS(T) -> T.Start
WIDTH(N) -> SUM(Child_i(N))
WIDTH(T) -> T.Width
```


### Invariants
* Invariants for all Tokens hold true 
* The tree contains every token
* span of any node is sum of spans of child nodes and tokens
* The tree length exactly matches the file length
* Every leaf node of the tree is a token
* Every Node contains at least one Token

### Building up the Tree

#### Error Tokens
We define two types of `Error` tokens:
* **Skipped Tokens:** extra token that no one knows how to deal with
* **Missing Tokens:** Grammar expects a token to be there, but it does not exist

##### Example 1
Let's say we run the following through `parseIf`
```php
if ($expression) 
{
}
```

```php
function parseIf($str, $parent) {
    $n = new IfNode();
    $n->ifKeyword = eat("if");
    $n->openParen = eat("(");
    $n->expression = parseExpression();
    $n->closeParen = eat(")");
    $n->block = parseBlock();
    $n->parent = $parent;
}
```

This above should generate the `IfNode` successfully. But let's say we run the following through,
which is missing a close paren token. 
```php
if ($expression // ) <- MissingToken
{
}
```

In this case, `eat(")")` will generate a `MissingToken` because the grammar expects a
token to  be there, but it does not exist.

##### Example 2
```php
class A {
    function foo() {
        return;
 // } <- MissingToken

    public function bar() {

    }
}
```

In this case, the `foo` function block is not closed. A `MissingToken` will be similarly generated,
but the logic will be a little different, in order to provide a gracefully degrading experience.
In particular, the tree that we expect here looks something like this:

![image](https://cloud.githubusercontent.com/assets/762848/19094553/727fd634-8a45-11e6-9491-97f3a6b9a35e.png)

This is achieved by continually keeping track of the current `ParseContext`. That is to say,
every time we venture into a child, that child is aware of its parent. Whenever the child gets to a token
that they themselves don't know how to handle (e.g. a `MethodNode` doesn't know what `public` means), they ask their parent if they know how to handle it, and 
continue walking up the tree. If we've walked the entire spine, and every node is similarly confused, a
`SkippedToken` will be generated. 

In this case, however, a `SkippedToken` is not generated because `ClassNode` will know what `public` means.
Instead, the method will say "okay, I'm done", generate a `MissingToken`, and `public` will be subsequently handled
by the `ClassNode`.

##### Example 3
Building on Example 2... in the following case, no one knows how to handle an 
ampersand, and so this token will become a `SkippedToken`
```php
class A {
    function foo() {
        return;
    & // <- SkippedToken
    }
    public function bar() {

    }
}
```

##### Example 4
There are also some instances, where the aforementioned error handling wouldn't be
appropriate, and special-casing based on certain heuristics, such as 
whitespace, would be required. 

```php
if ($a >
    $b = new MyClass;
```

In this case, the user likely intended the type of `$b` to be `MyClass`. However,
because under normal circumstances, parsers will ignore whitespace, the example above
would produce the following tree, whic himplies that the `$b` assignment never happens.
```
SourceFileNode
- IfNode
  - OpenParen = Token
  - Expression = RelationalExpressionNode
    - Left: $a Token
    - Right: $b Token
  - CloseParen = MissingToken
- SkippedToken: '='
- ObjectCreationExpression
  - New: Token
  - ClassTypeDesignator: MyClass
  - Semicolon: Token
```

In our design, however, because every Token includes preceding whitespace trivia, 
our parser would be able to use whitespace as a heuristic to infer the user's likely
intentions. So rather than handling the error by generating a skipped `=` token,
we could instead generate a missing token for the right hand side of the
RelationalExpressionNode.

Note that for this error case, it is more of an art than a science. That is to say, we
would add special casing for anticipated scenarios, rather than construct some general-purpose rule.

#### Other notes
* Just as it's imporant to understand the assumptions that *will* hold true,
it is also important to understand the assumptions that will not hold true.
One such **non-invariant** is that not every token generated by the lexer ends up in the tree.

### Incremental Parsing

> Note: not yet implemented, but helps guide related architectural decisions / principles.

For large files, it can be expensive to reparse the tree on every edit. Instead,
we save time by reusing nodes from the old AST.

Rather than reparsing the entire token stream, we reparse only the portion corresponding
to the edit range. Such "invalidated" nodes include the directly-intersecting node, as well as 
(by definition) its parents. 

![image](https://cloud.githubusercontent.com/assets/762848/21580025/6557333e-cf88-11e6-9d45-9adf4f6c98d4.png)

In order to minimize the impact of edge cases, we avoid context-specific conditions in the parser.
For instance, let us apply the following transformation (making an edit that turns a compound statement into an
 class):
```php
/* BEFORE */
{
    function __construct() : int { }
}

/* AFTER */
class A {
    function __construct() : int { }
}
```

Technically, a constructor cannot include a return type. However, this constraint
limits the reusability of the node during incremental parsing. Such context-specific handling
during incremental parsing complicates the logic, and tends to result in a long-tail of 
hard-to-debug incremental parsing bugs, so we avoid it where possible. Instead we produce
diagnostics once the AST has already been produced. 

In addition to simply avoiding context-specific conditions where possible, we minimize
the number of edge cases by limiting the granularity of node-reuse. In the case of this parser,
we believe a reasonable balance is to limit granularity to a list `ParseContext`. 

## Open Questions
Some open Qs:
  * need some examples of large PHP applications to help benchmark
  * would PHP 5 provide sufficient perf?
  * what sort of data structures do we need? Ideally we'd throw everything into a struct. Anything better?

## Real world validation strategy
* benchmark against other parsers (investigate any instance of disagreement)
* perf benchmarks (should be able to get semantic information )