# PHP Parser Syntax Visualizer Tool
## Overview
VSCode Extension that demonstrates some of the basic usage and functionality of the parser.
- writes AST to adjacent *.ast file using JSON representation
- Error squigglies

![image](https://cloud.githubusercontent.com/assets/762848/21635753/3f8c0cb8-d214-11e6-8424-e200d63abc18.png)



## Instructions
1. npm install `client`/`server` dependencies, run Extension
2. open PHP file from `example` folder, and `*.ast` file to the side
3. check out error information, and corresponding AST
4. make some changes, and save to view updates

## Settings
* `php.syntaxVisualizer.parserPath`: set this path to use a different parser version than the one bundled with the
syntax visualizer. For instance, after `git clone https://github.com/Microsoft/tolerant-php-parser`, set
`php.syntaxVisualizer.parserPath` to the absolute directory of the `tolerant-php-parser/src` folder. This will enable you to easily
debug the parser during development. 