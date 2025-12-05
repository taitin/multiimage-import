<?php

return [
    '1.0.0' => [
        'Initialize extension.',
    ],
    '1.0.1' => [
        'Update.',
    ],

    '1.0.2' => [
        'fix mutil problem.',
    ],

    '1.0.3' => [
        'Fix file upload conflict causing errno=21 directory error.',
        'Improve setId() method to always generate unique IDs.',
        'Add automatic cleanup for temporary directories older than 24 hours.',
        'Prevent multi-user file path conflicts.',
    ],

    '1.0.4' => [
        'Fix ZipArchive::extractTo() Invalid or uninitialized Zip object error.',
        'Add proper ZIP file validation before extraction.',
        'Provide detailed error messages for ZIP file failures.',
        'Auto-create extraction directory if not exists.',
    ],

    '1.0.5' => [
        'Fix ZIP file path duplication issue.',
        'Correct ZIP file path construction to use uploaded file path directly.',
        'Prevent path errors like import_temp/ID/files/import_temp/ID/files/',
    ],

    '1.0.6' => [
        'Handle missing files gracefully during import.',
        'Skip missing files with warning log instead of failing entire import.',
        'Add file existence check before copy operation.',
        'Improve error handling with try-catch in fileMove method.',
    ],

];
