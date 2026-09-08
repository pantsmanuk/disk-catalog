<?php

$rows = max(1, (int) env('DISK_SHELF_ROWS', 1));
$columns = max(1, (int) env('DISK_SHELF_COLUMNS', 24));

return [
    'shelf' => [
        'rows' => $rows,
        'columns' => $columns,
        'slots' => $rows * $columns,
        'auto_fit' => (bool) env('DISK_SHELF_AUTO_FIT', false),
    ],
];
