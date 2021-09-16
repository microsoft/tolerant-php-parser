# PHP Parser Syntax Visualizer Tool
## Overview
VSCode Extension that demonstrates some of the basic usage and functionality of the parser.

### Writes AST to adjacent *.ast file using JSON representation
![image](https://cloud.githubusercontent.com/assets/762848/21635753/3f8c0cb8-d214-11e6-8424-e200d63abc18.png)

### Error Diagnostics from AST
![image](https://cloud.githubusercontent.com/assets/762848/21705272/d5f2f7d8-d373-11e6-9688-46ead75b2fd3.png)
## Install from VSIX
1. [Download the VSIX](https://github.com/microsoft/tolerant-php-parser/raw/main/syntax-visualizer/client/php-syntax-visualizer-0.0.1.vsix), and load into VS Code by running
`code --install-extension <my-vsix-path>` or by selecting `Install from VSIX...`
from the Command Palette (`Ctrl+Shift+P`).
![image](https://cloud.githubusercontent.com/assets/762848/21704944/62191a56-d371-11e6-97f6-8cc9ea0bbdec.png)

2. Open a folder with some PHP files
3. Edit the file - you'll see an adjacent `*.ast` file will appear. Additionally, you should see
error squigglies if there are any errors in the file.
4. The AST will be updated every time you save the file.

> Note: You may need to disable any other PHP language service extensions (no need
to them off completely - you can disable them on a per-workspace basis)
> * Set `"php.validate.enable": false`
> * Disable other PHP language service extensions like Crane and PHP IntelliSense

## Build from Source
1. From `syntax-visualizer/server/`, run `npm install && npm run compile`
2. From `syntax-visualizer/client`, run `npm install && npm run compile`
3. Open `syntax-visualizer/client` in VS Code, and press F5 to launch the extension in the debugger.
    - This will open a new instance of VS Code with the extension loaded

When running in this configuration, any changes you make to the parser will be immediately reflected
in the extension (which makes it *super* handy for debugging any failing tests in the parser.)
