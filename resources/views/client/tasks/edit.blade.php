@extends('layouts.client-app')

@section('page-title')
    <div class="row bg-title">
        <!-- .page title -->
        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
            <h4 class="page-title"><i class="{{ $pageIcon }}"></i> {{ $pageTitle }} #{{ $project->id }} - <span
                        class="font-bold">{{ ucwords($project->project_name) }}</span></h4>
        </div>
        <!-- /.page title -->
        <!-- .breadcrumb -->
        <div class="col-lg-9 col-sm-8 col-md-8 col-xs-12">
            <ol class="breadcrumb">
                <li><a href="{{ route('client.dashboard.index') }}">@lang('app.menu.home')</a></li>
                <li><a href="{{ route('client.projects.index') }}">{{ $pageTitle }}</a></li>
                <li class="active">@lang('app.menu.invoices')</li>
            </ol>
        </div>
        <!-- /.breadcrumb -->
    </div>
@endsection

@section('content')

    <div class="row">
        <div class="col-md-12">

            <section>
                <div class="sttabs tabs-style-line">
                    @include('client.projects.show_project_menu')

                    <div class="content-wrap">
                        <section id="section-line-3" class="show">
                            <div class="row">
                                <div class="col-md-12" id="invoices-list-panel">
                                    <div class="white-box">


                                        <ul class="list-group" id="invoices-list">
                                            <li class="list-group-item">
                                                <div class="row font-bold">
                                                    <div class="col-md-10">
                                                        @lang('app.task')
                                                    </div>
                                                    <div class="col-md-2 text-right">
                                                        @lang('app.dueDate')
                                                    </div>
                                                </div>
                                            </li>
                                            @forelse($tasks as $task)
                                                <li class="list-group-item">
                                                    <div class="row">
                                                        <div class="col-md-9">
                                                            <a href="javascript:;" class="text-muted show-task-detail"
                                                               data-task-id="{{ $task->id }}">{{ ucfirst($task->heading) }}</a>
                                                        </div>
                                                        <div class="col-md-3 text-right">
                                                            <span class="@if($task->due_date->isPast()) text-danger @else text-success @endif m-r-10">{{ $task->due_date->format($global->date_format) }}</span>
                                                            {!! ($task->user->image) ? '<img data-toggle="tooltip" data-original-title="' . ucwords($task->user->name) . '" src="' . asset_url('avatar/' . $task->user->image) . '"
                        alt="user" class="img-circle" height="35"> ' : '<img data-toggle="tooltip" data-original-title="' . ucwords($task->user->name) . '" src="' . asset('img/default-profile-2.png') . '"
                        alt="user" class="img-circle" height="35"> ' !!}
                                                        </div>
                                                    </div>
                                                </li>
                                            @empty
                                                <li class="list-group-item">
                                                    <div class="row">
                                                        <div class="col-md-7">
                                                            @lang('messages.noRecordFound')
                                                        </div>
                                                    </div>
                                                </li>
                                            @endforelse
                                        </ul>
                                    </div>
                                </div>

                            </div>
                        </section>

                    </div><!-- /content -->
                </div><!-- /tabs -->
            </section>
        </div>


    </div>
    <!-- .row -->

@endsection

@push('footer-script')
    <script>
        $('.show-task-detail').click(function () {
            $(".right-sidebar").slideDown(50).addClass("shw-rside");

            var id = $(this).data('task-id');
            var url = "{{ route('client.tasks.show',':id') }}";
            url = url.replace(':id', id);

            $.easyAjax({
                type: 'GET',
                url: url,
                success: function (response) {
                    if (response.status == "success") {
                        $('#right-sidebar-content').html(response.view);
                    }
                }
            });
        })

        $('ul.showProjectTabs .projectTasks').addClass('tab-current');
    </script>
@endpush
