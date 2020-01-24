<?php

namespace App\Observers;

use App\Lead;
use App\UniversalSearch;

class LeadObserver
{

    public function deleted(Lead $lead){
        UniversalSearch::where('searchable_id', $lead->id)->where('module_type', 'lead')->delete();
//        if ($universalSearches){
//            foreach ($universalSearches as $universalSearch){
//                UniversalSearch::destroy($universalSearch->id);
//            }
//        }
    }

}
