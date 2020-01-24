<?php

namespace App\Console\Commands;

use App\Notifications\TaskReminder;
use App\Setting;
use App\Task;
use App\TaskboardColumn;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendTaskReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-task-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send task reminders';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        try {
            $this->global_setting = Setting::first();
        } catch (\Exception $e) {
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $now = Carbon::now($this->global_setting->timezone);
        $completedTaskColumn = TaskboardColumn::where('slug', 'completed')->first();

        if ($this->global_setting->before_days > 0) {
            $beforeDeadline = $now->clone()->subdays($this->global_setting->before_days)->format('Y-m-d');
            $tasks = Task::select('id')->where('due_date', $beforeDeadline)->where('board_column_id', '<>', $completedTaskColumn->id)->get();
            foreach ($tasks as $key => $task) {
                $task = Task::with('user')->findOrFail($task->id);
                $notifyUser = $task->user;
                // dd($notifyUser);
                $notifyUser->notify(new TaskReminder($task));
            }
        }
        if ($this->global_setting->after_days > 0) {
            $afterDeadline = $now->clone()->addDays($this->global_setting->after_days)->format('Y-m-d');
        }
    }
}
