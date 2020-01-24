<?php

namespace App\Observers;

use App\Project;
use App\UniversalSearch;

class ProjectObserver
{

    public function deleting(Project $project){
        $universalSearches = UniversalSearch::where('searchable_id', $project->id)->where('module_type', 'project')->get();
        if ($universalSearches){
            foreach ($universalSearches as $universalSearch){
                UniversalSearch::destroy($universalSearch->id);
            }
        }
    }

}
