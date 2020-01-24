<?php

namespace App\DataTables\Admin;

use App\Contract;
use App\DataTables\BaseDataTable;
use App\LogTimeFor;
use App\ProjectTimeLog;
use App\Task;
use App\TaskboardColumn;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;

class AllTimeLogsDataTable extends BaseDataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    protected $timeLogFor;
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('action', function ($row) {
                $action = '<div class="btn-group dropdown m-r-10">
                <button aria-expanded="false" data-toggle="dropdown" class="btn dropdown-toggle waves-effect waves-light" type="button"><i class="ti-more"></i></button>
                <ul role="menu" class="dropdown-menu pull-right">
                  <li><a href="javascript:;" class="edit-time-log"
                  data-toggle="tooltip" data-time-id="' . $row->id . '"  data-original-title="Edit"><i class="fa fa-pencil" aria-hidden="true"></i> ' . trans('app.edit') . '</a></li>
                  <li> <a href="javascript:;" class="sa-params"
                  data-toggle="tooltip" data-time-id="' . $row->id . '" data-original-title="Delete"><i class="fa fa-times" aria-hidden="true"></i> ' . trans('app.delete') . '</a></li>';

                $action .= '</ul> </div>';
                return $action;

            })
            ->editColumn('name', function ($row) {
                return '<a href="' . route('admin.employees.show', $row->user_id) . '" target="_blank" >' . ucwords($row->name) . '</a>';
            })
            ->editColumn('start_time', function ($row) {
                return $row->start_time->timezone($this->global->timezone)->format($this->global->date_format . ' ' . $this->global->time_format);
            })
            ->editColumn('end_time', function ($row) {
                if (!is_null($row->end_time)) {
                    return $row->end_time->timezone($this->global->timezone)->format($this->global->date_format . ' ' . $this->global->time_format);
                } else {
                    return "<label class='label label-success'>" . __('app.active') . "</label>";
                }
            })
            ->editColumn('total_hours', function ($row) {
                $timeLog = intdiv($row->total_minutes, 60) . ' hrs ';

                if (($row->total_minutes % 60) > 0) {
                    $timeLog .= ($row->total_minutes % 60) . ' mins';
                }

                return $timeLog;
            })
            ->addColumn('earnings', function ($row) {
                if (is_null($row->hourly_rate)) {
                    return '--';
                }
                $hours = intdiv($row->total_minutes, 60);

                $earning = round($hours * $row->hourly_rate);

                return $this->global->currency->currency_symbol . $earning . ' (' . $this->global->currency->currency_code . ')';
            })
            ->editColumn('project_name', function ($row) {

                if ($this->logTimeFor != null && $this->logTimeFor->log_time_for == 'task') {
                    return ucfirst($row->project_name);
                } else {
                    return '<a href="' . route('admin.projects.show', $row->project_id) . '">' . ucfirst($row->project_name) . '</a>';
                }
            })
            ->addIndexColumn()
            ->rawColumns(['end_time', 'action', 'project_name', 'name'])
            ->removeColumn('project_id')
            ->removeColumn('total_minutes')
            ->removeColumn('task_id');

    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Product $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(ProjectTimeLog $model)
    {
        $request = $this->request();

        $startDate = Carbon::createFromFormat($this->global->date_format, $request->startDate)->toDateString();
        $endDate = Carbon::createFromFormat($this->global->date_format, $request->endDate)->toDateString();
        $projectId = $request->projectId;
        $employee = $request->employee;

        $projectName = 'projects.project_name';
        $model = $model->join('users', 'users.id', '=', 'project_time_logs.user_id')
            ->join('employee_details', 'users.id', '=', 'employee_details.user_id');

        $this->timeLogFor = LogTimeFor::first();

        if ($this->logTimeFor != null && $this->logTimeFor->log_time_for == 'task') {
            $model = $model->join('tasks', 'tasks.id', '=', 'project_time_logs.task_id');
            $projectName = 'tasks.heading as project_name';
        } else {
            $model = $model->join('projects', 'projects.id', '=', 'project_time_logs.project_id');
        }

        $model = $model->select('project_time_logs.id', $projectName, 'project_time_logs.start_time', 'project_time_logs.end_time', 'project_time_logs.total_hours', 'project_time_logs.total_minutes', 'project_time_logs.memo', 'project_time_logs.user_id', 'project_time_logs.project_id', 'project_time_logs.task_id', 'users.name', 'employee_details.hourly_rate');


        if (!is_null($startDate)) {
            $model->where(DB::raw('DATE(project_time_logs.`start_time`)'), '>=', $startDate);
        }

        if (!is_null($endDate)) {
            $model->where(DB::raw('DATE(project_time_logs.`end_time`)'), '<=', $endDate);
        }

        if (!is_null($employee) && $employee !== 'all') {
            $model->where('project_time_logs.user_id', $employee);
        }

        if (!is_null($projectId) && $projectId !== 'all') {
            if ($this->logTimeFor != null && $this->logTimeFor->log_time_for == 'task') {
                $model->where('project_time_logs.task_id', '=', $projectId);
            } else {
                $model->where('project_time_logs.project_id', '=', $projectId);
            }
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
            ->setTableId('all-time-logs-table')
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
                   window.LaravelDataTables["all-time-logs-table"].buttons().container()
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
            '#' => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false ],
            $this->timeLogFor == 'task' ? __('app.task') : __('app.project') => ['data' => 'project_name', 'name' => 'projects.project_name'],
            __('app.menu.employees')  => ['data' => 'name', 'name' => 'users.name'],
            __('modules.timeLogs.startTime') => ['data' => 'start_time', 'name' => 'start_time'],
            __('modules.timeLogs.endTime') => ['data' => 'end_time', 'name' => 'end_time'],
            __('modules.timeLogs.totalHours') => ['data' => 'total_hours', 'name' => 'total_hours'],
            __('app.earnings') => ['data' => 'earnings', 'name' => 'earnings'],
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->width(150)
                ->addClass('text-center')
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'All_time_log_' . date('YmdHis');
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
