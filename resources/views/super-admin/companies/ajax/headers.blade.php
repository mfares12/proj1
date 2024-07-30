@push('styles')

    <style>
        .table-stats span {
            border-bottom: 1px dotted #000;
            text-decoration: none;
        }
    </style>
@endpush
<div class="row">
    <div class="col-md-12">
        <x-cards.data :title="__('app.statistics')">
            <label>
                {{__('superadmin.browserDetectDescription')}}
            </label>
            <div class="row">
                <div @class(['col-md-7' => $company->location_details, 'col-md-12' => !$company->location_details])>
                    <x-table class="table-striped table-stats">
                        <x-slot name="thead">
                            <th width="30%">Type</th>
                            <th></th>
                        </x-slot>
                        @if($company->headers)
                            @foreach(json_decode($company->headers,true) as $index=>$head)
                                <tr>
                                    <td>
                                <span data-toggle="tooltip"
                                      data-original-title="{{__('superadmin.browserDetectTooltip.'.$index)}}">
                                    @if(is_bool($head))
                                        {{$index}}
                                    @else
                                        {{ ucwords(preg_replace('/(?<!\ )[A-Z]/', ' $0', $index))}}
                                    @endif
                                </span>
                                    </td>
                                    <td class="text-left pl-20">
                                        @if(is_bool($head))
                                            @if($head)
                                                <i class="fa fa-check-circle text-success" data-toggle="tooltip" title="{{__('app.yes')}}"></i>
                                            @else
                                                <i class="fa fa-times text-danger" data-toggle="tooltip" title="{{__('app.no')}}"></i>
                                            @endif
                                        @else
                                            <strong>{{ $head ?: '-' }}</strong>
                                        @endif
                                    </td>

                                </tr>
                            @endforeach
                            <tr>
                                <td class="">{{__('superadmin.registeredIp')}}</td>
                                <td class="text-left pl-20"><strong>{{ trim($company->register_ip) ?? '-'}}</strong></td>
                            </tr>

                        @else
                            <tr>
                                <td colspan="2">
                                    <x-cards.no-record icon="list" :message="__('messages.noRecordFound')"/>
                                </td>
                            </tr>
                        @endif
                    </x-table>
                </div>

                @if($company->location_details)
                    <div class="col-md-5">
                        <x-table class="table-striped table-stats">
                            <x-slot name="thead">
                                <th>{{ucwords(__('app.location'))}}</th>
                                <th></th>
                            </x-slot>
                            @php($details = json_decode($company->location_details,true))
                            @foreach($details as $index => $head)
                                @continue($index=='driver')
                                <tr>
                                    <td>
                                        <div data-toggle="tooltip">
                                            {{ ucwords(preg_replace('/(?<!\ )[A-Z]/', ' $0', $index))}}
                                        </div>
                                    </td>
                                    <td class="text-left pl-20">
                                        <strong>
                                            @if($index ==='countryName')
                                                <i class="flag-icon flag-icon-{{strtolower($details['isoCode'])}} flag-icon-squared"></i>
                                            @endif
                                            {{ $head ?: '-' }}
                                        </strong>
                                    </td>

                                </tr>
                            @endforeach
                        </x-table>
                    </div>
                @endif
            </div>
        </x-cards.data>
    </div>
</div>

<!-- ROW END -->

