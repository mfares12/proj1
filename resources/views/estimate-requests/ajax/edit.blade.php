<!-- CREATE ESTIMATE REQUEST START -->
<div class="bg-white rounded b-shadow-4 create-inv">
    <!-- HEADING START -->
    <div class="px-lg-4 px-md-4 px-3 py-3">
        <h4 class="mb-0 f-21 font-weight-normal text-capitalize">@lang('app.estimateDetails')</h4>
    </div>
    <!-- HEADING END -->
    <hr class="m-0 border-top-grey">
    <!-- FORM START -->
    <x-form class="c-inv-form" id="saveInvoiceForm" method="PUT">
        <!-- CURRENCY START -->
        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <div class="col-md-6 col-lg-4">
                <x-forms.number fieldId="estimated_budget" :fieldLabel="__('modules.estimateRequest.estimatedBudget')" fieldName="estimated_budget"
                    :fieldPlaceholder="__('placeholders.price')" :fieldValue="$estimateRequest->estimated_budget"></x-forms.number>
            </div>

            <div class="col-md-6 col-lg-4">
                <x-forms.select fieldId="project_id" fieldName="project_id" :fieldLabel="__('app.project')"
                    search="true">
                    <option value="">--</option>
                    @foreach ($projects as $project)
                        <option data-currency-id="{{ $project->currency_id }}" @selected($estimateRequest->project_id == $project->id)
                             value="{{ $project->id }}">{{ $project->project_name }}
                        </option>
                    @endforeach
                </x-forms.select>
            </div>

            <!-- CURRENCY START -->
            <div class="col-md-6 col-lg-4">
                <div class="form-group c-inv-select my-3">
                    <x-forms.label fieldId="currency_id" :fieldLabel="__('modules.invoices.currency')">
                    </x-forms.label>

                    <div class="select-others height-35 rounded">
                        <select class="form-control select-picker" name="currency_id" id="currency_id">
                            @foreach ($currencies as $currency)
                                <option
                                    @selected ($currency->id == $estimateRequest->currency_id)
                                    value="{{ $currency->id }}">
                                    {{ $currency->currency_code . ' (' . $currency->currency_symbol . ')' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <!-- CURRENCY END -->

            <div class="col-lg-4 col-md-6">
                <x-forms.text :fieldLabel="__('modules.estimateRequest.earlyRequest')" fieldName="early_requirement" fieldId="early_requirement"
                        :fieldPlaceholder="__('placeholders.days')" :popover="__('modules.estimateRequest.earlyRequestToolTip')"
                        :fieldValue="$estimateRequest->early_requirement" />
            </div>

            <div class="col-md-12 my-3">
                <div class="form-group">
                    <x-forms.label fieldId="description" :fieldLabel="__('app.description')" :fieldRequired='true'>
                    </x-forms.label>
                    <div id="description">{!! $estimateRequest->description !!}</div>
                    <textarea name="description" id="description-text" class="d-none"></textarea>
                </div>
            </div>

        </div>

        <!-- CANCEL SAVE SEND START -->
        <x-form-actions class="c-inv-btns">
            <div class="d-flex mb-3">
                <x-forms.button-primary id="save-estimate-request" class="mr-3" icon="check">@lang('app.save')
                </x-forms.button-primary>
                <x-forms.button-cancel :link="route('estimates.index')" class="border-0">@lang('app.cancel')
                </x-forms.button-cancel>
            </div>

        </x-form-actions>
        <!-- CANCEL SAVE SEND END -->

    </x-form>
    <!-- FORM END -->
</div>
<!-- CREATE ESTIMATE REQUEST END -->

<script>
    $(document).ready(function() {
        quillMention(null, '#description');

        $('#save-estimate-request').click(function() {
            let note = document.getElementById('description').children[0].innerHTML;
            document.getElementById('description-text').value = note;

            if (KTUtil.isMobileDevice()) {
                $('.desktop-description').remove();
            } else {
                $('.mobile-description').remove();
            }

            $.easyAjax({
                url: "{{ route('estimate-request.update', $estimateRequest->id) }}",
                container: '#saveInvoiceForm',
                type: "POST",
                blockUI: true,
                file: true,
                data: $('#saveInvoiceForm').serialize(),
                success: function(response) {
                    if (response.status == 'success') {
                        window.location.href = response.redirectUrl;
                    }
                }
            })
        });

        init(RIGHT_MODAL);
    });

</script>
