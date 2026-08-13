<form id="businessSettingForm">
    @csrf
    <div class="row">
        <div class="col-md-12">
            <h4>Business Setting</h4>
            <hr>
        </div>
        <div class="col-md-6 mb-3">
            <label>Timezone<span class="text-danger">*</span></label>
            <select class="form-select select2" name="timezone">
                <option value="">--Select Timezone--</option>
                @foreach ($timezones as $timezone)
                    <option value="{{ $timezone->name }}"
                        {{ $business_setting->timezone == $timezone->name ? 'selected' : '' }}>
                        (UTC {{ $timezone->offset }})
                        {{ $timezone->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label>Overall Tax Rate (%)<span class="text-danger">*</span></label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control" name="overall_tax_rate"
                value="{{ $business_setting->overall_tax_rate }}">
            <small class="text-muted">Applied to orders paid by cash or any other non-card method.</small>
        </div>
        <div class="col-md-6 mb-3">
            <label>Card Tax Rate (%)<span class="text-danger">*</span></label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control" name="card_tax_rate"
                value="{{ $business_setting->card_tax_rate }}">
            <small class="text-muted">Applied automatically when an order is paid fully by card.</small>
        </div>

        @php
            $date_formats = [
                'd-m-Y' => '30-06-2026',
                'd/m/Y' => '30/06/2026',
                'd.m.Y' => '30.06.2026',

                'm-d-Y' => '06-30-2026',
                'm/d/Y' => '06/30/2026',
                'm.d.Y' => '06.30.2026',

                'Y-m-d' => '2026-06-30',
                'Y/m/d' => '2026/06/30',
                'Y.m.d' => '2026.06.30',

                'd M Y' => '30 Jun 2026',
                'M d, Y' => 'Jun 30, 2026',
                'F d, Y' => 'June 30, 2026',

                'd F Y' => '30 June 2026',

                'j M Y' => '30 Jun 2026',
                'j F Y' => '30 June 2026',
            ];
        @endphp
        <div class="col-md-6 mb-3">
            <label>Date Format<span class="text-danger">*</span></label>
            <select class="form-select select2" name="date_format">
                <option value="">--Select Date Format--</option>
                @foreach ($date_formats as $key => $value)
                    <option value="{{ $key }}" {{ $business_setting->date_format == $key ? 'selected' : '' }}>
                        {{ $key }} ({{ $value }})</option>
                @endforeach
            </select>
        </div>
        @php
            $time_formats = [
                'H:i' => '21:35',
                'H:i:s' => '21:35:40',
                'h:i A' => '09:35 PM',
                'h:i:s A' => '09:35:40 PM',
                'g:i A' => '9:35 PM',
                'g:i:s A' => '9:35:40 PM',
            ];
        @endphp
        <div class="col-md-6 mb-3">
            <label>Time Format<span class="text-danger">*</span></label>
            <select class="form-select select2" name="time_format">
                <option value="">--Select Time Format--</option>
                @foreach ($time_formats as $key => $value)
                    <option value="{{ $key }}"
                        {{ $business_setting->time_format == $key ? 'selected' : '' }}>
                        {{ $key }} ({{ $value }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary"
                    onclick="saveSetting('#businessSettingForm','{{ url('admin/setting/business') }}')">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</form>
