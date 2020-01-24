<?php

namespace App\Observers;

use App\CreditNotes;
use App\UniversalSearch;

class CreditNoteObserver
{

    public function deleting(CreditNotes $creditNote){
        $universalSearches = UniversalSearch::where('searchable_id', $creditNote->id)->where('module_type', 'creditNote')->get();
        if ($universalSearches){
            foreach ($universalSearches as $universalSearch){
                UniversalSearch::destroy($universalSearch->id);
            }
        }
    }

}
