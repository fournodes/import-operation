<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Import-Operation Model
    |--------------------------------------------------------------------------
    */

    /*
    * Import Operation
    */
    'settings' => [
        // Define the size/looks of the content div for all CRUDs
        'contentClass' => 'col-md-8 bold-labels',

        // When using tabbed forms, what kind of tabs would you like?
        'tabsType' => 'horizontal', //options: horizontal, vertical

        // How would you like the validation errors to be shown?
        'groupedErrors' => true,
        'inlineErrors'  => true,

        // when the page loads, put the cursor on the first input?
        'autoFocusOnFirstField' => true,

        // Should we show a cancel button to the user?
        'showCancelButton' => true,

        // Before saving the entry, how would you like the request to be stripped?
        // - false - ONLY save inputs that have fields (safest)
        // - [x, y, z] - save ALL inputs, EXCEPT the ones given in this array
        'saveAllInputsExcept' => false,
        // 'saveAllInputsExcept' => ['_token', '_method', 'http_referrer', 'current_tab', 'save_action'],
    ],

    'import_batch_table_name'   => 'import_operation_batches',
    'import_mapping_table_name' => 'import_operation_mappings',

    'preview_row_limit' => 10,
    'error_row_limit'   => 20,
];
