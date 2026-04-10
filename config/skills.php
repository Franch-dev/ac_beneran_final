<?php

return [
    'path' => env('SKILLS_PATH', base_path('skills')),
    'allowed_extensions' => ['md', 'markdown', 'txt', 'json', 'yaml', 'yml'],
    'max_file_size' => (int) env('SKILLS_MAX_FILE_SIZE', 2 * 1024 * 1024),
    'snippet_length' => (int) env('SKILLS_SNIPPET_LENGTH', 1200),
];

