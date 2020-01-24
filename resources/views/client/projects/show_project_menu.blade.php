<div class="white-box">
    <div class="row">
    <div class="col-md-12">
        <nav>
            <ul class="showProjectTabs">
                <li class="projects"><a href="{{ route('client.projects.show', $project->id) }}"><i class="icon-grid"></i> <span>@lang('modules.projects.overview')</span></a></li>

                @if(in_array('employees',$modules))
                <li><a href="{{ route('client.project-members.show', $project->id) }}"><span>@lang('modules.projects.members')</span></a></li>
                @endif

                @if($project->client_view_task == 'enable' && in_array('tasks',$modules))
                    <li class="projectTasks"><a href="{{ route('client.tasks.edit', $project->id) }}"><i class="ti-layout-list-thumb"></i> <span>@lang('app.menu.tasks')</span></a></li>
                @endif

                <li class="projectFiles"><a href="{{ route('client.files.show', $project->id) }}"><i class="ti-files"></i> <span>@lang('modules.projects.files')</span></a></li>
                
                @if(in_array('timelogs',$modules))
                <li class="projectTimelogs"><a href="{{ route('client.time-log.show', $project->id) }}"><i class="ti-alarm-clock"></i> <span>@lang('app.menu.timeLogs')</span></a></li>
                @endif

                @if(in_array('invoices',$modules))
                <li class="projectInvoices"><a href="{{ route('client.project-invoice.show', $project->id) }}"><i class="ti-file"></i> <span>@lang('app.menu.invoices')</span></a></li>
                @endif
                
            </ul>
        </nav>
    </div>
    </div>
</div>