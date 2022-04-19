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
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mapping-group d-none">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">New Mapping</span>
                                </div>
                                <input type="text" name="mapping_name" class="form-control" placeholder="Enter Mapping Name">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-default toggle-mapping-group"><i class="la la-times"></i></button>
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
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    @for ($i = 1; $i <= count($rows[0]); $i++)
                                        <th>
                                            <select data-column="{{ $i }}" name="mapping[]" class="form-control" tabindex="{{ $i }}">
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
                                @if ($importBatch->headers)
                                    <tr>
                                        @foreach ($importBatch->headers as $key => $value)
                                            <th>{{ $value }}</th>
                                        @endforeach
                                    </tr>
                                @endif
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>
                                        @foreach ($row as $key => $value)
                                            <td class="text-truncate">{{ $value }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>

                        </table>
                    </div>

                    <div class="mt-3" id="table_info"></div>

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
        table tr th,
        table tr td {
            min-width: 200px;
            max-width: 300px;
        }

    </style>
@endsection

@section('after_scripts')
    <script>

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

            $('#table_info').html(`${mappingSelected.length} column(s) mapped. ${mappingNotSelected.length} column(s) not mapped.`);
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
