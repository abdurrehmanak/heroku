<div class="panel-body bg-owner-reply"  id="replyMessageBox_{{$reply->id}}">

    <div class="row m-b-5">

        <div class="col-xs-2 col-md-1">
            {!!  ($reply->user->image) ? '<img src="'.asset_url('avatar/'.$reply->user->image).'"
                                alt="user" class="img-circle" width="40">' : '<img src="'.asset('img/default-profile-2.png').'"
                                alt="user" class="img-circle" width="40">' !!}
        </div>
        <div class="col-xs-8 col-md-10">
            <h4 class="m-t-0"><a
                        @if($reply->user->hasRole('employee'))
                        href="{{ route('member.employees.show', $reply->user_id) }}"
                        @elseif($reply->user->hasRole('client'))
                        href="{{ route('member.clients.show', $reply->user_id) }}"
                        @endif
                        class="text-inverse">{{ ucwords($reply->user->name) }} <span
                            class="text-muted font-12">{{ $reply->created_at->format($global->date_format .' '.$global->time_format) }}</span></a>
            </h4>

            <div class="font-light">
                {!! ucfirst(nl2br($reply->message)) !!}
            </div>
        </div>
        <div class="col-xs-2 col-md-1">
            <a href="javascript:;" data-toggle="tooltip" data-original-title="Delete"
               data-file-id="{{ $reply->id }}"
               class="btn btn-inverse btn-outline btn-trash sa-params" data-pk="list"><i
                        class="fa fa-trash"></i></a>
        </div>


    </div>
    <!--/row-->
    @if(sizeof($reply->files) > 0)
        <div class="row bg-white" id="list">
            <ul class="list-group" id="files-list">
                @forelse($reply->files as $file)
                    <li class="list-group-item b-none col-md-6">
                        <div class="row">
                            <div class="col-md-9">
                                {{ $file->filename }}
                            </div>
                            <div class="col-md-3">
                                @if($file->external_link != '')
                                    <a target="_blank" href="{{ $file->external_link }}"
                                       data-toggle="tooltip" data-original-title="View"
                                       class="btn btn-info btn-sm btn-outline"><i
                                                class="fa fa-search text-info"></i></a>
                                @elseif(config('filesystems.default') == 'local')
                                    <a target="_blank" href="{{ asset_url('ticket-files/'.$reply->id.'/'.$file->hashname) }}"
                                       data-toggle="tooltip" data-original-title="View"
                                       class="btn btn-info btn-sm btn-outline"><i
                                                class="fa fa-searchv"></i></a>

                                @elseif(config('filesystems.default') == 's3')
                                    <a target="_blank" href="{{ $url.'ticket-files/'.$reply->id.'/'.$file->filename }}"
                                       data-toggle="tooltip" data-original-title="View"
                                       class="btn btn-info btn-sm btn-outline"><i
                                                class="fa fa-search text-info"></i></a>
                                @elseif(config('filesystems.default') == 'google')
                                    <a target="_blank" href="{{ $file->google_url }}"
                                       data-toggle="tooltip" data-original-title="View"
                                       class="btn btn-info btn-sm btn-outline"><i
                                                class="fa fa-search text-info"></i></a>
                                @elseif(config('filesystems.default') == 'dropbox')
                                    <a target="_blank" href="{{ $file->dropbox_link }}"
                                       data-toggle="tooltip" data-original-title="View"
                                       class="btn btn-info btn-sm btn-outline"><i
                                                class="fa fa-search text-info"></i></a>
                                @endif

                                @if(is_null($file->external_link))
                                    &nbsp;&nbsp;
                                    <a href="{{ route('member.ticket-files.download', $file->id) }}"
                                       data-toggle="tooltip" data-original-title="Download"
                                       class="btn btn-inverse btn-sm btn-outline"><i
                                                class="fa fa-download"></i></a>
                                @endif

                                <span class="clearfix font-12 text-muted">{{ $file->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="list-group-item">
                        <div class="row">
                            <div class="col-md-10">
                                @lang('messages.noFileUploaded')
                            </div>
                        </div>
                    </li>
                @endforelse

            </ul>
        </div>
        <!--/row-->
    @endif

</div>
