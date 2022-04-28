@extends(backpack_view('blank'))
@section('header')
    <div class="container-fluid">
        <h2>
            <span class="text-capitalize">{!! trans('fournodes.import-operation::import-operation.mapping') . ' ' . ($crud->getHeading() ?? $crud->entity_name_plural) . ' ' . trans('fournodes.import-operation::import-operation.import') !!}</span>
            <small id="datatable_info_stack">{!! $crud->getSubheading() ?? '' !!}</small>
        </h2>
    </div>
@endsection

@section('content')
    <div class="row mt-4">
        <div class="card">
            <div class="card-body">                
                <form class="form-horizontal" method="POST" action="{{ url($crud->route . '/import/process') }}" onsubmit="return checkIfMappingIsSelected()">
                    {{ csrf_field() }}
                    <input type="hidden" name="import_batch_id" value="{{ $importBatch->id }}" />
                    <input type="hidden" name="total" value="{{ $totalRows }}" />
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mapping-group d-none">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">New Mapping</span>
                                </div>
                                <input type="text" name="mapping_name" class="form-control" placeholder="Enter Mapping Name">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-default toggle-mapping-group"><i class="la la-times"></i></button>
                                    <button type="button" class="btn btn-primary" data-toggle="collapse" href="#settings" role="button" aria-expanded="false" aria-controls="settings"><i class="la la-cog"></i></button>
                                </div>
                            </div>
                            <div class="input-group mapping-group">
                                <select name="mapping_id" class="form-control">
                                    <option value="" selected disabled>Select Mapping</option>
                                    @foreach ($mappings as $mapping)
                                        <option data-name="{{ $mapping['name'] }}" data-mapping="{{ json_encode($mapping['mapping']) }}" value="{{ $mapping['id'] }}">
                                            {{ $mapping['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-success toggle-mapping-group"><i class="la la-plus"></i></button>
                                    <button type="button" class="btn btn-primary" data-toggle="collapse" href="#settings" role="button" aria-expanded="false" aria-controls="settings"><i class="la la-cog"></i></button>
                                </div>
                            </div>
                        </div>
                        <div id="settings" class="col-md-12 collapse">
                            <div class="card card-body mb-3">
                                <div class="row">
                                    <div class="col-md-{{ $bottomRows ? 4 : 6 }} form-group">
                                        <label>Sheet</label>
                                        <select name="sheet" class="form-control">
                                            @foreach ($sheets as $sheetNumber => $sheetName)
                                                <option value="{{ $sheetNumber }}" {{ $importBatch->settings['sheet'] == $sheetNumber ? 'selected' : '' }}>
                                                    {{ $sheetName }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 form-group">
                                        <label>Headers</label>                                                                        
                                        <select name="header" class="form-control">
                                            <option value="0" {{ $importBatch->settings['header'] == 0 ? 'selected' : '' }}>No</option>
                                            <option value="1" {{ $importBatch->settings['header'] == 1 ? 'selected' : '' }}>Yes</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 form-group">
                                        <label>Top Offset</label>
                                        <select name="top_offset" class="form-control">
                                            @for ($x = 0; $x <= count($topRows); $x++)
                                                <option value="{{ $x }}" {{ $importBatch->settings['top_offset'] == $x ? 'selected' : '' }}>{{ $x }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    @if ($bottomRows)
                                        <div class="col-md-2 form-group">
                                            <label>Bottom Offset</label>
                                            <select name="bottom_offset" class="form-control">
                                                @for ($x = 0; $x <= count($bottomRows); $x++)
                                                    <option value="{{ $x }}" {{ $importBatch->settings['bottom_offset'] == $x ? 'selected' : '' }}>{{ $x }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    @endif
                                    <div class="col-md-2 form-group">
                                        <label>Limit</label>
                                        <select name="limit" class="form-control">
                                            @php
                                                $limit = config('fournodes.import-operation.preview_row_limit');
                                                for ($i=1; $i <= 5; $i++) {
                                                    $value = $i * $limit;
                                                    echo "<option value='{$value}' " . ($importBatch->settings['limit'] == $value ? 'selected' : '') . ">{$value}</option>";
                                                }
                                            @endphp
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    @for ($i = 1; $i <= $totalColumns; $i++)
                                        <th>
                                            <select data-column="{{ $i+1 }}" name="mapping[]" class="form-control" tabindex="{{ $i }}">
                                                <option value=""></option>
                                                @foreach ($fields as $field)
                                                    <option value="{{ $field['name'] }}">
                                                        {{ $field['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </th>
                                    @endfor
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topRows as $rowIndex => $row)
                                    <tr class="top_rows">
                                        <td>{{ $rowIndex }}</td>
                                        @foreach ($row as $key => $value)
                                            <td class="text-truncate">{{ $value }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                @if ($bottomRows)
                                    <tr>
                                        <td class="text-center" colspan="{{ $totalColumns + 1 }}">.....</td>
                                    </tr>
                                    @foreach ($bottomRows as $footerRowIndex => $footerRow)
                                        <tr class="bottom_rows">
                                            <td>{{ $footerRowIndex }}</td>
                                            @foreach ($footerRow as $key => $value)
                                                <td class="text-truncate">{{ $value }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>

                        </table>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6" id="count_info"></div>
                        <div class="col-md-6 text-right" id="mapping_info"></div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn ml-2 btn-primary">Import Data</button>
                        <a href="{{ url($crud->route . '/import') }}" class="btn btn-default">Back</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection


@section('after_styles')
    <style type="text/css">
        table tr th:not(:first-child),
        table tr td:not(:first-child) {
            min-width: 200px;
            max-width: 300px;
        }
    </style>
@endsection

@section('after_scripts')
    <script>

        $(function() {
            $('select[name="header"]').trigger('change');
        });

        $(".toggle-mapping-group").on('click', () => {
            $('input[name="mapping_name"]').val('');
            $('select[name="mapping_id"]').val('');
            $(".mapping-group").toggleClass("d-none");
        });

        $('select[name="mapping_id"]').on('change', function() {
            let selectedOption = $(this).find(':selected');
            let mapping = selectedOption.data('mapping');

            $('input[name="mapping_name"]').val(selectedOption.data('name'));

            $("select[name='mapping[]']").each((i, select) =>
                $(select).find(`option[value="${mapping[i]}"]`).prop('selected', true)
            ).trigger('change');
        });

        $("select[name='mapping[]']").on('change', function () {

            // Add success class to column that are mapps
            let column = $(this).data('column');
            $(`table th:nth-child(${column}), table td:nth-child(${column})`).toggleClass('bg-success', $(this).val() != '')
          
            // Show how many column have been mapped or not mapped
            let mappings = $('select[name="mapping[]"]');  
            let mappingSelected = mappings.find('option[value!=""]:selected');
            let mappingNotSelected = mappings.find('option[value=""]:selected');

            // Remove disabled from all options
            mappings.find('option').prop('disabled', false);

            // Apply disabled on all selected options
            mappingSelected.each(function(i, o) {
                mappings.find('option[value="'+ o.value +'"]').not(o).prop('disabled', true);
            });

            $('#mapping_info').html(`${mappingSelected.length} column(s) mapped. ${mappingNotSelected.length} column(s) not mapped.`);
        });

        let checkIfMappingIsSelected = () => {

            let mappingSelectedCount = $('select[name="mapping[]"] option[value!=""]:selected').length;

            if(mappingSelectedCount > 0)
                return true;
            else {
                new Noty({
                    type: 'error',
                    text: '{!! trans('fournodes.import-operation::import-operation.mapping_required') !!}'
                }).show();
                return false;
            }
        };

        // Listen for change in settings that refresh the page
        $('select[name="sheet"], select[name="limit"]').on('change', function() {   
            window.location.search = new URLSearchParams({
                sheet: $('select[name="sheet"]').val(),
                header: $('select[name="header"]').val(),
                top_offset: $('select[name="top_offset"]').val(),
                bottom_offset: $('select[name="bottom_offset"]').val(),
                limit: $('select[name="limit"]').val(),
            }).toString();
        });

        // Listen for change in settings that modify the view
        $('select[name="header"], select[name="top_offset"]').on('change', function() {
            const header = Number.parseInt($('select[name="header"]').val());
            const topOffset = Number.parseInt($('select[name="top_offset"]').val());
            
            $('table tbody tr.top_rows').removeClass('font-weight-bold').show();
            
            for (let index = 0; index < topOffset; index++) {
                $('table tbody tr').eq(index).hide();
            }
            
            if(header) {
                $('table tbody tr').eq(topOffset).addClass('font-weight-bold');
            }
        });
        
        $('select[name="bottom_offset"]').on('change', function() {
            const bottomOffset = Number.parseInt($('select[name="bottom_offset"]').val());

            $('table tbody tr.bottom_rows').show();
            
            if(bottomOffset > 0) {
                $('table tbody tr.bottom_rows').slice(bottomOffset * -1).hide();
            }

        });

        $('select[name="top_offset"], select[name="bottom_offset"], select[name="mapping[]"]').on('change', function() {
            const total = Number.parseInt($('input[name="total"]').val());
            const topOffset = Number.parseInt($('select[name="top_offset"]').val());
            const bottomOffset = Number.parseInt($('select[name="bottom_offset"]').val());
            const header = Number.parseInt($('select[name="header"]').val());
            const rowsToBeInserted = total - topOffset - bottomOffset - header;

            $('#count_info').html(`${rowsToBeInserted} rows will be inserted`);
        });

    </script>
@endsection

{{-- FIELD CSS - will be loaded in the after_styles section --}}
@push('after_styles')
    <!-- include select2 css-->
    <link href="{{ asset('packages/select2/dist/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('packages/select2-bootstrap-theme/dist/select2-bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
@endpush

{{-- FIELD JS - will be loaded in the after_scripts section --}}
@push('after_scripts')
    <!-- include select2 js-->
    <script src="{{ asset('packages/select2/dist/js/select2.full.min.js') }}"></script>
    @if (app()->getLocale() !== 'en')
        <script src="{{ asset('packages/select2/dist/js/i18n/' . str_replace('_', '-', app()->getLocale()) . '.js') }}"></script>
    @endif
    <script>
        $.fn.modal.Constructor.prototype.enforceFocus = function() {};

        $('select[name="mapping[]"]').select2({
            theme: "bootstrap",
            allowClear: true,
            placeholder: '',
            debug: true,
        });
    </script>
@endpush
