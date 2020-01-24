<?php

namespace App\Http\Controllers;

use App\Invoice;
use App\InvoiceItems;
use App\InvoiceSetting;
use App\OfflinePaymentMethod;
use App\PaymentGatewayCredentials;
use App\Project;
use App\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helper\Reply;
use App\Setting;
use App\TaskboardColumn;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('home');
    }

    public function login()
    {
        return redirect(route('login'));
    }

    public function invoice($id)
    {
        $this->pageTitle = __('app.menu.clients');
        $this->pageIcon = 'icon-people';

        $this->invoice = Invoice::with('currency', 'project', 'project.client')->whereRaw('md5(id) = ?', $id)->firstOrFail();
        $this->paidAmount = $this->invoice->getPaidAmount();

        if ($this->invoice->discount > 0) {
            if ($this->invoice->discount_type == 'percent') {
                $this->discount = (($this->invoice->discount / 100) * $this->invoice->sub_total);
            } else {
                $this->discount = $this->invoice->discount;
            }
        } else {
            $this->discount = 0;
        }

        $taxList = array();

        $items = InvoiceItems::whereNotNull('taxes')
            ->where('invoice_id', $this->invoice->id)
            ->get();

        foreach ($items as $item) {
            foreach (json_decode($item->taxes) as $tax) {
                $this->tax = InvoiceItems::taxbyid($tax)->first();
                if ($this->tax) {
                    if (!isset($taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'])) {
                        $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = ($this->tax->rate_percent / 100) * $item->amount;
                    } else {
                        $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + (($this->tax->rate_percent / 100) * $item->amount);
                    }
                }
            }
        }

        $this->taxes = $taxList;

        $this->settings = Setting::findOrFail(1);
        $this->credentials = PaymentGatewayCredentials::first();
        $this->methods = OfflinePaymentMethod::activeMethod();
        $this->invoiceSetting = InvoiceSetting::first();

        return view('invoice', [
            'companyName' => $this->settings->company_name,
            'pageTitle' => $this->pageTitle,
            'pageIcon' => $this->pageIcon,
            'global' => $this->settings,
            'setting' => $this->settings,
            'settings' => $this->settings,
            'invoice' => $this->invoice,
            'paidAmount' => $this->paidAmount,
            'discount' => $this->discount,
            'credentials' => $this->credentials,
            'taxes' => $this->taxes,
            'methods' => $this->methods,
            'invoiceSetting' => $this->invoiceSetting,
        ]);
    }
    public function app()
    {
        $setting = Setting::select('id', 'company_name')->first();

        return ['data' => $setting];
    }

    public function gantt($ganttProjectId)
    {
        $this->settings = Setting::first();
        $this->ganttProjectId = $ganttProjectId;
        $this->project = Project::whereRaw('md5(id) = ?', $ganttProjectId)->first();

        return view('gantt', [
            'ganttProjectId' => $this->ganttProjectId,
            'global' => $this->settings,
            'project' => $this->project
        ]);
    }

    public function ganttData($ganttProjectId)
    {

        $data = array();
        $links = array();

        $projects = Project::select('id', 'project_name', 'start_date', 'deadline', 'completion_percent')
            ->whereRaw('md5(id) = ?', $ganttProjectId)
            ->get();

        $id = 0; //count for gantt ids
        foreach ($projects as $project) {
            $id = $id + 1;
            $projectId = $id;

            // TODO::ProjectDeadline to do
            $projectDuration = 0;
            if ($project->deadline) {
                $projectDuration = $project->deadline->diffInDays($project->start_date);
            }

            $data[] = [
                'id' => $projectId,
                'text' => ucwords($project->project_name),
                'start_date' => $project->start_date->format('Y-m-d H:i:s'),
                'duration' => $projectDuration,
                'progress' => $project->completion_percent / 100,
                'color' => 'grey',
                'textColor' => 'white',
                'project_id' => $project->id
            ];

            $taskBoardColumn = TaskboardColumn::where('slug', 'completed')->first();
            $tasks = Task::projectTasks($project->id);

            foreach ($tasks as $key => $task) {
                $id = $id + 1;

                $taskDuration = $task->due_date->diffInDays($task->start_date);
                $taskDuration = $taskDuration + 1;

                $color = ($taskBoardColumn->id != $task->board_column_id) ? 'blue' : '#1de9b6';

                $data[] = [
                    'id' => $task->id,
                    'text' => ucfirst($task->heading),
                    'start_date' => (!is_null($task->start_date)) ? $task->start_date->format('Y-m-d') : $task->due_date->format('Y-m-d'),
                    'duration' => $taskDuration,
                    'parent' => $projectId,
                    'users' => [
                        ucwords($task->user->name)
                    ],
                    'color' => $color,
                    'taskid' => $task->id
                ];

                $links[] = [
                    'id' => $id,
                    'source' => $task->dependent_task_id != '' ? $task->dependent_task_id : $projectId,
                    'target' => $task->id,
                    'type' => $task->dependent_task_id != '' ? 0 : 1
                ];
            }
        }

        $ganttData = [
            'data' => $data,
            'links' => $links
        ];

        return response()->json($ganttData);
    }


    public function taskDetail($id)
    {
        $this->settings = Setting::first();
        $this->task = Task::with('board_column', 'subtasks', 'project')->findOrFail($id);
        $view = view('task_detail', [
            'task' => $this->task,
            'global' => $this->settings,
        ])->render();
        return Reply::dataOnly(['status' => 'success', 'view' => $view]);
    }
    
}



// Todo::remove this controller
