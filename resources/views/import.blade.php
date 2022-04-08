@extends(backpack_view('blank'))

@php
$defaultBreadcrumbs = [
    trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
    $crud->entity_name_plural => url($crud->route),
    trans('backpack::crud.add') => false,
];

// if breadcrumbs aren't defined in the CrudController, use the default breadcrumbs
$breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
    <section class="container-fluid">
        <h2>
            <span class="text-capitalize">{!! trans('fournodes.import-operation::import-operation.import') . ' ' . ($crud->getHeading() ?? $crud->entity_name_plural) !!}</span>
            <!-- <small>{!! $crud->getSubheading() ?? trans('backpack::crud.add') . ' ' . $crud->entity_name !!}.</small> -->

            @if ($crud->hasAccess('list'))
                <small><a href="{{ url($crud->route) }}" class="d-print-none font-sm"><i class="la la-angle-double-{{ config('backpack.base.html_direction') == 'rtl' ? 'right' : 'left' }}"></i> {{ trans('backpack::crud.back_to_all') }} <span>{{ $crud->entity_name_plural }}</span></a></small>
            @endif
        </h2>
    </section>
@endsection

@section('content')
    <div class="row">
        <div class="{{ $crud->getOperationSetting('contentClass') }}">
            <!-- Default box -->

            @include('crud::inc.grouped_errors')

            <form method="post" action="{{ url($crud->route) }}/import" enctype="multipart/form-data">
                {!! csrf_field() !!}

                <!-- load the view from the application if it exists, otherwise load the one in the package -->
                @if (view()->exists('vendor.backpack.crud.form_content'))
                    @include('vendor.backpack.crud.form_content', ['fields' => $crud->fields(), 'action' => 'create'])
                @else
                    @include('crud::form_content', ['fields' => $crud->fields(), 'action' => 'create'])
                @endif

                <div class="form-group">
                    <button type="submit" class="btn btn-success">
                        <span class="la la-link" role="presentation" aria-hidden="true"></span> &nbsp;
                        <span>{{ trans('fournodes.import-operation::import-operation.import_match_button') }}</span>
                    </button>

                    @if (!$crud->hasOperationSetting('showCancelButton') || $crud->getOperationSetting('showCancelButton') == true)
                        <a href="{{ $crud->hasAccess('list') ? url($crud->route) : url()->previous() }}" class="btn btn-default"><span class="la la-ban"></span> &nbsp;{{ trans('backpack::crud.cancel') }}</a>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection
