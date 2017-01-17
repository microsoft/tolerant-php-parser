# Overview

The syntax tree produced by the parser ensures two key attributes:

1. **All source information is held in full fidelity.** This means that the tree contains every piece of 
information found in the source text, every grammatical construct, every lexical token, and everything
else in between including whitespace and comments. The syntax trees also represent errors in source code
when the program is incomplete or malformed, by representing skipped or missing tokens in the syntax tree.

2. **A syntax tree obtained from the parser is completely round-trippable back to the text it was parsed from.**
From any syntax node, it is possible to get the text representation of the subtree rooted at that node.
This means that syntax trees can be used as a way to construct and edit source text.

## Key Concepts
The **Syntax Tree** produced is literally a tree data structure, where non-terminal structural elements parent other
elements. Each syntax tree is made up of **Nodes** (non-terminal elements) and
 **Tokens** (terminal elements).

Additionally associated with each Node and Token is **Positional Information**, **Errors**, and **Comment + Whitespace Trivia**.

All trees guarantee a set of **Invariants** - properties of the tree that always hold true, no matter what the
input. This set of invariants provides a consistent foundation 
that makes it easier to ensure the tree is "structurally sound", and confidently reason about the tree 
as we continue to build up our understanding. For instance, one such invariant is that the original text 
(including whitespace and comments) should always be reproducible from a Node. See [Invariants](Invariants.md)
for a complete list. 

## Tree Elements
### Nodes
Syntax nodes are one of the primary elements of syntax trees. These nodes represent 
syntactic constructs such as declarations, statements, clauses, and expressions. 
Each category of syntax nodes is represented by a separate class derived from `Node`.

### Tokens
Syntax tokens are the terminals of the language grammar, representing the smallest syntactic 
fragments of the code. They are never parents of other nodes or tokens. Syntax tokens 
consist of keywords, identifiers, literals, and punctuation.

For efficiency purposes, unlike syntax nodes, there is only one structure for all 
kinds of tokens with a mix of properties that have meaning depending on the kind 
of token that is being represented.

### Whitespace and Comment Trivia
Because whitespace and comment trivia are not part of the normal language syntax and can appear anywhere between 
any two tokens, they are not included in the syntax tree as a child of a node. Yet, because 
they are important when implementing a feature like refactoring and to maintain full 
fidelity with the source text, they do exist as part of the syntax tree.

You can access trivia by inspecting a token's LeadingWhitespaceAndComments. When source text is parsed,
sequences of trivia are associated with tokens. 

### Positional Information
Each node, token, or trivia knows its position within the source text and the number of 
characters it consists of. A text position is represented as a 32-bit integer, which is 
a zero-based byte index into the string. The width corresponds to a count of characters,
represented as integers. Zero-length refers to a location between two characters.

For efficiency purposes, the position refers to the absolute position within the text, 
and a helper function is available if you require Line/Column information.

### Errors
Even when the source text contains syntax errors, a full syntax tree that is round-trippable
to the source is exposed. When the parser encounters code that does not conform to the 
defined syntax of the language, it uses one of two techniques to create a syntax tree.

First, if the parser expects a particular kind of token, but does not find it, it may 
insert a missing token into the syntax tree in the location that the token was expected. 
A missing token represents the actual token that was expected, but it has an empty span.

Second, the parser may skip tokens until it finds one where it can continue parsing. 
In this case, the skipped tokens that were skipped are attached as a skipped token in the tree.

Note that the parser produces trees in a tolerant fashion, and will not produce errors for
all incorrect constructs (e.g. including a non-constant expression as the default value of
a method parameter). Instead, it attaches these errors on a post-parse walk of the tree.

## Next Steps
Check out the [Readme](../README.md) and [Getting Started](GettingStarted.md) pages for more information on how consume
the parser, or the [How It Works](HowItWorks.md) section if you want to dive deeper into the implementation.
