<?php

namespace App\Http\Controllers\Admin;

use App\Helper\Reply;
use App\Traits\ModuleVerify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use ZanySoft\Zip\Zip;
use \Nwidart\Modules\Facades\Module;

class CustomModuleController extends AdminBaseController
{
    use ModuleVerify;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('app.menu.moduleSettings');
        $this->pageIcon = 'icon-settings';
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->type = 'custom';
        $this->updateFilePath = config('froiden_envato.tmp_path');
        $this->allModules = Module::all();
        return view('admin.custom-modules.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->type = 'custom';
        $this->updateFilePath = config('froiden_envato.tmp_path');
        return view('admin.custom-modules.install', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        File::put(public_path() . '/install-version.txt', 'complete');

        $filePath = $request->filePath;
        $zip = Zip::open($filePath);

        // extract whole archive
        $zip->extract(base_path().'/Modules');

        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('cache:clear');
        Session::flush();

        //logout user after installing update
        Auth::logout();
        return Reply::success('Installed successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return $this->verifyModulePurchase($id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $module = Module::find($id);

        if ($request->status == 'active') {
            $module->enable();
        } else {
            $module->disable();
        }

        $plugins = \Nwidart\Modules\Facades\Module::allEnabled();
        // dd(array_keys($plugins));

        foreach ($plugins as $plugin) {
            Artisan::call('module:migrate', array($plugin, '--force' => true));
        }

        session(['worksuite_plugins' => array_keys($plugins)]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function verifyingModulePurchase(Request $request)
    {
        $request->validate([
            'purchase_code' => 'required|max:80',
        ]);

        $module = $request->module;
        $purchaseCode = $request->purchase_code;
        return $this->modulePurchaseVerified($module, $purchaseCode);
        
    }

}
