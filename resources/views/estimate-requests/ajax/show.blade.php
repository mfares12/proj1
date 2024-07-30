<div class="row">
    <div class="col-sm-12">
        <x-cards.data :title="__('modules.estimateRequest.estimateRequest')" class="mt-4">
            <x-slot name="action">
                <div class="dropdown">
                    <button class="btn f-14 px-0 py-0 text-dark-grey dropdown-toggle" type="button"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-ellipsis-h"></i>
                    </button>

                    <div class="dropdown-menu dropdown-menu-right border-grey rounded b-shadow-4 p-0"
                         aria-labelledby="dropdownMenuLink" tabindex="0">
                        @if ($estimateRequest->status == 'pending')
                            @if (
                                $editEstimatePermission == 'all'
                                || (($editEstimatePermission == 'added' || in_array('client', user_roles())) && $estimateRequest->added_by == user()->id)
                                || (($editEstimatePermission == 'owned' || in_array('client', user_roles())) && $estimateRequest->client_id == user()->id)
                                || (($editEstimatePermission == 'both' || in_array('client', user_roles())) && ($estimateRequest->client_id == user()->id || $estimateRequest->added_by == user()->id))
                            )
                                <a class="dropdown-item openRightModal"
                                    href="{{ route('estimate-request.edit', $estimateRequest->id) }}">@lang('app.edit')</a>

                            @endif
                        @endif
                        @if ($estimateRequest->status != 'accepted')
                            @if ($addEstimatePermission == 'all' || $addEstimatePermission == 'added')
                                <a class="dropdown-item"
                                    href="{{ route('estimates.create', ['estimate-request' => $estimateRequest->id]) }}">@lang('app.create') @lang('app.estimate')</a>
                            @endif
                        @endif
                        @if (
                            $deleteEstimatePermission == 'all'
                            || (($deleteEstimatePermission == 'added' || in_array('client', user_roles())) && $estimateRequest->added_by == user()->id)
                            || (($deleteEstimatePermission == 'owned' || in_array('client', user_roles())) && $estimateRequest->client_id == user()->id)
                            || (($deleteEstimatePermission == 'both' || in_array('client', user_roles())) && ($estimateRequest->client_id == user()->id || $estimateRequest->added_by == user()->id))
                        )
                            <a class="dropdown-item delete-table-row" href="javascript:;" data-estimate-request-id="{{ $estimateRequest->id }}">
                                @lang('app.delete')
                            </a>
                        @endif

                    </div>
                </div>
            </x-slot>
            <x-cards.data-row :label="__('app.clientName')" :value=" $estimateRequest->client->name_salutation" />

            <x-cards.data-row class="p-0 pb-3" :label="__('app.description')" :html="true" :value="$estimateRequest->description" />

            <x-cards.data-row :label="__('modules.estimateRequest.estimatedBudget')"
                :value="currency_format($estimateRequest->estimated_budget, $estimateRequest->currency_id)" />

            <x-cards.data-row :label="__('app.project')"
                :value="$estimateRequest->project ? $estimateRequest->project->project_name : '--'" />

            <x-cards.data-row :label="__('app.estimate')" :html="true" :value="$estimateLink" />
            <x-cards.data-row :label="__('modules.estimateRequest.earlyRequest')"
                :value="$estimateRequest->early_requirement ?? '--' " />
            @if (isset($estimateRequest->reason) && !empty($estimateRequest->reason))
                <x-cards.data-row :label="__('app.reason')" :value="$estimateRequest->reason" />
            @endif

            <div class="col-12 px-0 pb-3 d-block d-lg-flex d-md-flex">
                <p class="mb-0 text-lightest f-14 w-30 d-inline-block text-capitalize">
                    @lang('app.status')</p>
                <p class="mb-0 text-dark-grey f-14 w-70">
                    @if ($estimateRequest->status == 'accepted')
                        <i class="fa fa-circle mr-1 text-dark-green f-10"></i>
                    @elseif ($estimateRequest->status == 'pending')
                        <i class="fa fa-circle mr-1 text-yellow f-10"></i>
                    @elseif ($estimateRequest->status == 'in process')
                        <i class="fa fa-circle mr-1 text-blue f-10"></i>
                        @lang('app.inProcess')
                    @else
                        <i class="fa fa-circle mr-1 text-red f-10"></i>
                    @endif
                    @if ($estimateRequest->status != 'in process')
                        @lang('app.'. $estimateRequest->status)
                    @endif
                </p>
            </div>
        </x-cards.data>
    </div>
</div>

<script>
    $('body').on('click', '.delete-table-row', function() {
        var id = $(this).data('estimate-request-id');
        Swal.fire({
            title: "@lang('messages.sweetAlertTitle')",
            text: "@lang('messages.recoverRecord')",
            icon: 'warning',
            showCancelButton: true,
            focusConfirm: false,
            confirmButtonText: "@lang('messages.confirmDelete')",
            cancelButtonText: "@lang('app.cancel')",
            customClass: {
                confirmButton: 'btn btn-primary mr-3',
                cancelButton: 'btn btn-secondary'
            },
            showClass: {
                popup: 'swal2-noanimation',
                backdrop: 'swal2-noanimation'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                var url = "{{ route('estimate-request.destroy', ':id') }}";
                url = url.replace(':id', id);

                var token = "{{ csrf_token() }}";

                $.easyAjax({
                    type: 'POST',
                    url: url,
                    blockUI: true,
                    data: {
                        '_token': token,
                        '_method': 'DELETE'
                    },
                    success: function(response) {
                        if (response.status == "success") {
                            location.href = "{{ route('estimate-request.index') }}";
                            showTable();
                        }
                    }
                });
            }
        });
    });
</script>
