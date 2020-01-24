<?php

namespace App\Observers;

use App\Task;
use App\UniversalSearch;

class TaskObserver
{

    public function saving(Task $task)
    {
//        $user = auth()->user();
        // Cannot put in creating, because saving is fired before creating. And we need company id for check bellow
//        if ($user) {
//            $task->created_by = $user->id;
//        }
    }
    public function creating(Task $task)
    {
        $user = auth()->user();
//         Cannot put in creating, because saving is fired before creating. And we need company id for check bellow
        if ($user) {
            $task->created_by = $user->id;
        }
    }

    public function deleting(Task $task){
        $universalSearches = UniversalSearch::where('searchable_id', $task->id)->where('module_type', 'task')->get();
        if ($universalSearches){
            foreach ($universalSearches as $universalSearch){
                UniversalSearch::destroy($universalSearch->id);
            }
        }
    }

}
