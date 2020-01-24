<?php

namespace App\Observers;

use App\Invoice;
use App\UniversalSearch;

class InvoiceObserver
{

    public function deleting(Invoice $invoice){
        $universalSearches = UniversalSearch::where('searchable_id', $invoice->id)->where('module_type', 'invoice')->get();
        if ($universalSearches){
            foreach ($universalSearches as $universalSearch){
                UniversalSearch::destroy($universalSearch->id);
            }
        }
    }

}
