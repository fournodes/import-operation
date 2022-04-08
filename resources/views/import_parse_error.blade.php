@extends(backpack_view('blank'))

@section('header')
    <div class="container-fluid">
        <h2>
            <span class="text-capitalize">{!! trans('fournodes.import-operation::import-operation.error') . ' ' . ($crud->getHeading() ?? $crud->entity_name_plural) . ' ' . trans('fournodes.import-operation::import-operation.import') !!}</span>
            <small id="datatable_info_stack">{!! $crud->getSubheading() ?? '' !!}</small>
        </h2>
    </div>
@endsection

@section('content')
    <div class="row mt-4">
        <div class="card">
            <div class="card-body">
                <form class="form-horizontal" method="POST" action="{{ url($crud->route . '/import/process') }}" novalidate>
                    {{ csrf_field() }}
                    <input type="hidden" name="batch_id" value="{{ $batch_id }}" />
                    @if ($selectedMapping['id'])
                        <input type="hidden" name="mapping_id" value="{{ $selectedMapping['id'] }}" />
                        <input type="hidden" name="mapping_name" value="{{ $selectedMapping['name'] }}" />
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover border-top">
                            <thead>
                                <tr>
                                    @foreach ($selectedMapping['mapping'] as $map)
                                        <th>
                                            {{ ucfirst($map) }}
                                            <input type="hidden" name="mapping[]" value="{{ $map }}" />
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data as $rowIndex => $row)
                                    @if (isset($errors[$rowIndex]))
                                        <tr>
                                            @foreach ($row as $key => $value)
                                                <td class="text-truncate">
                                                    @if (isset($errors[$rowIndex][$selectedMapping['mapping'][$key]]))
                                                        <input type="text" name="data[{{ $rowIndex }}][{{ $key }}]" value="{{ $value }}" class="form-control is-invalid" />
                                                        <div class="invalid-feedback">
                                                            @foreach ($errors[$rowIndex][$selectedMapping['mapping'][$key]] as $error)
                                                                {{ $error }}
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        {{ $value }}
                                                        <input type="hidden" name="data[{{ $rowIndex }}][{{ $key }}]" value="{{ $value }}" />
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>

                        </table>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn ml-2 btn-primary">
                            <span class="la la-file-upload" role="presentation" aria-hidden="true"></span> &nbsp;
                            <span>{{ trans('fournodes.import-operation::import-operation.import_button') }}</span>
                        </button>
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
