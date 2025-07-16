<?php

return [
    // Storage disk where system documents are stored
    'storage_disk' => 'local',
    
    // Path prefix within the storage disk
    'path_prefix' => 'documents/',
    
    // File extensions allowed for system documents
    'allowed_extensions' => [
        'pdf', 'doc', 'docx', 'txt', 'rtf',
        'ppt', 'pptx', 'xls', 'xlsx', 'csv'
    ]
];
