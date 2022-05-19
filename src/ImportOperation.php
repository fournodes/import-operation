<?php

namespace Fournodes\ImportOperation;

set_time_limit(-1);

use Alert;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\ReaderInterface;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Carbon\Carbon;
use DateTime;
use Fournodes\ImportOperation\Models\ImportBatch;
use Fournodes\ImportOperation\Models\ImportMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Str;
use Validator;

trait ImportOperation
{
    protected ReaderInterface $reader;

    /**
     * Define which routes are needed for this operation.
     *
     * @param  string  $segment  Name of the current entity (singular). Used as first URL segment.
     * @param  string  $routeName  Prefix of the route name.
     * @param  string  $controller  Name of the current CrudController.
     */
    protected function setupImportRoutes($segment, $routeName, $controller)
    {
        Route::get($segment . '/import', [
            'as'        => $routeName . '.import',
            'uses'      => $controller . '@import',
            'operation' => 'import',
        ]);

        Route::post($segment . '/import', [
            'as'        => $routeName . '.save',
            'uses'      => $controller . '@save',
            'operation' => 'import',
        ]);

        Route::get($segment . '/import/map/{id}', [
            'as'        => $routeName . '.map',
            'uses'      => $controller . '@map',
            'operation' => 'import',
        ]);

        Route::post($segment . '/import/process', [
            'as'        => $routeName . '.process',
            'uses'      => $controller . '@process',
            'operation' => 'import',
        ]);
    }

    /**
     * Add the default settings, buttons, etc that this operation needs.
     */
    protected function setupImportDefaults()
    {
        $this->crud->allowAccess('import');

        $this->crud->operation('import', function () {
            $this->crud->loadDefaultOperationSettingsFromConfig('fournodes.import-operation.settings');
        });

        $this->crud->operation('list', function () {
            $this->crud->addButton('top', 'import', 'view', 'fournodes.import-operation::import_button', 'end');
        });
    }

    /**
     * Step #1. Show import form with default fields
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function import()
    {
        $this->crud->hasAccessOrFail('import');

        // Get all the feilds mentioned in setupImportOperation and filter out the ones that are defaultable plus default
        $this->crud->setOperationSetting('fields', $this->getFields(true));

        $this->data['crud']  = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? trans('fournodes.import-operation::import-operation.import') . ' ' . $this->crud->entity_name;

        // load the view from /resources/views/vendor/fournodes/import-operation/ if it exists, otherwise load the one in the package
        return view($this->crud->get('import.view') ?? 'fournodes.import-operation::import', $this->data);
    }

    /**
     * Step #2. Handle import file and redirect to mapping view.
     *
     * @return Illuminate\Http\RedirectResponse
     */
    public function save(Request $request)
    {
        $this->crud->hasAccessOrFail('import');

        // Store all form fields that were posted
        $fields = [];
        foreach ($this->getFields(true) as $field) {
            // If field type is upload extract fle
            if($field['type'] == 'upload')
                $fields[$field['name']] = $request->file('file');
            // If field is regular input then only add that if there is a value
            // An empty defaultable field indicates user wants to map the column later
            else if($request->get($field['name']))
                $fields[$field['name']] = $request->get($field['name']);
        }     

        // Define validation rules for checking data
        $validationRules = array_merge(
            // Validation rule for import file
            ['file' => 'required|file|mimes:xlsx,csv,txt,ods'],
            // Extract validation rules if a validation class was set for this operation
            array_intersect_key($this->importValidationRules(), $fields)
        );

        // Extract validation message if a validation class was set for this operation
        $validationMessages = $this->importValidationMessages();

        // Create a new validator using rules and messages
        $validator = Validator::make($fields, $validationRules, $validationMessages);

        if ($validator->fails()) {
            return redirect("{$this->crud->route}/import")->withErrors($validator);
        }

        $importBatch = ImportBatch::create([
            'defaults'  => array_intersect_key($this->crud->getStrippedSaveRequest(), $fields),
            'path'      => $request->file('file')->storeAs('import', Str::uuid()->toString() . '.' . $request->file('file')->getClientOriginalExtension()),
            'settings'  => [
                'header' => $request->get('file_contains_headers'),
            ],
        ]);

        return redirect("{$this->crud->route}/import/map/{$importBatch->id}");
    }

