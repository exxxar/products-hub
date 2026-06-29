<?php

return [
    // Путь для хранения дампов (относительно storage/app)
    'dumps_path' => env('WORKSPACE_DUMPS_PATH', 'dumps/workspaces'),

    // Максимальный возраст дампа в днях (старые удаляются автоматически)
    'dumps_max_age_days' => (int) env('WORKSPACE_DUMPS_MAX_AGE_DAYS', 30),
];
