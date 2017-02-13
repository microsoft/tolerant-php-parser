<?php

file_put_contents(
    __DIR__ . "/server/config.php",
    <<< 'PHP'
<?php
$GLOBALS["PARSER_PATH"] = __DIR__ . "/parser/src/";
PHP
);

copy(__DIR__ . "/../server/src/parse.php", __DIR__ . "/server/parse.php");

$outZip = __DIR__ . "/server/parser.zip";
$outDir = __DIR__ . '/server/parser';
if (file_exists($outDir)) {
    rmdirRecursive($outDir);
}

// Package the latest parser source file archive from msft/master
// This prevents old or in-progress work from being accidentally packaged with the extension.
// TODO - eventually add more configuration options
$updateMasterOutput = `git diff master msft/master 2>&1`;
if (\count($updateMasterOutput) > 0) {
    throw new Exception("master branch is not up to date with msft/master");
}

$root = exec("git rev-parse --show-toplevel");
exec("cd $root && git archive --format zip --output \"$outZip\" master");

$zip = new ZipArchive;
$zip->open($outZip);
$zip->extractTo($outDir);
$zip->close();
unlink($outZip);

function rmdirRecursive($dir) {
    $children = array_diff(scandir($dir), ["..", "."]);
    foreach($children as $child) {
        $childPath = "$dir/$child";
        is_dir($childPath) ? rmdirRecursive($childPath) : unlink($childPath);
    }
    rmdir($dir);
}