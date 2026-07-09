<form id="emailSettingForm">
    @csrf

    <div class="row">
        <div class="col-md-12">
            <h4>Email Configuration</h4>
            <hr>
        </div>

        <div class="col-md-6 mb-3">
            <label>Enable Email Notifications</label>
            <select class="form-select select2" name="enable_email_notifications">
                <option value="1"
                    {{ $email_setting->enable_email_notifications == 1 ? 'selected' : '' }}>
                    Yes
                </option>
                <option value="0"
                    {{ $email_setting->enable_email_notifications == 0 ? 'selected' : '' }}>
                    No
                </option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>Mail Mailer<span class="text-danger">*</span></label>
            <select class="form-select select2 email-config-field" name="mail_mailer">
                <option value="">--Select Mailer--</option>
                <option value="smtp" {{ $email_setting->mail_mailer == 'smtp' ? 'selected' : '' }}>SMTP</option>
                <option value="sendmail" {{ $email_setting->mail_mailer == 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                <option value="mailgun" {{ $email_setting->mail_mailer == 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                <option value="ses" {{ $email_setting->mail_mailer == 'ses' ? 'selected' : '' }}>Amazon SES</option>
                <option value="postmark" {{ $email_setting->mail_mailer == 'postmark' ? 'selected' : '' }}>Postmark</option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>SMTP Host</label>
            <input type="text"
                class="form-control email-config-field"
                name="mail_host"
                value="{{ $email_setting->mail_host }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>SMTP Port</label>
            <input type="text"
                class="form-control email-config-field"
                name="mail_port"
                onkeypress="return isNumberKey(event)"
                value="{{ $email_setting->mail_port }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>Username</label>
            <input type="text"
                class="form-control email-config-field"
                name="mail_username"
                value="{{ $email_setting->mail_username }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>Password</label>
            <input type="password"
                class="form-control email-config-field"
                name="mail_password"
                value="{{ $email_setting->mail_password }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>Encryption</label>
            <select class="form-select select2 email-config-field" name="mail_encryption">
                <option value="">None</option>
                <option value="tls" {{ $email_setting->mail_encryption == 'tls' ? 'selected' : '' }}>TLS</option>
                <option value="ssl" {{ $email_setting->mail_encryption == 'ssl' ? 'selected' : '' }}>SSL</option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>From Email Address</label>
            <input type="email"
                class="form-control email-config-field"
                name="mail_from_address"
                value="{{ $email_setting->mail_from_address }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>From Name</label>
            <input type="text"
                class="form-control email-config-field"
                name="mail_from_name"
                value="{{ $email_setting->mail_from_name }}">
        </div>

        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button"
                    class="btn btn-primary"
                    onclick="saveSetting('#emailSettingForm','{{ url('admin/setting/email') }}')">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</form>