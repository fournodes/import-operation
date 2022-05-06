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
                    <input type="hidden" name="import_batch_id" value="{{ $importBatch->id }}" />
                    @if ($selectedMapping['id'])
                        <input type="hidden" name="mapping_id" value="{{ $selectedMapping['id'] }}" />
                        <input type="hidden" name="mapping_name" value="{{ $selectedMapping['name'] }}" />
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover border-top">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    @foreach ($selectedMapping['mapping'] as $map)
                                        <th class="limit-width {{ $map ? '' : 'd-none' }}">
                                            {{ $map ? $mappingLabels[$map]['label'] : '' }}
                                            <input type="hidden" name="mapping[]" value="{{ $map }}" />
                                        </th>
                                    @endforeach
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $tabIndex = 1; @endphp
                                @foreach ($errorRows as $rowIndex => $errorRow)
                                    @if (isset($errorMessages[$rowIndex]))
                                        <tr>                                            
                                            <td class="align-middle">{{ $rowIndex }}</td>
                                            @foreach ($errorRow as $columnIndex => $columnValue)
                                                <td class="limit-width text-truncate align-middle {{ $selectedMapping['mapping'][$columnIndex] ? '' : 'd-none' }}">
                                                    @if (isset($errorMessages[$rowIndex][$selectedMapping['mapping'][$columnIndex]]))
                                                        <input type="text" name="error_rows[{{ $rowIndex }}][{{ $columnIndex }}]" value="{{ $columnValue }}" class="form-control is-invalid" tabindex="{{ $tabIndex++ }}"/>
                                                        <div class="invalid-feedback">
                                                            @foreach ($errorMessages[$rowIndex][$selectedMapping['mapping'][$columnIndex]] as $error)
                                                                {{ $error }}
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        {{ $columnValue }}
                                                        <input type="hidden" name="error_rows[{{ $rowIndex }}][{{ $columnIndex }}]" value="{{ $columnValue }}" />
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="align-middle text-center">
                                                <a href="#" class="btn btn-sm btn-danger remove_row">
                                                    <i class="la la-times"></i>
                                                    <input type="hidden" name="remove_rows[{{ $rowIndex }}]" value="0">
                                                </a>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                                @if ($moreErrors)
                                    <tr>
                                        <td colspan="100%" class="bg-light font-weight-bold text-danger">
                                            {{ trans('fournodes.import-operation::import-operation.more_error', ['count' => count($errorRows)]) }}
                                        </td>
                                    </tr>
                                @endif
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
        .limit-width {
            min-width: 200px;
            max-width: 300px;
        }

        .del_row {
            text-decoration: line-through;
        }

    </style>
@endsection

@section('after_scripts')
    <script>
        $('.remove_row').on('click', function () {
            const value = Number.parseInt($(this).find('input').val());

            if(value) {
                $(this).removeClass('btn-success').addClass('btn-danger');
                $(this).find('i').removeClass('la-check').addClass('la-times');
                $(this).parents('tr').removeClass('del_row');
                $(this).find('input').val(0);
            }
            else {
                $(this).removeClass('btn-danger').addClass('btn-success');
                $(this).find('i').removeClass('la-times').addClass('la-check');
                $(this).parents('tr').addClass('del_row');
                $(this).find('input').val(1);
            }
        });
    </script>
@endsection
