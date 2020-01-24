<?php

namespace App\Http\Controllers\Admin;

use App\Currency;
use App\DataTables\Admin\PaymentsDataTable;
use App\Helper\Reply;
use App\Http\Requests\Payments\ImportPayment;
use App\Http\Requests\Payments\StorePayment;
use App\Http\Requests\Payments\UpdatePayments;
use App\Invoice;
use App\Payment;
use App\Project;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class ManagePaymentsController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('app.menu.payments');
        $this->pageIcon = 'fa fa-money';
        $this->middleware(function ($request, $next) {
            if (!in_array('payments', $this->user->modules)) {
                abort(403);
            }
            return $next($request);
        });
    }

    public function index(PaymentsDataTable $dataTable)
    {
        $this->projects = Project::all();
        $this->clients = User::allClients();
        return $dataTable->render('admin.payments.index', $this->data);
    }

    public function create()
    {
        $this->projects = Project::all();
        $this->currencies = Currency::all();
        return view('admin.payments.create', $this->data);
    }

    public function store(StorePayment $request)
    {
        $payment = new Payment();
        if ($request->project_id != '') {
            $payment->project_id = $request->project_id;
            $payment->currency_id = $request->currency_id;
        } else if ($request->has('invoice_id')) {
            $invoice = Invoice::findOrFail($request->invoice_id);
            $payment->project_id = $invoice->project_id;
            $payment->invoice_id = $invoice->id;
            $payment->currency_id = $invoice->currency->id;
            $paidAmount = $invoice->amountPaid();
        } else {
            $currency = Currency::first();
            $payment->currency_id = $request->currency_id;
        }

        $payment->amount = round($request->amount, 2);
        $payment->gateway = $request->gateway;
        $payment->transaction_id = $request->transaction_id;
        $payment->paid_on =  Carbon::createFromFormat('d/m/Y H:i', $request->paid_on)->format('Y-m-d H:i:s');

        $payment->remarks = $request->remarks;
        $payment->save();

        if ($request->has('invoice_id')) {

            if (($paidAmount + $request->amount) >= $invoice->total) {
                $invoice->status = 'paid';
            } else {
                $invoice->status = 'partial';
            }
            $invoice->save();
        }



        return Reply::redirect(route('admin.payments.index'), __('messages.paymentSuccess'));
    }

    public function destroy($id)
    {
        $payment = Payment::find($id);

        // change invoice status if exists
        if ($payment->invoice) {
            $due = $payment->invoice->amountDue() + $payment->amount;
            if ($due <= 0) {
                $payment->invoice->status = 'paid';
            } else if ($due >= $payment->invoice->total) {
                $payment->invoice->status = 'unpaid';
            } else {
                $payment->invoice->status = 'partial';
            }
            $payment->invoice->save();
        }

        $payment->delete();

        return Reply::success(__('messages.paymentDeleted'));
    }

    public function edit($id)
    {
        $this->projects = Project::all();
        $this->currencies = Currency::all();
        $this->payment = Payment::findOrFail($id);
        return view('admin.payments.edit', $this->data);
    }

    public function update(UpdatePayments $request, $id)
    {

        $payment = Payment::findOrFail($id);
        if ($request->project_id != '') {
            $payment->project_id = $request->project_id;
        }
        $payment->currency_id = $request->currency_id;
        $payment->amount = round($request->amount, 2);
        $payment->gateway = $request->gateway;
        $payment->transaction_id = $request->transaction_id;
        $payment->paid_on = Carbon::createFromFormat('d/m/Y H:i', $request->paid_on)->format('Y-m-d H:i:s');
        $payment->status = 'complete';
        $payment->remarks = $request->remarks;
        $payment->save();

        // change invoice status if exists
        if ($payment->invoice) {
            if ($payment->invoice->amountDue() <= 0) {
                $payment->invoice->status = 'paid';
            } else if ($payment->invoice->amountDue() >= $payment->invoice->total) {
                $payment->invoice->status = 'unpaid';
            } else {
                $payment->invoice->status = 'partial';
            }
            $payment->invoice->save();
        }

        return Reply::redirect(route('admin.payments.index'), __('messages.paymentSuccess'));
    }

    public function payInvoice($invoiceId)
    {
        $this->invoice = Invoice::findOrFail($invoiceId);
        $this->paidAmount = $this->invoice->amountPaid();


        if ($this->invoice->status == 'paid') {
            return "Invoice already paid";
        }

        return view('admin.payments.pay-invoice', $this->data);
    }

    public function importExcel(ImportPayment $request)
    {
        if ($request->hasFile('import_file')) {
            $path = $request->file('import_file')->getRealPath();
            $data = Excel::load($path)->get();

            if ($data->count()) {

                foreach ($data as $key => $value) {

                    if ($request->currency_character) {
                        $amount = substr($value->amount, 1);
                    } else {
                        $amount = substr($value->amount, 0);
                    }

                    $amount = str_replace(',', '', $amount);
                    $amount = str_replace(' ', '', $amount);

                    $arr[] = [
                        'paid_on' => Carbon::createFromFormat($this->global->date_format, $value->date)->format('Y-m-d'),
                        'amount' => $amount,
                        'currency_id' => $this->global->currency_id,
                        'status' => 'complete'
                    ];
                }

                if (!empty($arr)) {
                    DB::table('payments')->insert($arr);
                }
            }
        }

        return Reply::redirect(route('admin.payments.index'), __('messages.importSuccess'));
    }

    public function downloadSample()
    {
        return response()->download(public_path() . '/payment-sample.csv');
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $status
     */
    public function export($startDate, $endDate, $status, $project)
    {

        $payments = Payment::leftJoin('projects', 'projects.id', '=', 'payments.project_id')
            ->join('currencies', 'currencies.id', '=', 'payments.currency_id')
            ->select('payments.id', 'projects.project_name', 'payments.amount', 'currencies.currency_symbol', 'currencies.currency_code', 'payments.status', 'payments.paid_on', 'payments.remarks');

        if ($startDate !== null && $startDate != 'null' && $startDate != '') {
            $payments = $payments->where(DB::raw('DATE(payments.`paid_on`)'), '>=', $startDate);
        }

        if ($endDate !== null && $endDate != 'null' && $endDate != '') {
            $payments = $payments->where(DB::raw('DATE(payments.`paid_on`)'), '<=', $endDate);
        }

        if ($status != 'all' && !is_null($status)) {
            $payments = $payments->where('payments.status', '=', $status);
        }

        if ($project != 'all' && !is_null($project)) {
            $payments = $payments->where('payments.project_id', '=', $project);
        }

        $attributes =  ['amount', 'currency_symbol', 'paid_on'];

        $payments = $payments->orderBy('payments.id', 'desc')->get()->makeHidden($attributes);

        // Initialize the array which will be passed into the Excel
        // generator.
        $exportArray = [];

        // Define the Excel spreadsheet headers
        $exportArray[] = ['ID', 'Project', 'Currency Code', 'Status', 'Remark', 'Amount', 'Paid On'];

        // Convert each member of the returned collection into an array,
        // and append it to the payments array.
        foreach ($payments as $row) {
            $exportArray[] = $row->toArray();
        }

        // Generate and return the spreadsheet
        Excel::create('payment', function ($excel) use ($exportArray) {

            // Set the spreadsheet title, creator, and description
            $excel->setTitle('Payment');
            $excel->setCreator('Worksuite')->setCompany($this->companyName);
            $excel->setDescription('payment file');

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
