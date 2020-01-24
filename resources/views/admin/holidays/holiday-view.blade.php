<div class="col-lg-12 col-sm-12 col-xs-12">
    <div class="vtabs">
        <ul class="nav tabs-vertical">
            @foreach($months as $month)
                <li class="tab nav-item @if($month == $currentMonth) active @endif">
                    <a data-toggle="tab" href="#{{ $month }}" class="nav-link " aria-expanded="@if($month == $currentMonth) true @else false @endif ">
                        <i class="fa fa-calendar"></i> {{ $month }} </a>
                </li>
            @endforeach
        </ul>
        <div class="tab-content p-0">
            @foreach($months as $month)
                <div id="{{$month}}" class="tab-pane @if($month == $currentMonth) active @endif">
                    <div class="panel block4">
                        <div class="panel-heading p-l-5">
                            <div class="caption">
                                <i class="fa fa-calendar"> </i> {{$month}}
                            </div>

                        </div>
                        <div class="portlet-body">
                            <div class="table-scrollable">
                                <table class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th> # </th>
                                        <th> @lang('modules.holiday.date') </th>
                                        <th> @lang('modules.holiday.occasion') </th>
                                        <th> @lang('modules.holiday.day') </th>
                                        <th> @lang('modules.holiday.action') </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @if(isset($holidaysArray[$month]))

                                        @for($i=0;$i<count($holidaysArray[$month]['date']);$i++)

                                            <tr id="row{{ $holidaysArray[$month]['id'][$i] }}">
                                                <td> {{($i+1)}} </td>
                                                <td> {{ $holidaysArray[$month]['date'][$i] }} </td>
                                                <td> {{ $holidaysArray[$month]['ocassion'][$i] }} </td>
                                                <td> {{ $holidaysArray[$month]['day'][$i] }} </td>
                                                <td>
                                                    <button type="button" onclick="del('{{ $holidaysArray[$month]['id'][$i] }}',' {{ $holidaysArray[$month]['date'][$i] }}')" href="#" class="btn btn-danger btn-circle">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endfor
                                    @endif

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>