    /**
     * Step #3. Show import data for mapping
     *
     * @param ImportBatch $importBatch
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function map(int $importBatchId)
    {
        $topRows       = [];
        $bottomRows    = [];
        $sheets        = [];
        $topRowsFilled = $bottomRowsFilled = false;
        $totalColumns  = 0;

        $importBatch = ImportBatch::findOrFail($importBatchId);

        $settings = [
            'sheet'         => request()->get('sheet') ?? 1,
            'header'        => request()->get('header') ?? $importBatch->settings['header'],
            'top_offset'    => request()->get('top_offset') ?? 0,
            'bottom_offset' => request()->get('bottom_offset') ?? 0,
            'limit'         => request()->get('limit') ?? config('fournodes.import-operation.preview_row_limit'),
            'total_column'  => request()->get('total_column') ?? 0,
            'total'         => request()->get('total') ?? 0,
        ];

        $this->initiateFileReader($importBatch->path);

        foreach ($this->reader->getSheetIterator() as $sheetIndex => $sheet) {
            $sheets[$sheetIndex] = $sheet->getName();

            if ($sheetIndex == $settings['sheet']) {
                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    $cells = $row->toArray();

                    // Store the maximum number of column in any row
                    if (count($cells) > $settings['total_column']) {
                        $settings['total_column'] = count($cells);
                    }

                    if ($topRowsFilled) {
                        $bottomRows[$rowIndex] = $cells;
                        if ($bottomRowsFilled) {
                            unset($bottomRows[array_key_first($bottomRows)]);
                        }
                    } else {
                        $topRows[$rowIndex] = $cells;
                    }

                    if (!$topRowsFilled && count($topRows) == $settings['limit']) {
                        $topRowsFilled = true;
                    }
                    if (!$bottomRowsFilled && count($bottomRows) == $settings['limit']) {
                        $bottomRowsFilled = true;
                    }

                    $settings['total']++;
                }
            }
        }

        $importBatch->settings = $settings;
        $importBatch->save();

        $topRows    = $this->convertDateTimeInRowsToValue($topRows);
        $bottomRows = $this->convertDateTimeInRowsToValue($bottomRows);

        $this->data = [
            'crud'         => $this->crud,
            'importBatch'  => $importBatch,
            'sheets'       => $sheets,
            'topRows'      => $topRows,
            'bottomRows'   => $bottomRows,
            'mappings'     => ImportMapping::whereModelType($this->crud->getModel()->getMorphClass())->get(),
            'fields'       => $this->getFields(false, array_keys($importBatch->defaults)),
        ];

        $this->reader->close();
        // load the view from /resources/views/vendor/fournodes/import-operation/ if it exists, otherwise load the one in the package
        return view($this->crud->get('import.parse.view') ?? 'fournodes.import-operation::import_parse', $this->data);
    }

    /**
     * Save mapping and import data to its entity
     * If data fails validation, loads error view with faulty rows
     * wich re-submits to this function also.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @return \CRUD\Route
     */
    public function process(Request $request)
    {
        $this->crud->hasAccessOrFail('import');

        $importMappingData = [
            'id'         => $request->mapping_id ?? null,
            'name'       => $request->mapping_name ?? "Untitled Mapping# {$request->import_batch_id}",
            'model_type' => $this->crud->getModel()->getMorphClass(),
            'mapping'    => $request->mapping,
        ];

        // Check if an exiting mapping option was selected
        if (isset($request->mapping_id)) {
            ImportMapping::whereId($request->mapping_id)->update($importMappingData);
        }
        // Check if a new mapping is to be saved
        elseif (!is_null($request->mapping_name)) {
            $importMappingData = ImportMapping::create($importMappingData);
        }

        // Load batch information.
        $importBatch = ImportBatch::find($request->import_batch_id);

        if ($request->sheet) {
            $importBatch->settings = [
                'sheet'         => $request->sheet,
                'header'        => $request->header,
                'top_offset'    => $request->top_offset,
                'bottom_offset' => $request->bottom_offset,
                'limit'         => $request->limit,
                'total_column'  => $request->total_column,
                'total'         => $request->total,
            ];

            $importBatch->save();
        }

        // If coming from error view update batch data with updated data
        if (isset($request->error_rows)) {
            $inputFileInfo  = pathinfo($importBatch->path);

            $inputFileName = storage_path('app/' . $importBatch->path);
            $reader        = ReaderEntityFactory::createReaderFromFile($inputFileName);
            $reader->open($inputFileName);

            $inputFileName  = storage_path("app/{$importBatch->path}");
            $outputFileName = storage_path("app/{$inputFileInfo['dirname']}/" . Str::uuid()->toString() . ".{$inputFileInfo['extension']}");

            $writer = WriterEntityFactory::createWriterFromFile($outputFileName);
            $writer->openToFile($outputFileName);

            $writtenRows = 0;

            foreach ($reader->getSheetIterator() as $sheetIndex => $sheet) {
                if ($sheetIndex == $importBatch->settings['sheet']) {
                    foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                        // If row is deleted by user, skip it
                        if (isset($request->remove_rows[$rowIndex]) && $request->remove_rows[$rowIndex] == 1) {
                            continue;
                        }

                        foreach ($row->toArray() as $cellIndex => $cell) {
                            // If cell value is a DateTime instance
                            if ($cell instanceof DateTime) {
                                $cell = (
                                    isset($request->error_rows[$rowIndex][$cellIndex])
                                    ? (new DateTime($request->error_rows[$rowIndex][$cellIndex]))
                                    : $cell
                                )->format('Y-m-d');
                            } else {
                                $cell = (
                                    isset($request->error_rows[$rowIndex][$cellIndex])
                                    ? $request->error_rows[$rowIndex][$cellIndex]
                                    : $cell
                                );
                            }

                            // update row
                            $row->setCellAtIndex(WriterEntityFactory::createCell($cell), $cellIndex);
                        }
                        // write the edited row to the new file in new file
                        $writer->addRow($row);
                        $writtenRows++;
                    }
                }
            }

            // Update total number of newly written rows
            $batchSettings = $importBatch->settings;            
            $batchSettings['total'] = $writtenRows;
            $importBatch->settings = $batchSettings;
            $importBatch->save();

            $reader->close();
            $writer->close();

            // Delete old file and rename new file with old file's name
            unlink($inputFileName);
            rename($outputFileName, $inputFileName);
        }

