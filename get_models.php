<?php
foreach(glob('app/Models/*.php') as $file) {
    $content = file_get_contents($file);
    preg_match('/class (\w+)/', $content, $m);
    preg_match('/protected \$table\s*=\s*\'([^\']+)\'/', $content, $t);
    preg_match('/protected \$fillable\s*=\s*\[([^\]]+)\]/', $content, $f);
    $className = $m[1] ?? basename($file, '.php');
    $tableName = $t[1] ?? '';
    $fillable = isset($f[1]) ? trim($f[1]) : '';
    echo "$className ($tableName): $fillable\n";
}
