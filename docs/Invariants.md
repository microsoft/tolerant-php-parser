# Invariants
> This documentation was auto-generated using this parser to help dogfood the API. Please contribute
 fixes to `tools/PrintInvariants.php` and suggest API improvements.

We define and test both parser and lexer against a set of invariants (characteristics
about the produced token set or tree that always hold true, no matter what the input). This set of invariants provides
a consistent foundation that makes it easier to ensure the tree is "structurally sound", and confidently
reason about the tree as we continue to build up our understanding.

## Token Invariants
- Sum of the lengths of all the tokens should be equivalent to the length of the document.
- A token's Start is always >= FullStart.
- A token's content exactly matches the range of the file its span specifies
- FullText of each token matches Trivia plus Text
- Concatenating FullText of each token returns the document
- a token's FullText length is equivalent to Length - (Start - FullStart)
- a token's Trivia length is equivalent to (Start - FullStart)
- End-of-file token text should have zero length
- Tokens array should always end with end of file token
- Tokens array should contain exactly one EOF token
- Token FullStart should begin immediately after previous token end
- SkippedToken length should be greater than 0
- MissingToken length should be equal to 0

## Node Invariants
- All invariants of Tokens
- The tree length exactly matches the file length.
- All Nodes have at least one child. $encode
- Span of any Node is span of child nodes and tokens.
- Parent of Node contains same child node.
- each child has exactly one parent.
- Every child is Node or Token type
- Root node of tree has no parent.
- root node of tree is never a child.
