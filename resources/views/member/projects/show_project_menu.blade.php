<div class="white-box">
    <div class="row">
    <div class="col-md-12">
        <nav>
            <ul class="showProjectTabs">
                <li class="projects"><a href="{{ route('member.projects.show', $project->id) }}"><i class="icon-grid"></i> <span>@lang('modules.projects.overview')</span></a>
                </li>

                @if(in_array('employees',$modules))
                <li class="projectMembers"><a href="{{ route('member.project-members.show', $project->id) }}"><i class="icon-people"></i> <span>@lang('modules.projects.members')</span></a></li>
                @endif

                @if(in_array('tasks',$modules))
                <li class="projectTasks"><a href="{{ route('member.tasks.show', $project->id) }}"><i class="ti-layout-list-thumb"></i> <span>@lang('app.menu.tasks')</span></a></li>
                @endif

                <li class="projectFiles"><a href="{{ route('member.files.show', $project->id) }}"><i class="ti-files"></i> <span>@lang('modules.projects.files')</span></a></li>
              
                @if(in_array('timelogs',$modules))
                <li class="projectTimelogs"><a href="{{ route('member.time-log.show-log', $project->id) }}"><i class="ti-alarm-clock"></i> <span>@lang('app.menu.timeLogs')</span></a></li>
                @endif

                
            </ul>
        </nav>
    </div>
    </div>
</div>