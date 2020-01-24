<div class="rpanel-title"> @lang('app.task') <span><i class="ti-close right-side-toggle"></i></span> </div>
<div class="r-panel-body">

    <div class="row">
        <div class="row">
            <div class="col-xs-12 m-b-10">
                <label class="label" style="background-color: {{ $task->board_column->label_color }}">{{ $task->board_column->column_name }}</label>
            </div>
            <div class="col-xs-12">
                <h5>{{ ucwords($task->heading) }}
                    @if($task->task_category_id)
                        <label class="label label-default text-dark m-l-5 font-light">{{ ucwords($task->category->category_name) }}</label>
                    @endif

                    <label class="m-l-5 font-light label
                    @if($task->priority == 'high')
                            label-danger
@elseif($task->priority == 'medium') label-warning @else label-success @endif
                            ">
                        <span class="text-dark">@lang('modules.tasks.priority') ></span>  {{ ucfirst($task->priority) }}
                    </label>


                </h5>
                @if(!is_null($task->project_id))
                    <p><i class="icon-layers"></i> {{ ucfirst($task->project->project_name) }}</p>
                @endif

            </div>
        </div>
        <div class="col-xs-6 col-md-3 font-12 m-t-10">
            <label class="font-12" for="">@lang('modules.tasks.assignTo')</label><br>
            @if($task->user->image)
                <img src="{{ asset_url('avatar/'.$task->user->image) }}" class="img-circle" width="25" height="25" alt="">
            @else
                <img src="{{ asset('img/default-profile-2.png') }}" class="img-circle" width="25" height="25" alt="">
            @endif

            {{ ucwords($task->user->name) }}
        </div>
        @if($task->create_by)
            <div class="col-xs-6 col-md-3 font-12 m-t-10">
                <label class="font-12" for="">@lang('modules.tasks.assignBy')</label><br>
                @if($task->create_by->image)
                    <img src="{{ asset_url('avatar/'.$task->create_by->image) }}" class="img-circle" width="25" height="25" alt="">
                @else
                    <img src="{{ asset('img/default-profile-2.png') }}" class="img-circle" width="25" height="25" alt="">
                @endif

                {{ ucwords($task->create_by->name) }}
            </div>
        @endif

        @if($task->start_date)
            <div class="col-xs-6 col-md-3 font-12 m-t-10">
                <label class="font-12" for="">@lang('app.startDate')</label><br>
                <span class="text-success" >{{ $task->start_date->format($global->date_format) }}</span><br>
            </div>
        @endif
        <div class="col-xs-6 col-md-3 font-12 m-t-10">
            <label class="font-12" for="">@lang('app.dueDate')</label><br>
            <span @if($task->due_date->isPast()) class="text-danger" @endif>
                {{ $task->due_date->format($global->date_format) }}
            </span>
            <span style="color: {{ $task->board_column->label_color }}" id="columnStatus"> {{ $task->board_column->column_name }}</span>

        </div>
        <div class="col-xs-12 task-description b-all p-10 m-t-20">
            {!! ucfirst($task->description) !!}
        </div>
 
        <div class="col-xs-12 m-t-5">
            <h5>@lang('modules.tasks.subTask')</h5>
            <ul class="list-group" id="sub-task-list">
                @foreach($task->subtasks as $subtask)
                    <li class="list-group-item row">
                        <div class="col-xs-12">
                            <div>
                                @if ($subtask->status != 'complete')
                                    {{ ucfirst($subtask->title) }}
                                @else
                                    <span style="text-decoration: line-through;">{{ ucfirst($subtask->title) }}</span>
                                @endif
                            </div>
                            @if($subtask->due_date)<span class="text-muted m-l-5 font-12"> - @lang('modules.invoices.due'): {{ $subtask->due_date->format($global->date_format) }}</span>@endif
                        </div>

                       
                    </li>
                @endforeach
            </ul>
        </div>
  
        <div class="col-xs-12 m-t-15 b-b">
            <h5>@lang('modules.tasks.comment')</h5>
        </div>

        <div class="col-xs-12" id="comment-container">
            <div id="comment-list">
                @forelse($task->comments as $comment)
                    <div class="row b-b m-b-5 font-12">
                        <div class="col-xs-12">
                            <h5>{{ ucwords($comment->user->name) }} <span class="text-muted font-12">{{ ucfirst($comment->created_at->diffForHumans()) }}</span></h5>
                        </div>
                        <div class="col-xs-12">
                            {!! ucfirst($comment->comment)  !!}
                        </div>
                        
                    </div>
                @empty
                    <div class="col-xs-12">
                        @lang('messages.noRecordFound')
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</div>
