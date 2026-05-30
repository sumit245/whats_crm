<x-layout-dashboard title="{{__('Create Campaign')}}">

    <link href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/select2/css/select2-bootstrap4.css') }}" rel="stylesheet" />
    @if (session()->has('alert'))
        <x-alert>
            @slot('type', session('alert')['type'])
            @slot('msg', session('alert')['msg'])
        </x-alert>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <x-page-header title="{{ __('Create Campaign') }}"
        subtitle="{{ __('Build and schedule a bulk message campaign') }}"
        :breadcrumb="[__('Campaign'), __('Create')]">
        <a href="{{ route('campaigns') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> {{ __('Back to Campaigns') }}
        </a>
    </x-page-header>

    <style>
        #smartwizard .tab-content {
            overflow: unset !important;
        }
        .campaign-wizard-nav {
            position: sticky;
            bottom: 3.5rem;
            z-index: 1000;
            background: var(--bs-card-bg, #fff);
            padding: 1rem 0 0.5rem;
            margin-top: 1rem !important;
            box-shadow: 0 -6px 16px rgba(0, 0, 0, 0.08);
        }
    </style>

    {{-- wizard --}}
    <div class="row">
        <div class="col-xl-12 mx-auto">
            <div class="card">
                @if (!session()->has('selectedDevice'))
                    <div class="alert alert-danger"> {{__('Please select a device first')}} </div>
                @else
                    <div class="card-body">

                        <!-- SmartWizard html -->
                        <div id="smartwizard" style="height: 100%;">
                            <ul class="nav">
                                <li class="nav-item">
                                    <a class="nav-link" href="#step-1">
                                        <strong>{{__('Step 1')}}</strong> <br />{{__('Create name and preview the sender.')}}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#step-2">
                                        <strong>{{__('Step 2')}}</strong> <br />{{__('Set message and destination')}} </a>
                                </li>
                                <li class="nav-item">
                                    <a onclick="return false;" class="nav-link" href="#step-3">
                                        <strong>{{__('Step 3')}}</strong> <br />{{__('Delay and Campaign type')}}</a>
                                </li>

                            </ul>
                            <div class="tab-content col-md-10 offset-md-1 mt-4">
                                <div id="step-1" class="tab-pane" role="tabpanel" aria-labelledby="step-1">
                                    <div class="form-group">
                                        <label class="form-label" for="campaignName">{{__('Sender Number / Device')}}</label>
                                        <input type="text" class="form-control" id="campaignName" name="sender"
                                            placeholder="{{__('Enter campaign name')}}"
                                            value="{{ session('selectedDevice')['device_body'] }}" disabled>
                                        <input type="hidden" name="device_id" id="device_id"
                                            value="{{ session('selectedDevice')['device_id'] }}">

                                    </div>
                                    <div class="form-group mt-4">
                                        <label class="form-label" for="campaign_name">{{__('Campaign Name')}}</label>
                                        <input type="text" class="form-control" id="campaign_name"
                                            name="campaign_name" placeholder="{{__('Enter campaign name')}}">
                                    </div>
                                </div>
                                <div id="step-2" class="tab-pane" role="tabpanel" aria-labelledby="step-2">

                                    <div class="mb-3 form-group">
                                        <label class="form-label">{{__('Select PhoneBook')}}</label>
                                        <select id="phonebook_id" name="phonebook_id" class="single-select phonebook-option">
											@foreach ($phonebooks as $phonebook)
											  <option value="{{ $phonebook->id }}">{{ $phonebook->name }} ( {{$phonebook->contacts_count }} {{__('Numbers')}} )</option>
											@endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="type" class="form-label">{{__('Type Message')}}</label>
                                        <select name="type" id="type" class="js-states form-control"
                                            tabindex="-1" required>
                                            <option value="" selected disabled>{{__('Select One')}}</option>
                                            <option value="metatemplate">{{__('WhatsApp Template (Meta / HSM)')}}</option>
                                            <option value="text">{{__('Text Message')}}</option>
                                            <option value="media">{{__('Media Message')}}</option>
											<option value="sticker">{{__('Sticker Message')}}</option>
											<option value="location">{{__('Location Message')}}</option>
											<option value="vcard">{{__('VCard Message')}}</option>
                                            <option value="list">{{__('List Message')}} </option>
                                            <option value="button">{{__('Button Message ( Deprecated )')}}</option>
                                            <option value="template">{{__('Template Message ( Deprecated )')}}</option>


                                        </select>
                                    </div>
                                    <div class=" form-group ajaxplace ">

                                    </div>

                                    {{-- Meta HSM template selector + variable mapping --}}
                                    <div id="metaTemplateArea" class="form-group d-none">
                                        <label class="form-label">{{__('Approved Template')}}</label>
                                        <select id="meta_template_select" name="template_id" class="form-select" disabled>
                                            <option value="">{{__('Select a template')}}</option>
                                        </select>
                                        <div class="form-text">{{__('Only Meta-approved templates for the selected device are listed.')}}</div>

                                        <div id="meta_template_preview" class="mt-3 d-none">
                                            <label class="form-label">{{__('Preview')}}</label>
                                            <div class="wa-bubble" id="meta_template_bubble"></div>
                                        </div>

                                        <div id="meta_template_vars" class="mt-3"></div>
                                    </div>
									<div id="loadjs"></div>
                                </div>
                                <div id="step-3" class="tab-pane" role="tabpanel" aria-labelledby="step-3"
                                    style="height : 100%;">
                                    <div class="form-group mt-2">
                                        <label for="" class="form-label">{{__('Delay Per Message (Second)')}}</label>
                                        <input type="number" name="delay" id="delay" class="form-control"
                                            placeholder="{{__('Delay Per Message (Second)')}}" value="10" min="1"
                                            max="60">

                                    </div>
                                    <div class="form-group">
                                        <label for="tipe" class="form-label">{{__('Type')}}</label>
                                        <select name="tipe" id="tipe" class="js-states form-control">
                                            <option value="immediately">{{__('Immediately')}}</option>
                                            <option value="schedule">{{__('Schedule')}}</option>

                                        </select>
                                    </div>
                                    <div class="form-group d-none" id="datetime">
                                        <label for="datetime" class="form-label">{{__('Date Time')}}</label>
                                        <input type="datetime-local" id="datetime2" name="datetime"
                                            class="form-control">
                                    </div>
                                </div>

                            </div>

                        </div>
                        {{-- prev and next button --}}


                        <div class="d-flex justify-content-center gap-2 mt-4 campaign-wizard-nav">
                            <button class="btn btn-info text-white" id="prev-btn" type="button">{{__('Previous')}}</button>
                            <button class="btn btn-info text-white" id="next-btn" type="button">{{__('Next')}}</button>
                            <button class="btn btn-info text-white d-none buttonsubmit" id="finish-btn"
                                type="button">{{__('Create Campaign')}}</button>
                        </div>


                    </div>
            </div>
            @endif
        </div>
    </div>
    {{-- end wizard --}}
	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.3/dist/leaflet.css" />
	<script src="https://unpkg.com/leaflet@1.3.3/dist/leaflet.js"></script>
    <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/js/form-select2.js') }}"></script>
	<script src="https://woody180.github.io/vanilla-javascript-emoji-picker/vanillaEmojiPicker.js"></script>
    <script>
        $(document).ready(function() {

            // Smart Wizard
            // if url have #step-2 or above.. replace with #step-1
            if (window.location.hash === '#step-2' || window.location.hash === '#step-3') {
                window.location.hash = '#step-1';
            }


            $("#smartwizard").smartWizard({
                selected: 0,
                theme: "dots",
                autoAdjustHeight: false,
                transition: {
                    animation: "slide-horizontal", // Effect on navigation, none/fade/slide-horizontal/slide-vertical/slide-swing
                },
                toolbarSettings: {
                    toolbarPosition: "none",
                },
                // disable all anchor
                anchorSettings: {
                    anchorClickable: false,
                },

            });

            function refreshWizardLayout() {
                $(".tab-content").height('auto');
            }
			
			function loadScript(url) {
			  var script = document.createElement('script');
			  script.src = url;
			  document.getElementById("loadjs").appendChild(script); 
			}

			$('#type').on('change', () => {
				const type = $('#type').val();

				// Meta HSM template flow is handled client-side (no form-message ajax).
				if (type === 'metatemplate') {
					$('.ajaxplace').html('');
					$('#metaTemplateArea').removeClass('d-none');
					loadMetaTemplates();
					refreshWizardLayout();
					return;
				}

				// Any other type uses the legacy dynamic form. Reset/clear the
				// template fields so they are not submitted with a non-template campaign.
				$('#metaTemplateArea').addClass('d-none');
				$('#meta_template_select').val('').prop('disabled', true);
				$('#meta_template_vars').html('');
				$('#meta_template_preview').addClass('d-none');

				$.ajax({
					url: `{{ url('/form-message') }}/${type}`,
					
					type: "GET",
					dataType: "html",
					beforeSend: function( xhr ) {
						$('#smartwizard').smartWizard("loader", "show");
					},
					success: (result) => {
						document.getElementById('loadjs').innerHTML = '';
						$(".ajaxplace").html(result);
						refreshWizardLayout();
						loadScript('{{asset("js/text.js")}}');
						loadScript('{{asset("vendor/laravel-filemanager/js/stand-alone-button2.js")}}');
						$('#smartwizard').smartWizard("loader", "hide");
					},
					error: (error) => {
						$('#smartwizard').smartWizard("loader", "hide");
						console.log(error);
					},
				});
			});

			// ── Meta HSM templates ────────────────────────────────────────
			let metaTemplates = [];

			function loadMetaTemplates() {
				const deviceId = $('#device_id').val();
				const $select = $('#meta_template_select');
				$select.prop('disabled', true).html('<option value="">{{ __("Loading...") }}</option>');
				$('#meta_template_vars').html('');
				$('#meta_template_preview').addClass('d-none');

				$.ajax({
					url: `{{ url('/campaign/templates') }}/${deviceId}`,
					type: 'GET',
					dataType: 'json',
					success: (templates) => {
						metaTemplates = templates || [];
						let opts = '<option value="">{{ __("Select a template") }}</option>';
						metaTemplates.forEach(t => {
							opts += `<option value="${t.id}">${t.name} (${t.language})</option>`;
						});
						$select.html(opts).prop('disabled', false);
						if (metaTemplates.length === 0) {
							$select.html('<option value="">{{ __("No approved templates for this device") }}</option>');
						}
					},
					error: () => {
						$select.html('<option value="">{{ __("Failed to load templates") }}</option>');
						toastr['error']('{{ __("Could not load templates") }}');
					},
				});
			}

			function bodyTextOf(template) {
				const comps = template.components || [];
				const body = comps.find(c => (c.type || '').toUpperCase() === 'BODY');
				return body ? (body.text || '') : '';
			}

			$(document).on('change', '#meta_template_select', function () {
				const id = $(this).val();
				const template = metaTemplates.find(t => String(t.id) === String(id));
				$('#meta_template_vars').html('');
				$('#meta_template_preview').addClass('d-none');
				if (!template) return;

				const body = bodyTextOf(template);

				// Preview bubble
				$('#meta_template_bubble').text(body || template.name);
				$('#meta_template_preview').removeClass('d-none');

				// Discover numbered placeholder variables in the body
				const nums = [...body.matchAll(/\{\{(\d+)\}\}/g)].map(m => parseInt(m[1]));
				const unique = [...new Set(nums)].sort((a, b) => a - b);

				if (unique.length === 0) {
					$('#meta_template_vars').html('<div class="alert alert-light border py-2 mb-0"><small>{{ __("This template has no variables.") }}</small></div>');
					refreshWizardLayout();
					return;
				}

				let html = '<label class="form-label">{{ __("Map Template Variables") }}</label>';
			unique.forEach(n => {
				// Build the literal placeholder token in JS so Blade never tries
				// to parse it as a PHP echo (which broke view compilation).
				const token = '{' + '{' + n + '}' + '}';
				html += `
					<div class="row g-2 align-items-center mb-2">
						<div class="col-2"><span class="badge bg-secondary">${token}</span></div>
						<div class="col-5">
							<select name="var_source_${n}" class="form-select form-select-sm var-source" data-index="${n}">
								<option value="name">{{ __("Contact Name") }}</option>
								<option value="number">{{ __("Contact Number") }}</option>
								<option value="static">{{ __("Static text") }}</option>
							</select>
						</div>
						<div class="col-5">
							<input type="text" name="var_static_${n}" class="form-control form-control-sm var-static"
								data-index="${n}" placeholder="{{ __('Static value') }}" disabled>
						</div>
					</div>`;
				});
				$('#meta_template_vars').html(html);
				refreshWizardLayout();
			});

			// Toggle the static text input based on the chosen source
			$(document).on('change', '.var-source', function () {
				const i = $(this).data('index');
				$(`.var-static[data-index='${i}']`).prop('disabled', $(this).val() !== 'static');
			});

            // External Button Events
            $("#prev-btn").on("click", function() {
                $("#smartwizard").smartWizard("prev");
                return true;
            });
            $("#next-btn").on("click", function() {
                let nextSelected = $("#smartwizard").smartWizard("getStepIndex");
                if (validation(nextSelected)) {
                    $("#smartwizard").smartWizard("next");
                    return true;
                }

            });
            // validation 
            function requiredInput(id) {
                
                return true;
            }


            function checkMultipleForm(type, count = 3, template = false) {
                let success = false;
                let firstElement = $(`input[name='${type}[1]']`).val();
                // Periksa apakah ada elemen pertama yang diisi
                if (firstElement === undefined) {
                    toastr['warning'](`{{__("Please add at least one input")}} ${type}`);
                } else {
                    // Periksa apakah semua elemen diisi
                    let isAllFilled = true;
                    for (let i = 1; i <= count; i++) {
                        let element = $(`input[name='${type}[${i}]']`).val();
                        if (element !== undefined && element === '') {
                            isAllFilled = false;
                            break;
                        }
                        if (template) {
                            try {
                                let format = element.split('|');

                                if (format.length < 3 || (format[0] !== 'call' && format[0] !== 'url')) {
                                    toastr['warning'](
                                        `Invalid ${type} ${i} format, format must be like this: call|number|text or url|url|text, first element must be call or url`
                                    );
                                    return false;
                                    break;
                                }
                            } catch (e) {

                            }
                        }
                    }

                    if (isAllFilled) {
                        success = true;
                    } else {
                        toastr['warning'](`{{__("All inputs must be filled")}} ${type}`);
                    }
                }

                return success;
            }



            function validation(step) {
                if (step == 0) {
                    return requiredInput('campaign_name');
                }
                if (step == 1) {
                    let phonebook = $('.phonebook-option').val();
                    const type = $('#type').val();
                    if (phonebook == null) {
                        toastr['warning']('{{__("Please select phonebook")}}');
                        return false;
                    }
                    if (type == 'metatemplate') {
                        if (!$('#meta_template_select').val()) {
                            toastr['warning']('{{__("Please select an approved template")}}');
                            return false;
                        }
                        return true;
                    }
                    if (type == 'text') {
                        return requiredInput('message');
                    }else if (type == 'location') {
                        return requiredInput('latitude');
					}else if (type == 'vcard') {
                        return requiredInput('phone');
                    }else if (type == 'sticker') {
                        let image = $('#thumbnail-sticker').val();
						if (image.length < 5) {
                            toastr['warning']('{{__("Please fill all field needed")}}');
                            return false;
                        }
						return true;
                    } else if (type == 'media') {

                        let image = $('#thumbnail').val();
                        let caption = $('#caption').val();
                        if (image.length < 5) {
                            toastr['warning']('{{__("Please fill all field needed")}}');
                            return false;
                        }
                        return true;
                    } else if (type == 'button') {
                        return requiredInput('message') && checkMultipleForm('button', 5);
                    } else if (type == 'template') {
                        return requiredInput('message') && checkMultipleForm('template', 3, true);
                    } else if (type == 'list') {
                        return requiredInput('message') && requiredInput('buttonlist') && requiredInput(
                            'namelist') && requiredInput('titlelist') && checkMultipleForm('list', 5);
                    } else {
                        toastr['warning']('{{__("Please select message type")}}');
                        return false;
                    }

                }
                return true;
            }

            // handle change tipe campaign
            $('#tipe').change(function() {
                if ($(this).val() == 'schedule') {
                    $('#datetime').removeClass('d-none');
					refreshWizardLayout();
                } else {
                    $('#datetime').addClass('d-none');
					refreshWizardLayout();
                }
            });

            function updateWizardButtons(stepPosition) {
                $("#prev-btn").removeClass("disabled");
                $("#next-btn").removeClass("disabled");
                if (stepPosition === "first") {
                    $("#prev-btn").addClass("disabled");
                    $('.buttonsubmit').addClass('d-none');
                } else if (stepPosition === "last") {
                    $('.buttonsubmit').removeClass('d-none');
                    $("#next-btn").addClass("disabled");
                } else {
                    $('.buttonsubmit').addClass('d-none');
                }
            }

            // on wizard step change
            $("#smartwizard").on(
                "showStep",
                function(e, anchorObject, stepNumber, stepDirection, stepPosition) {
                    updateWizardButtons(stepPosition);
                    refreshWizardLayout();
                }
            );

            updateWizardButtons("first");

            $('.buttonsubmit').click(function() {
                if (!requiredInput('delay')) {
                    return false;
                }
                if ($('#tipe').val() == 'schedule') {
                    if (!requiredInput('datetime2')) {
                        return false;
                    }
                }


                // find input and select in tab-content
                const input = $('.tab-content').find('input');
                const select = $('.tab-content').find('select');
                const textarea = $('.tab-content').find('textarea');
                let data = {};
                var datetimeInput = document.getElementById('datetime2');
                var datetimeValue = datetimeInput.value;
                data['device_id'] = $('#device_id').val();
                data['delay'] = $('#delay').val();
                data['datetime'] = datetimeValue;
                // get name and value from input and select
                $('input[type="text"]').each(function() {
                    data[$(this).attr('name')] = $(this).val();
                });
				$('input[type="tel"]').each(function() {
                    data[$(this).attr('name')] = $(this).val();
                });

                $('input[type="radio"]:checked').each(function() {
                    data[$(this).attr('name')] = $(this).val();
                });
				$('input[type="checkbox"]:checked').each(function() {
                    data[$(this).attr('name')] = $(this).val();
                });
                select.each(function() {
                    data[$(this).attr('name')] = $(this).val();
                });
                textarea.each(function() {
                    data[$(this).attr('name')] = $(this).val();
                });

                const formData = new FormData();
                for (const key in data) {
                    if (key == 'thumbnail')
                        formData.append('image', data[key]);
                    else {
                        formData.append(key, data[key]);
                    }

                }








                $.ajax({
                    url: "{{ url('/campaign/store') }}",
                    method: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(result) {
                        if (result.error) {
                            toastr['error'](result.message);
                        } else {
                            toastr['success'](result.message);
                            // reset wizard form
                            $('#smartwizard').smartWizard("reset");
                            input.each(function() {
                                $(this).val('');
                            });
                            select.each(function() {
                                $(this).val('');
                            });
                            textarea.each(function() {
                                $(this).val('');
                            });
                        }
                    },
                    error: function(err) {
                        console.log(err);
                    }
                });




            });







        });
    </script>
</x-layout-dashboard>
