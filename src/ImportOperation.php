<?php

namespace Fournodes\ImportOperation;

use Alert;
use Fournodes\ImportOperation\Models\ImportBatch;
use Fournodes\ImportOperation\Models\ImportMapping;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Storage;
use Validator;

trait ImportOperation
{
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

        // Get all the fields that were posted
        $fields = $request->all();

        // Define validation rules for checking data
        $validationRules = array_merge(
            // Validation rule for import file
            ['file' => 'required|file|mimes:csv,txt'],
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
            'defaults' => $this->crud->getStrippedSaveRequest(),
            'path'     => $request->file('file')->store('import'),
            'headers'  => $request->has('headers'),
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
        $batch = ImportBatch::find($importBatchId);

        $rows = $this->fileToArray($batch->path);

        $this->data = [
            'crud'     => $this->crud,
            'batch'    => $batch,
            'headers'  => $batch->headers ? array_shift($rows) : false,
            'rows'     => array_slice($rows, 0, config('fournodes.import-operation.preview_row_count')),
            'mappings' => ImportMapping::whereModelType($this->crud->getModel()->getMorphClass())->get(),
            'fields'   => $this->getFields(),
        ];

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
            'name'       => $request->mapping_name ?? "Untitled Mapping# {$request->batch_id}",
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
        $importBatch     = ImportBatch::find($request->batch_id);
        $importBatchData = $this->fileToArray($importBatch->path, $importBatch->headers);

        // If coming from error view update batch data with updated data
        if (isset($request->data)) {
            foreach ($request->data as $key => $row) {
                $importBatchData[$key] = $row;
            }

            $this->arrayToFile($importBatch->path, $importBatchData);
        }

        // Create a mapping index lookup array
        $mappingLookup = [];
        foreach ($importMappingData['mapping'] as $key => $value) {
            if (!empty($value)) {
                $mappingLookup[$value] = $key;
            }
        }

        $importData = [];
        foreach ($importBatchData as $row) {
            $temp = [];

            // Mapping data
            foreach ($mappingLookup as $mappingName => $index) {
                $temp[$mappingName] = $row[$index];
            }

            // Adding default fields
            foreach ($importBatch->defaults as $mappingName => $mappingValue) {
                $temp[$mappingName] = $mappingValue;
            }

            $importData[] = $temp;
        }

        // Run validations and create.
        $errors             = [];
        $validationRules    = $this->importValidationRules();
        $validationMessages = $this->importValidationMessages();

        foreach ($importData as $index => $entity) {
            $validator = Validator::make($entity, $validationRules, $validationMessages);

            if ($validator->fails()) {
                $errors[$index] = $validator->errors()->toArray();
            }
        }

        if (empty($errors)) {
            // TODO: Add logic to handle what happens when some records are not imported
            $insertCount = $this->crud->model->insertOrIgnore($importData);

            Alert::success(trans('fournodes.import-operation::import-operation.import_success', ['count' => $insertCount]))->flash();

            return redirect($this->crud->route);
        } else {
            $this->data = [
                'batch_id'        => $request->batch_id,
                'crud'            => $this->crud,
                'data'            => $importBatchData,
                'errors'          => $errors,
                'selectedMapping' => $importMappingData,
            ];

            Alert::error(trans('fournodes.import-operation::import-operation.import_error', ['count' => count($errors)]));

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
    private function getFields($includeDefaultFields = false)
    {
        $allFields = collect($this->crud->fields());

        // When includeDefaultFields is true, this will return all file picker and checkbox for header + importable fields
        if ($includeDefaultFields) {
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
                    'name'  => 'headers',
                    'label' => trans('fournodes.import-operation::import-operation.headers_field_label'),
                    'type'  => 'checkbox',
                ],
            ]);

            // Any field that is default added to import step
            $fields = $importFields->merge(
                $allFields->filter(fn ($field) => isset($field['default']) && $field['default'] == true)
            );
        } else {
            $fields = $allFields->filter(fn ($field) => !isset($field['default']));
        }

        return $fields->toArray();
    }

    private function fileToArray($filepath, $skipHeader = false)
    {
        $data = array_map('str_getcsv', file(storage_path('app/' . $filepath)));

        if ($skipHeader) {
            array_shift($data);
        }

        return $data;
    }

    private function arrayToFile($filepath, $data)
    {
        $file = fopen(storage_path('app/' . $filepath), 'w');

        foreach ($data as $line) {
            fputcsv($file, $line);
        }

        fclose($file);
    }
}
