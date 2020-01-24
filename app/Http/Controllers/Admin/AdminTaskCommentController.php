<?php

namespace App\Http\Controllers\Admin;

use App\Helper\Reply;
use App\Http\Requests\Tasks\StoreTaskComment;
use App\Notifications\TaskCommentClient;
use App\Task;
use App\TaskComment;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class AdminTaskCommentController extends AdminBaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTaskComment $request)
    {
        $comment = new TaskComment();
        $comment->comment = $request->comment;
        $comment->task_id = $request->taskId;
        $comment->user_id = $this->user->id;
        $comment->save();

        $task = Task::with(['project','project.members'])->findOrFail($comment->task_id);

        $notifyUser = User::findOrFail($task->user_id);
        $notifyUser->notify(new \App\Notifications\TaskComment($task, $comment->created_at));

        if ($task->project_id != null) {

            if ($task->project->client_id != null && $task->project->allow_client_notification == 'enable') {
                $notifyUser = User::withoutGlobalScope('active')->findOrFail($task->project->client_id);
                $notifyUser->notify(new TaskCommentClient($task, $comment->created_at));
            }

            $members = User::whereIn('id',$task->project->members->pluck('user_id'))->get();

            Notification::send($members, new \App\Notifications\TaskComment($task, $comment->created_at));
        }

        $this->comments = TaskComment::where('task_id', $request->taskId)->orderBy('id', 'desc')->get();
        $view = view('admin.tasks.task_comment', $this->data)->render();

        return Reply::dataOnly(['status' => 'success', 'view' => $view]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $comment = TaskComment::findOrFail($id);
        $comment_task_id = $comment->task_id;
        $comment->delete();
        $this->comments = TaskComment::where('task_id', $comment_task_id)->orderBy('id', 'desc')->get();
        $view = view('admin.tasks.task_comment', $this->data)->render();

        return Reply::dataOnly(['status' => 'success', 'view' => $view]);
    }
}
