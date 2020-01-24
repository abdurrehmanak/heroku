<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\AllTasksDataTable;
use App\Helper\Reply;
use App\Http\Requests\Tasks\StoreTask;
use App\Notifications\NewClientTask;
use App\Notifications\NewTask;
use App\Notifications\TaskCompleted;
use App\Notifications\TaskReminder;
use App\Notifications\TaskUpdated;
use App\Notifications\TaskUpdatedClient;
use App\Project;
use App\ProjectMember;
use App\Task;
use App\TaskboardColumn;
use App\TaskCategory;
use App\TaskFile;
use App\Traits\ProjectProgress;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class ManageAllTasksController extends AdminBaseController
{
    use ProjectProgress;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('app.menu.tasks');
        $this->pageIcon = 'ti-layout-list-thumb';
        $this->middleware(function ($request, $next) {
            if (!in_array('tasks', $this->user->modules)) {
                abort(403);
            }
            return $next($request);
        });
    }

    public function index(AllTasksDataTable $dataTable)
    {
        $this->projects = Project::all();
        $this->clients = User::allClients();
        $this->employees = User::allEmployees();
        $this->taskBoardStatus = TaskboardColumn::all();
        $this->taskCategories = TaskCategory::all();

        return $dataTable->render('admin.tasks.index', $this->data);
    }

    public function edit($id)
    {
        $this->task = Task::findOrFail($id);
        $this->projects   = Project::all();
        $this->employees  = User::allEmployees();
        $this->categories = TaskCategory::all();
        $this->taskBoardColumns = TaskboardColumn::all();
        $completedTaskColumn = TaskboardColumn::where('slug', '!=', 'completed')->first();
        if ($completedTaskColumn) {
            $this->allTasks = Task::where('board_column_id', $completedTaskColumn->id)
                ->where('id', '!=', $id);

            if ($this->task->project_id != '') {
                $this->allTasks = $this->allTasks->where('project_id', $this->task->project_id);
            }

            $this->allTasks = $this->allTasks->get();
        } else {
            $this->allTasks = [];
        }
        return view('admin.tasks.edit', $this->data);
    }

    public function update(StoreTask $request, $id)
    {

        $task = Task::findOrFail($id);
        $oldStatus = TaskboardColumn::findOrFail($task->board_column_id);

        $task->heading = $request->heading;
        if ($request->description != '') {
            $task->description = $request->description;
        }
        $task->start_date = Carbon::createFromFormat($this->global->date_format, $request->start_date)->format('Y-m-d');
        $task->due_date = Carbon::createFromFormat($this->global->date_format, $request->due_date)->format('Y-m-d');
        $task->user_id = $request->user_id;
        $task->task_category_id = $request->category_id;
        $task->priority = $request->priority;
        $task->board_column_id = $request->status;
        $task->dependent_task_id = $request->has('dependent') && $request->dependent == 'yes' && $request->has('dependent_task_id') && $request->dependent_task_id != '' ? $request->dependent_task_id : null;

        $taskBoardColumn = TaskboardColumn::findOrFail($request->status);

        if ($taskBoardColumn->slug == 'completed') {
            $task->completed_on = Carbon::today()->format('Y-m-d');
        } else {
            $task->completed_on = null;
        }

        $task->project_id = $request->project_id;
        $task->save();

        if ($oldStatus->slug == 'incomplete'  && $taskBoardColumn->slug == 'completed') {
            // notify user
            $notifyUser = User::withoutGlobalScope('active')->findOrFail($request->user_id);
            $notifyUser->notify(new TaskCompleted($task));

            if ($task->project_id != null) {
                if ($task->project->client_id != null  && $task->project->allow_client_notification == 'enable') {
                    $notifyClient = User::findOrFail($task->project->client_id);
                    $notifyClient->notify(new TaskCompleted($task));
                }
            }
        } else {
            //Send notification to user
            $notifyUser = User::findOrFail($request->user_id);
            $notifyUser->notify(new TaskUpdated($task));

            if ($task->project_id != null) {
                if ($task->project->client_id != null && $task->project->allow_client_notification == 'enable') {
                    $notifyUser = User::withoutGlobalScope('active')->findOrFail($task->project->client_id);
                    $notifyUser->notify(new TaskUpdatedClient($task));
                }
            }
        }

        //calculate project progress if enabled
        $this->calculateProjectProgress($request->project_id);

        return Reply::dataOnly(['taskID' => $task->id]);

        //        return Reply::redirect(route('admin.all-tasks.index'), __('messages.taskUpdatedSuccessfully'));
    }

    public function destroy(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        // If it is recurring and allowed by user to delete all its recurring tasks
        if ($request->has('recurring') && $request->recurring == 'yes') {
            Task::where('recurring_task_id', $id)->delete();
        }

        // Delete current task
        Task::destroy($id);



        //calculate project progress if enabled
        $this->calculateProjectProgress($task->project_id);

        return Reply::success(__('messages.taskDeletedSuccessfully'));
    }

    public function create()
    {
        $this->projects = Project::all();
        $this->employees = User::allEmployees();
        $this->categories = TaskCategory::all();
        $completedTaskColumn = TaskboardColumn::where('slug', '!=', 'completed')->first();
        if ($completedTaskColumn) {
            $this->allTasks = Task::where('board_column_id', $completedTaskColumn->id)->get();
        } else {
            $this->allTasks = [];
        }
        return view('admin.tasks.create', $this->data);
    }

    public function membersList($projectId)
    {
        $this->members = ProjectMember::byProject($projectId);
        $list = view('admin.tasks.members-list', $this->data)->render();
        return Reply::dataOnly(['html' => $list]);
    }

    public function dependentTaskLists($projectId, $taskId = null)
    {
        $completedTaskColumn = TaskboardColumn::where('slug', '!=', 'completed')->first();
        if ($completedTaskColumn) {
            $this->allTasks = Task::where('board_column_id', $completedTaskColumn->id)
                ->where('project_id', $projectId);

            if ($taskId != null) {
                $this->allTasks = $this->allTasks->where('id', '!=', $taskId);
            }

            $this->allTasks = $this->allTasks->get();
        } else {
            $this->allTasks = [];
        }

        $list = view('admin.tasks.dependent-task-list', $this->data)->render();
        return Reply::dataOnly(['html' => $list]);
    }

    public function store(StoreTask $request)
    {
        DB::beginTransaction();
        $ganttTaskArray = [];
        $gantTaskLinkArray = [];
        $taskBoardColumn = TaskboardColumn::where('slug', 'incomplete')->first();
        $task = new Task();
        $task->heading = $request->heading;
        if ($request->description != '') {
            $task->description = $request->description;
        }
        $task->start_date = Carbon::createFromFormat($this->global->date_format, $request->start_date)->format('Y-m-d');
        $task->due_date = Carbon::createFromFormat($this->global->date_format, $request->due_date)->format('Y-m-d');
        $task->user_id = $request->user_id;
        $task->project_id = $request->project_id;
        $task->task_category_id = $request->category_id;
        $task->priority = $request->priority;
        $task->board_column_id = $taskBoardColumn->id;
        $task->dependent_task_id = $request->has('dependent') && $request->dependent == 'yes' && $request->has('dependent_task_id') && $request->dependent_task_id != '' ? $request->dependent_task_id : null;

        if ($request->board_column_id) {
            $task->board_column_id = $request->board_column_id;
        }
        $task->save();

        // For gantt chart
        if ($request->page_name && $request->page_name == 'ganttChart') {
            $parentGanttId = $request->parent_gantt_id;

            $taskDuration = $task->due_date->diffInDays($task->start_date);
            $taskDuration = $taskDuration + 1;

            $ganttTaskArray[] = [
                'id' => $task->id,
                'text' => $task->heading,
                'start_date' => $task->start_date->format('Y-m-d'),
                'duration' => $taskDuration,
                'parent' => $parentGanttId,
                'users' => [
                    ucwords($task->user->name)
                ],
                'taskid' => $task->id
            ];

            $gantTaskLinkArray[] = [
                'id' => 'link_' . $task->id,
                'source' => $task->dependent_task_id != '' ? $task->dependent_task_id : $parentGanttId,
                'target' => $task->id,
                'type' => $task->dependent_task_id != '' ? 0 : 1
            ];
        }

        // Add repeated task
        if ($request->has('repeat') && $request->repeat == 'yes') {
            $repeatCount = $request->repeat_count;
            $repeatType = $request->repeat_type;
            $repeatCycles = $request->repeat_cycles;
            $startDate = Carbon::createFromFormat($this->global->date_format, $request->start_date)->format('Y-m-d');
            $dueDate = Carbon::createFromFormat($this->global->date_format, $request->due_date)->format('Y-m-d');


            for ($i = 1; $i < $repeatCycles; $i++) {
                $repeatStartDate = Carbon::createFromFormat('Y-m-d', $startDate);
                $repeatDueDate = Carbon::createFromFormat('Y-m-d', $dueDate);

                if ($repeatType == 'day') {
                    $repeatStartDate = $repeatStartDate->addDays($repeatCount);
                    $repeatDueDate = $repeatDueDate->addDays($repeatCount);
                } else if ($repeatType == 'week') {
                    $repeatStartDate = $repeatStartDate->addWeeks($repeatCount);
                    $repeatDueDate = $repeatDueDate->addWeeks($repeatCount);
                } else if ($repeatType == 'month') {
                    $repeatStartDate = $repeatStartDate->addMonths($repeatCount);
                    $repeatDueDate = $repeatDueDate->addMonths($repeatCount);
                } else if ($repeatType == 'year') {
                    $repeatStartDate = $repeatStartDate->addYears($repeatCount);
                    $repeatDueDate = $repeatDueDate->addYears($repeatCount);
                }

                $newTask = new Task();
                $newTask->heading = $request->heading;
                if ($request->description != '') {
                    $newTask->description = $request->description;
                }
                $newTask->start_date = $repeatStartDate->format('Y-m-d');
                $newTask->due_date = $repeatDueDate->format('Y-m-d');
                $newTask->user_id = $request->user_id;
                $newTask->project_id = $request->project_id;
                $newTask->task_category_id = $request->category_id;
                $newTask->priority = $request->priority;
                $newTask->board_column_id = $taskBoardColumn->id;
                $newTask->recurring_task_id = $task->id;
                $newTask->dependent_task_id = $request->has('dependent') && $request->dependent == 'yes' && $request->has('dependent_task_id') && $request->dependent_task_id != '' ? $request->dependent_task_id : null;

                if ($request->board_column_id) {
                    $newTask->board_column_id = $request->board_column_id;
                }

                $newTask->save();

                // For gantt chart
                if ($request->page_name && $request->page_name == 'ganttChart') {
                    $parentGanttId = $request->parent_gantt_id;
                    $taskDuration = $newTask->due_date->diffInDays($newTask->start_date);
                    $taskDuration = $taskDuration + 1;

                    $ganttTaskArray[] = [
                        'id' => $newTask->id,
                        'text' => $newTask->heading,
                        'start_date' => $newTask->start_date->format('Y-m-d'),
                        'duration' => $taskDuration,
                        'parent' => $parentGanttId,
                        'users' => [
                            ucwords($newTask->user->name)
                        ],
                        'taskid' => $newTask->id
                    ];

                    $gantTaskLinkArray[] = [
                        'id' => 'link_' . $newTask->id,
                        'source' => $parentGanttId,
                        'target' => $newTask->id,
                        'type' => 1
                    ];
                }

                $startDate = $newTask->start_date->format('Y-m-d');
                $dueDate = $newTask->due_date->format('Y-m-d');
            }
        }

        //calculate project progress if enabled
        $this->calculateProjectProgress($request->project_id);

        //      Send notification to user
        $notifyUser = User::withoutGlobalScope('active')->findOrFail($request->user_id);
        $notifyUser->notify(new NewTask($task));

        if ($task->project_id != null) {
            if ($task->project->client_id != null && $task->project->allow_client_notification == 'enable') {
                $notifyUser = User::withoutGlobalScope('active')->findOrFail($task->project->client_id);
                $notifyUser->notify(new NewClientTask($task));
            }
        }

        if (!is_null($request->project_id)) {
            $this->logProjectActivity($request->project_id, __('messages.newTaskAddedToTheProject'));
        }
        DB::commit();
        //log search
        $this->logSearchEntry($task->id, 'Task ' . $task->heading, 'admin.all-tasks.edit', 'task');

        if ($request->page_name && $request->page_name == 'ganttChart') {

            return Reply::successWithData(
                'messages.taskCreatedSuccessfully',
                [
                    'tasks' => $ganttTaskArray,
                    'links' => $gantTaskLinkArray
                ]
            );
        }

        if ($request->board_column_id) {
            return Reply::redirect(route('admin.taskboard.index'), __('messages.taskCreatedSuccessfully'));
        }
        return Reply::dataOnly(['taskID' => $task->id]);
        //        return Reply::redirect(route('admin.all-tasks.index'), __('messages.taskCreatedSuccessfully'));
    }

    public function ajaxCreate($columnId)
    {
        $this->projects = Project::all();
        $this->columnId = $columnId;
        $this->employees = User::allEmployees();
        $completedTaskColumn = TaskboardColumn::where('slug', '!=', 'completed')->first();
        if ($completedTaskColumn) {
            $this->allTasks = Task::where('board_column_id', $completedTaskColumn->id)->get();
        } else {
            $this->allTasks = [];
        }

        return view('admin.tasks.ajax_create', $this->data);
    }

    public function remindForTask($taskID)
    {
        $task = Task::with('user')->findOrFail($taskID);

        // Send  reminder notification to user
        $notifyUser = $task->user;
        $notifyUser->notify(new TaskReminder($task));

        return Reply::success('messages.reminderMailSuccess');
    }

    public function show($id)
    {
        $this->task = Task::with('board_column', 'subtasks', 'project')->findOrFail($id);
        $view = view('admin.tasks.show', $this->data)->render();
        return Reply::dataOnly(['status' => 'success', 'view' => $view]);
    }

    public function showFiles($id)
    {
        $this->taskFiles = TaskFile::where('task_id', $id)->get();
        return view('admin.tasks.ajax-file-list', $this->data);
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $projectId
     * @param $hideCompleted
     */
    public function export($startDate, $endDate, $projectId, $hideCompleted)
    {

        $startDate = Carbon::createFromFormat($this->global->date_format, $startDate)->toDateString();
        $endDate = Carbon::createFromFormat($this->global->date_format, $endDate)->toDateString();

        $tasks = Task::leftJoin('projects', 'projects.id', '=', 'tasks.project_id')
            ->join('users', 'users.id', '=', 'tasks.user_id')
            ->join('taskboard_columns', 'taskboard_columns.id', '=', 'tasks.board_column_id')
            ->select('tasks.id', 'projects.project_name', 'tasks.heading', 'users.name', 'users.image', 'taskboard_columns.column_name', 'tasks.due_date');

        if (!is_null($startDate)) {
            $tasks->where(DB::raw('DATE(tasks.`due_date`)'), '>=', $startDate);
        }

        if (!is_null($endDate)) {
            $tasks->where(DB::raw('DATE(tasks.`due_date`)'), '<=', $endDate);
        }

        if ($projectId != 0) {
            $tasks->where('tasks.project_id', '=', $projectId);
        }

        if ($hideCompleted == '1') {
            $tasks->where('tasks.status', '=', 'incomplete');
        }

        $attributes =  ['image', 'due_date'];

        $tasks = $tasks->get()->makeHidden($attributes);

        // Initialize the array which will be passed into the Excel
        // generator.
        $exportArray = [];

        // Define the Excel spreadsheet headers
        $exportArray[] = ['ID', 'Project', 'Title', 'Assigned TO', 'Status', 'Due Date'];

        // Convert each member of the returned collection into an array,
        // and append it to the payments array.
        foreach ($tasks as $row) {
            $exportArray[] = $row->toArray();
        }

        // Generate and return the spreadsheet
        Excel::create('task', function ($excel) use ($exportArray) {

            // Set the spreadsheet title, creator, and description
            $excel->setTitle('Task');
            $excel->setCreator('Worksuite')->setCompany($this->companyName);
            $excel->setDescription('task file');

            // Build the spreadsheet, passing in the payments array
            $excel->sheet('sheet1', function ($sheet) use ($exportArray) {
                $sheet->fromArray($exportArray, null, 'A1', false, false);

                $sheet->row(1, function ($row) {

                    // call row manipulation methods
                    $row->setFont(array(
                        'bold'       =>  true
                    ));
                });
            });
        })->download('xlsx');
    }
}
