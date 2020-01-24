<?php

namespace App\DataTables\Admin;

use App\DataTables\BaseDataTable;
use App\Estimate;
use App\Expense;
use App\Invoice;
use App\Notice;
use App\Payment;
use App\Task;
use App\TaskboardColumn;
use App\Ticket;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;

class TaskReportDataTable extends BaseDataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->editColumn('due_date', function ($row) {
                if ($row->due_date->isPast()) {
                    return '<span class="text-danger">' . $row->due_date->format($this->global->date_format) . '</span>';
                }
                return '<span class="text-success">' . $row->due_date->format($this->global->date_format) . '</span>';
            })
            ->editColumn('name', function ($row) {
                return ($row->image) ? '<img src="' . asset_url('avatar/' . $row->image) . '"
                                                            alt="user" class="img-circle" width="30" height="30"> ' . ucwords($row->name) : '<img src="' . asset('img/default-profile-2.png') . '"
                                                            alt="user" class="img-circle" width="30" height="30"> ' . ucwords($row->name);
            })
            ->editColumn('heading', function ($row) {
                return '<a href="javascript:;" data-task-id="' . $row->id . '" class="show-task-detail">' . ucfirst($row->heading) . '</a>';
            })
            ->editColumn('status', function ($row) {
                return '<label class="label" style="background-color: ' . $row->label_color . '">' . $row->column_name . '</label>';
            })
            ->editColumn('project_name', function ($row) {
                if (is_null($row->project_id)) {
                    return "";
                }
                return '<a href="' . route('admin.projects.show', $row->project_id) . '">' . ucfirst($row->project_name) . '</a>';
            })
            ->rawColumns(['status', 'project_name', 'due_date', 'name', 'heading'])
            ->removeColumn('project_id')
            ->removeColumn('image')
            ->addIndexColumn();

    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Product $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Task $model)
    {
        $request = $this->request();
        $startDate = $request->startDate;
        $endDate = $request->endDate;
        $employeeId = $request->employeeId;
        $projectId = $request->projectId;

        $startDate = Carbon::createFromFormat($this->global->date_format, $startDate)->toDateString();
        $endDate = Carbon::createFromFormat($this->global->date_format, $endDate)->toDateString();

        $model = $model->leftJoin('projects', 'projects.id', '=', 'tasks.project_id')
            ->join('users', 'users.id', '=', 'tasks.user_id')
            ->join('taskboard_columns', 'taskboard_columns.id', '=', 'tasks.board_column_id')
            ->select('tasks.id', 'projects.project_name', 'tasks.heading', 'users.name', 'users.image', 'tasks.due_date', 'tasks.status', 'tasks.project_id', 'taskboard_columns.column_name', 'taskboard_columns.label_color');

        if (!is_null($startDate)) {
            $model->where(DB::raw('DATE(tasks.`due_date`)'), '>=', $startDate);
        }

        if (!is_null($endDate)) {
            $model->where(DB::raw('DATE(tasks.`due_date`)'), '<=', $endDate);
        }

        if ($projectId != 0) {
            $model->where('tasks.project_id', '=', $projectId);
        }

        if ($employeeId != 0) {
            $model->where('tasks.user_id', $employeeId);
        }

        return $model;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('task-report-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            ->orderBy(0)
            ->destroy(true)
            ->responsive(true)
            ->serverSide(true)
            ->stateSave(true)
            ->processing(true)
            ->language(__("app.datatable"))
            ->buttons(
                Button::make(['extend'=> 'export','buttons' => ['excel', 'csv']])
            )
            ->parameters([
                'initComplete' => 'function () {
                   window.LaravelDataTables["task-report-table"].buttons().container()
                    .appendTo( ".bg-title .text-right")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $("body").tooltip({
                        selector: \'[data-toggle="tooltip"]\'
                    })
                }',
            ]);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            ' #' => ['data' => 'DT_RowIndex', 'orderable' =>false, 'searchable' => false ],
            __('app.project')  => ['data' => 'project_name', 'name' => 'projects.project_name'],
            __('app.title') => ['data' => 'heading', 'name' => 'heading'],
            __('modules.tasks.assignTo') => ['data' => 'name', 'name' => 'users.name'],
            __('app.dueDate') => ['data' => 'due_date', 'name' => 'due_date'],
            __('app.status') => ['data' => 'status', 'name' => 'status'],
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'Task report_' . date('YmdHis');
    }

    public function pdf()
    {
        set_time_limit(0);
        if ('snappy' == config('datatables-buttons.pdf_generator', 'snappy')) {
            return $this->snappyPdf();
        }

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('datatables::print', ['data' => $this->getDataForPrint()]);

        return $pdf->download($this->getFilename() . '.pdf');
    }
}
