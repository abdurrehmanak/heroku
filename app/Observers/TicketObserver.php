<?php

namespace App\Observers;

use App\Ticket;
use App\UniversalSearch;

class TicketObserver
{

    public function deleting(Ticket $ticket){
        $universalSearches = UniversalSearch::where('searchable_id', $ticket->id)->where('module_type', 'ticket')->get();
        if ($universalSearches){
            foreach ($universalSearches as $universalSearch){
                UniversalSearch::destroy($universalSearch->id);
            }
        }
    }

}
