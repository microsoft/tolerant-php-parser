# Tolerant PHP Parser
This is an early prototype of a PHP parser designed, from the beginning, for IDE usage scenarios (see [Design Goals](#design-goals) for more details). There is
still a ton of work to be done, so at this point, this repo mostly serves as 
an experiment and the start of a conversation.

![image](https://cloud.githubusercontent.com/assets/762848/19023070/4ab01c92-889a-11e6-9bb5-ec1a6816aba2.png)

## Get Started
**:dart: [Design Goals](#design-goals)** - learn about the design goals of the project (features, performance metrics, and more).

**:sunrise_over_mountains: [Syntax Overview](Overview.md)** - learn about the composition and key properties of the syntax tree.

**:seedling: [Documentation](GettingStarted.md#getting-started)** - learn how to reference the parser from your project, and how to perform
operations on the AST to answer questions about your code.

**:eyes: [Syntax Visualizer Tool](syntax-visualizer/client#php-parser-syntax-visualizer-tool)** - get a more tangible feel for the AST. Get creative - see if you can break it! 

**:chart_with_upwards_trend: [Current Status and Approach](#current-status-and-approach)** - how much of the grammar is supported? Performance? Memory? API stability?

**:wrench: [How it works](HowItWorks.md)** - learn about the architecture, design decisions, and tradeoffs.
  * [Lexer and Parser](HowItWorks.md#lexer)
  * [Error Tolerance Strategy](HowItWorks.md#error-tokens)
  * [Incremental Parsing](HowItWorks.md#incremental-parsing)
  * [Open Questions](HowItWorks.md#open-questions)
  * [Validation Strategy](HowItWorks.md#validation-strategy)

**:sparkling_heart: [Contribute!](Contributing.md)** - learn how to get involved, check out some pointers to educational commits that'll
help you ramp up on the codebase (even if you've never worked on a parser before), 
and recommended workflows that make it easier to iterate.

## Design Goals
* Error tolerant design - in IDE scenarios, code is, by definition, incomplete. In the case that invalid code is entered, the
parser should still be able to recover and produce a valid + complete tree, as well as relevant diagnostics. 
* Fast and lightweight (should be able to parse several MB of source code per second,
 to leave room for other features). 
  * Memory-efficient data structures
  * Allow for incremental parsing in the future
* Adheres to [PHP language spec](https://github.com/php/php-langspec),
supports both PHP5 and PHP7 grammars
* Generated AST provides properties (fully representative, etc.) necessary for semantic and transformational
operations, which also need to be performant.
  * Fully representative and round-trippable back to the text it was parsed from (all whitespace and comment "trivia" are included in the parse tree)
  * Possible to easily traverse the tree through parent/child nodes
  * [< 100 ms UI response time](https://www.computer.org/csdl/proceedings/afips/1968/5072/00/50720267.pdf),
so each language server operation should be < 50 ms to leave room for all the
 other stuff going on in parallel.
* Simple and maintainable over time - parsers have a tendency to get *really*
 confusing, really fast, so readability and debug-ability is high priority.
* Testable - the parser should produce provably valid parse trees. We achieve this by defining and continuously testing
 a set of invariants about the tree.
* Friendly and descriptive API to make it easy for others to build on. 
* Written in PHP - make it as easy as possible for the PHP community to consume and contribute.

## Current Status and Approach
To ensure a sufficient level of correctness at every step of the way, the
parser is being developed using the following incremental approach:

* [x] **Phase 1:** Write lexer that does not support PHP grammar, but supports EOF 
and Unknown tokens. Write tests for all invariants.
* [x] **Phase 2:** Support PHP lexical grammar, lots of tests
* [x] **Phase 3:** Write a parser that does not support PHP grammar, but produces tree of 
Error Nodes. Write tests for all invariants.
* [x] **Phase 4:** Support PHP syntactic grammar, lots of tests
* [ ] **Phase 5 (in progress :running:):** Real-world validation and optimization
  * [ ] _**Correctness:**_ validate that there are no errors produced on sample codebases, benchmark against other parsers (investigate any instance of disagreement), fuzz-testing
  * [ ] _**Performance:**_ profile, benchmark against large PHP applications
* [ ] **Phase 6:** Finalize API to make it as easy as possible for people to consume. 

> :rabbit: **Ready to see just how deep the rabbit hole goes?** Check out the [Overview](Overview.md) to learn more about key properties of the Syntax Tree and [How It Works](HowItWorks.md) for all the fun technical details.

<hr>
This project has adopted the [Microsoft Open Source Code of Conduct](https://opensource.microsoft.com/codeofconduct/). 
For more information see the [Code of Conduct FAQ](https://opensource.microsoft.com/codeofconduct/faq/) or contact 
[opencode@microsoft.com](mailto:opencode@microsoft.com) with any additional questions or comments.
