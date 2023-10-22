<?php

$roboSrc = __DIR__ . '/vendor/consolidation/robo/robo';
$roboDest = __DIR__ . '/vendor/bin/robo';

if (!is_link($roboDest) && file_exists($roboSrc)) {
    symlink($roboSrc, $roboDest);
    echo "Symlink created successfully.\n";
} else {
    echo "Symlink already exists or source file not found.\n";
}