        // initiating file reader
        $this->initiateFileReader($importBatch->path);

        // Create a mapping index lookup array
        $mappingLookup = [];
        foreach ($importMappingData['mapping'] as $key => $value) {
            if (!empty($value)) {
                $mappingLookup[$value] = $key;
            }
        }

        // Run validations and create.
        $errorRows     = [];
        $errorMessages = [];
        $errorRowCount = 0;
        $errorRowLimit = (int) config('fournodes.import-operation.error_row_limit');

        $validationRules    = $this->importValidationRules();
        $validationMessages = $this->importValidationMessages();

        $topOffset = $importBatch->settings['top_offset'] + $importBatch->settings['header'];
        $rowLimit  = $importBatch->settings['total'] - $importBatch->settings['bottom_offset'];

        // Iterate over all the rows of the selected sheet
        foreach ($this->reader->getSheetIterator() as $sheetIndex => $sheet) {
            if ($sheetIndex == $importBatch->settings['sheet']) {
                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    $currentRow     = [];
                    $mappedRow      = [];

                    if ($rowIndex > $rowLimit) {
                        break;
                    }

                    if ($rowIndex <= $topOffset) {
                        continue;
                    }

                    $cells = $row->toArray();

                    // make sure all rows have equal number of columns
                    if (count($cells) < $importBatch->settings['total_column']) {
                        $cells = array_pad($cells, $importBatch->settings['total_column'], null);
                    }

                    foreach ($cells as $cellIndex => $cell) {
                        $cellValue = $cell instanceof DateTime
                        ? $cell->format('Y-m-d')
                        : $cell;

                        if (!empty($importMappingData['mapping'][$cellIndex])) {
                            $mappedRow[$importMappingData['mapping'][$cellIndex]] = $cellValue;
                        }

                        $currentRow[] = $cellValue;
                    }

                    // Override default fields
                    foreach ($importBatch->defaults as $mappingName => $mappingValue) {
                        $mappedRow[$mappingName] = $mappingValue;
                    }

                    // Running validation against the mapped row
                    $validator = Validator::make($mappedRow, $validationRules, $validationMessages);

                    // If validation fails, add row to error and its error messages
                    if ($validator->fails()) {
                        $errorRowCount++;
                        $errorMessages[$rowIndex] = $validator->errors()->toArray();
                        $errorRows[$rowIndex]     = $currentRow;
                    }

                    // break loop if row count exceeds error rows limit
                    if ($errorRowCount >= $errorRowLimit) {
                        break;
                    }
                }
            }
        }

        // If no errors werer found
        if ($errorRowCount === 0) {
            $insertCount = 0;
            $importData  = [];

            // Iterate over all the rows of the selected sheet
            foreach ($this->reader->getSheetIterator() as $sheetIndex => $sheet) {
                if ($sheetIndex == $importBatch->settings['sheet']) {
                    foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                        if ($rowIndex > $rowLimit) {
                            break;
                        }

                        if ($rowIndex <= $topOffset) {
                            continue;
                        }

                        // Mapping each row to seleted mapping
                        $row = $row->toArray();
                        foreach ($mappingLookup as $mappingName => $index) {
                            $importData[$rowIndex][$mappingName] = isset($row[$index]) ? $row[$index] : null;
                        }

                        // Adding default fields
                        foreach ($importBatch->defaults as $mappingName => $mappingValue) {
                            $importData[$rowIndex][$mappingName] = $mappingValue;
                        }

                        // Batch insert when insertable row count reaches a limit
                        if (count($importData) >= 500) {
                            $insertCount += $this->crud->model->insertOrIgnore($importData);
                            $importData = [];
                        }
                    }
                }
            }
            $this->reader->close();

            if (!empty($importData)) {
                // TODO: Add logic to handle what happens when some records are not imported
                $insertCount += $this->crud->model->insertOrIgnore($importData);
            }

            unlink(storage_path('app/' . $importBatch->path));

            Alert::success(trans('fournodes.import-operation::import-operation.import_success', ['count' => $insertCount]))->flash();

            return redirect($this->crud->route);
        } else {
            $this->reader->close();

            $this->data = [
                'crud'            => $this->crud,
                'importBatch'     => $importBatch,
                'errorRows'       => $errorRows,
                'errorMessages'   => $errorMessages,
                'moreErrors'      => $errorRowCount >= $errorRowLimit,
                'selectedMapping' => $importMappingData,
                'mappingLabels'   => $this->getFields(false, array_keys($importBatch->defaults)),
            ];

            Alert::error(trans('fournodes.import-operation::import-operation.import_error', ['count' => $errorRowCount]));

            return view($this->crud->get('import.parse_error.view') ?? 'fournodes.import-operation::import_parse_error', $this->data);
        }
    }

    /**
     * Fetch validation rules from entity's validation request class
     * @return array
     */
    protected function importValidationRules()
    {
        if ($validationRequestClass = $this->crud->getFormRequest()) {
            $validationRequest = new $validationRequestClass();

            return $validationRequest->rules();
        }

        return [];
    }

    protected function importValidationMessages()
    {
        if ($validationRequestClass = $this->crud->getFormRequest()) {
            $validationRequest = new $validationRequestClass();

            return $validationRequest->messages();
        }

        return [];
    }

    /**
     * Returns list of importable fields.
     *
     * There are two type of importable fields
     *
     * 1. Default fields:
     * Sometimes you want some fields to have a fixed value for all records
     * Developers have the option to define some fields as default by adding the default property
     * These fields are populated at the time of file selection and apply for all records
     * Example of default fields could be userID or userType etc
     *
     * @return array
     */
    private function getFields($defaultFieldsOnly = false, $exceptFields = [])
    {
        $allFields = collect($this->crud->fields());

        // When defaultFieldsOnly is true, this will return file picker, checkbox for header and importable fields
        if ($defaultFieldsOnly) {
            // These fields always show on import step
            $importFields = collect([
                'file' => [
                    'name'   => 'file',
                    'label'  => trans('fournodes.import-operation::import-operation.file_field_label'),
                    'type'   => 'upload',
                    'upload' => true,
                    'disk'   => 'uploads',
                ],
                'headers' => [
                    'name'  => 'file_contains_headers',
                    'label' => trans('fournodes.import-operation::import-operation.headers_field_label'),
                    'type'  => 'checkbox',
                ],
            ]);

            // Any field that is default added to import step
            $fields = $importFields->merge(
                $allFields->filter(fn ($field) => isset($field['import_default']) && $field['import_default'] == true)
            );
        } else {
            $fields = $allFields->except($exceptFields);
        }

        return $fields->toArray();
    }

    private function initiateFileReader($filePath = null)
    {
        if ($filePath) {
            $inputFileName = storage_path('app/' . $filePath);
            $this->reader  = ReaderEntityFactory::createReaderFromFile($inputFileName);
            // $this->reader->setShouldFormatDates(true);
            $this->reader->open($inputFileName);
        }
    }

    private function convertDateTimeInRowsToValue($rows)
    {
        if (count($rows)) {
            foreach ($rows as $rowIndex => $row) {
                foreach ($row as $cellIndex => $cell) {
                    if ($cell instanceof DateTime) {
                        $rows[$rowIndex][$cellIndex] = Carbon::instance($cell)->isoFormat(config('backpack.base.default_date_format'));
                    }
                }
            }
        }

        return $rows;
    }
}
