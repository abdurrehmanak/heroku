<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\TaskReportDataTable;
use App\Helper\Reply;
use App\Project;
use App\Task;
use App\TaskboardColumn;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class TaskReportController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('app.menu.taskReport');
        $this->pageIcon = 'ti-pie-chart';
    }

    public function index(TaskReportDataTable $dataTable)
    {
        $this->projects = Project::all();
        $this->fromDate = Carbon::today()->subDays(30);
        $this->toDate = Carbon::today();
        $this->employees = User::allEmployees();

        $taskBoardColumn = TaskboardColumn::all();

        $incompletedTaskColumn = $taskBoardColumn->filter(function ($value, $key) {
            return $value->slug == 'incomplete';
        })->first();

        $completedTaskColumn = $taskBoardColumn->filter(function ($value, $key) {
            return $value->slug == 'completed';
        })->first();

        $this->totalTasks = Task::where(DB::raw('DATE(`due_date`)'), '>=', $this->fromDate->format('Y-m-d'))
            ->where(DB::raw('DATE(`due_date`)'), '<=', $this->toDate->format('Y-m-d'))
            ->count();

        $this->completedTasks = Task::where(DB::raw('DATE(`due_date`)'), '>=', $this->fromDate->format('Y-m-d'))
            ->where(DB::raw('DATE(`due_date`)'), '<=', $this->toDate->format('Y-m-d'))
            ->where('tasks.board_column_id', $completedTaskColumn->id)
            ->count();

        $this->pendingTasks = Task::where(DB::raw('DATE(`due_date`)'), '>=', $this->fromDate->format('Y-m-d'))
            ->where(DB::raw('DATE(`due_date`)'), '<=', $this->toDate->format('Y-m-d'))
            ->where('tasks.board_column_id', '<>', $completedTaskColumn->id)
            ->count();

        return $dataTable->render('admin.reports.tasks.index', $this->data);
    }

    public function store(Request $request)
    {
        $taskBoardColumn = TaskboardColumn::all();
        $startDate = Carbon::createFromFormat($this->global->date_format, $request->startDate)->toDateString();
        $endDate = Carbon::createFromFormat($this->global->date_format, $request->endDate)->toDateString();

        $incompletedTaskColumn = $taskBoardColumn->filter(function ($value, $key) {
            return $value->slug == 'incomplete';
        })->first();

        $completedTaskColumn = $taskBoardColumn->filter(function ($value, $key) {
            return $value->slug == 'completed';
        })->first();

        $totalTasks = Task::where(DB::raw('DATE(`due_date`)'), '>=', $startDate)
            ->where(DB::raw('DATE(`due_date`)'), '<=', $endDate);

        if (!is_null($request->projectId)) {
            $totalTasks->where('project_id', $request->projectId);
        }

        if (!is_null($request->employeeId)) {
            $totalTasks->where('user_id', $request->employeeId);
        }

        $totalTasks = $totalTasks->count();

        $completedTasks = Task::where(DB::raw('DATE(`due_date`)'), '>=', $startDate)
            ->where(DB::raw('DATE(`due_date`)'), '<=', $endDate);

        if (!is_null($request->projectId)) {
            $completedTasks->where('project_id', $request->projectId);
        }

        if (!is_null($request->employeeId)) {
            $completedTasks->where('user_id', $request->employeeId);
        }
        $completedTasks = $completedTasks->where('tasks.board_column_id', $completedTaskColumn->id)->count();

        $pendingTasks = Task::where(DB::raw('DATE(`due_date`)'), '>=', $startDate)
            ->where(DB::raw('DATE(`due_date`)'), '<=', $endDate);

        if (!is_null($request->projectId)) {
            $pendingTasks->where('project_id', $request->projectId);
        }

        if (!is_null($request->employeeId)) {
            $pendingTasks->where('user_id', $request->employeeId);
        }

        $pendingTasks = $pendingTasks->where('tasks.board_column_id', '<>', $completedTaskColumn->id)->count();

        return Reply::successWithData(
            __('messages.reportGenerated'),
            ['pendingTasks' => $pendingTasks, 'completedTasks' => $completedTasks, 'totalTasks' => $totalTasks]
        );
    }

    public function export($startDate = null, $endDate = null, $employeeId = null, $projectId = null)
    {
        $startDate = Carbon::createFromFormat($this->global->date_format, $startDate)->toDateString();
        $endDate = Carbon::createFromFormat($this->global->date_format, $endDate)->toDateString();

        $tasks = Task::leftJoin('projects', 'projects.id', '=', 'tasks.project_id')
            ->join('users', 'users.id', '=', 'tasks.user_id')
            ->select('tasks.id', 'projects.project_name', 'tasks.heading', 'users.name', 'tasks.due_date', 'tasks.status');

        if (!is_null($startDate) && $startDate != 0) {
            $tasks->where(DB::raw('DATE(tasks.`due_date`)'), '>=', $startDate);
        }

        if (!is_null($endDate)  && $endDate != 0) {
            $tasks->where(DB::raw('DATE(tasks.`due_date`)'), '<=', $endDate);
        }

        if (!is_null($projectId)  &&  $projectId != 0) {
            $tasks->where('tasks.project_id', '=', $projectId);
        }

        if (!is_null($employeeId)  &&  $employeeId != 0) {
            $tasks->where('tasks.user_id', $employeeId);
        }

        $tasks->get();


        $attributes =  ['due_date'];

        $tasks = $tasks->get()->makeHidden($attributes);

        // Initialize the array which will be passed into the Excel
        // generator.
        $exportArray = [];

        // Define the Excel spreadsheet headers
        $exportArray[] = ['ID', 'Project', 'Title', 'Assigned To', 'Status', 'Due Date'];

        // Convert each member of the returned collection into an array,
        // and append it to the payments array.
        foreach ($tasks as $row) {
            $exportArray[] = $row->toArray();
        }

        // Generate and return the spreadsheet
        Excel::create('Task Report', function ($excel) use ($exportArray) {

            // Set the spreadsheet title, creator, and description
            $excel->setTitle('Task Report');
            $excel->setCreator('Worksuite')->setCompany($this->companyName);
            $excel->setDescription('Task Report file');

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
