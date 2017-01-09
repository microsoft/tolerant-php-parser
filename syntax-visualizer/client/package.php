<?php

copy(__DIR__ . "/../server/src/parse.php", __DIR__ . "/server/parse.php");

$outZip = __DIR__ . "/server/parser.zip";
$outDir = __DIR__ . '/server/parser';
if (file_exists($outDir)) {
    rmdirRecursive($outDir);
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