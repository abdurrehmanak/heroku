<div class="white-box">
    <div class="row">
    <div class="col-md-12">
        <nav>
            <ul class="showProjectTabs">
                <li class="projects">
                    <a href="{{ route('admin.projects.show', $project->id) }}"><i class="icon-grid"></i>
                        <span>@lang('modules.projects.overview')</span></a>
                </li>
                @if(in_array('employees',$modules))
                <li class="projectMembers">
                    <a href="{{ route('admin.project-members.show', $project->id) }}"><i class="icon-people"></i>
                        <span>@lang('modules.projects.members')</span></a>
                </li>
                @endif
                <li class="projectMilestones">
                    <a href="{{ route('admin.milestones.show', $project->id) }}"><i class="icon-flag"></i>
                        <span>@lang('modules.projects.milestones')</span></a>
                </li>
                @if(in_array('tasks',$modules))
                <li class="projectTasks">
                    <a href="{{ route('admin.tasks.show', $project->id) }}"><i class="ti-layout-list-thumb"></i>
                        <span>@lang('app.menu.tasks')</span></a>
                </li>
                @endif
                <li class="projectFiles">
                    <a href="{{ route('admin.files.show', $project->id) }}"><i class="ti-files"></i>
                        <span>@lang('modules.projects.files')</span></a>
                </li>
                @if(in_array('invoices',$modules))
                <li class="projectInvoices">
                    <a href="{{ route('admin.invoices.show', $project->id) }}"><i class="ti-file"></i>
                        <span>@lang('app.menu.invoices')</span></a>
                </li>
                @endif @if(in_array('timelogs',$modules))
                <li class="projectTimelogs">
                    <a href="{{ route('admin.time-logs.show', $project->id) }}"><i class="ti-alarm-clock"></i>
                        <span>@lang('app.menu.timeLogs')</span></a>
                </li>
                @endif
                <li class="burndownChart">
                    <a href="{{ route('admin.projects.burndown-chart', $project->id) }}"><i class="icon-graph"></i>
                        <span>@lang('modules.projects.burndownChart')</span></a>
                </li>
            </ul>
        </nav>
    </div>
    </div>
</div>