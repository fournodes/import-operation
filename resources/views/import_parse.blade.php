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
                <form class="form-horizontal" method="POST" action="{{ url($crud->route . '/import/process') }}">
                    {{ csrf_field() }}
                    <input type="hidden" name="batch_id" value="{{ $batch->id }}" />
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
                                    @for ($i = 0; $i < count($rows[0]); $i++)
                                        <th>
                                            <select name="mapping[]" class="form-control">
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
                                @if ($headers)
                                    <tr>
                                        @foreach ($headers as $key => $value)
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
            min-width: 150px;
            max-width: 300px;
        }

    </style>
@endsection

@section('after_scripts')
    <script>
        $(".toggle-mapping-group").click(() => {
            $('input[name="mapping_name"]').val('');
            $('select[name="mapping_id"]').val('');
            $(".mapping-group").toggleClass("d-none");
        });

        $('select[name="mapping_id"]').change(function() {
            let selectedOption = $(this).find(':selected');
            let mapping = selectedOption.data('mapping');

            $('input[name="mapping_name"]').val(selectedOption.data('name'));

            $("select[name='mapping[]']").each((i, select) =>
                $(select).find(`option[value="${mapping[i]}"]`).prop('selected', true)
            );
        });
    </script>
@endsection
