<?php

namespace App\Observers;

use App\Notice;
use App\UniversalSearch;

class NoticeObserver
{

    public function deleting(Notice $notice){
        $universalSearches = UniversalSearch::where('searchable_id', $notice->id)->where('module_type', 'notice')->get();
        if ($universalSearches){
            foreach ($universalSearches as $universalSearch){
                UniversalSearch::destroy($universalSearch->id);
            }
        }
    }

}
