<!-- ========= All Javascript files linkup ======== -->
<!-- Core JS -->
<!-- build:js public/assets/vendor/js/core.js -->
<script src="{{ asset('public/assets/vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset('public/assets/vendor/libs/popper/popper.js') }}"></script>
<script src="{{ asset('public/assets/vendor/js/bootstrap.js') }}"></script>
<script src="{{ asset('public/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/js/all.min.js"></script>

<script src="{{ asset('public/assets/vendor/js/menu.js') }}"></script>
<!-- endbuild -->

<script src="{{ asset('public/assets/js/admin/sidebar-active-menu.js') }}"></script>

<!-- Vendors JS -->
<script src="{{ asset('public/assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
<!-- Main JS -->
<script src="{{ asset('public/assets/js/main.js') }}"></script>
<script src="{{ asset('public/assets/js/password-toggle.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.30.1/moment.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="{{ asset('public/assets/js/universal.js') }}"></script>
{{-- Global page action lock: button-only spinner + current-page actionable lock (no content overlay) --}}
<script src="{{ asset('public/assets/js/admin/page-action-lock.js') }}"></script>
<script src="{{ asset('public/assets/js/admin/quick-add.js') }}"></script>
<script src="{{ asset('public/assets/js/admin/import-export.js') }}"></script>
<script src="{{ asset('public/assets/js/admin/view-jv-modal.js') }}"></script>
<script src="{{ asset('public/assets/js/admin/stock-consumption-modal.js') }}"></script>
<script src="{{ asset('public/assets/js/admin/global-date-filter.js') }}"></script>
<script src="{{ asset('public/assets/js/admin/barcode-scanner.js') }}"></script>
<script src="{{ asset('public/assets/js/admin/global-search.js') }}"></script>
<script src="https://cdn.datatables.net/2.3.8/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@if (app()->getLocale() !== 'en')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/{{ app()->getLocale() }}.js"></script>
@endif
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@if (app()->getLocale() !== 'en')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/{{ app()->getLocale() }}.js"></script>
@endif

<script>
    const CURRENT_LOCALE = "{{ app()->getLocale() }}";

    $(document).ready(function() {
        $('.datatables').DataTable();
        initGlobalDateFilter();

        if ($('body').data('input-dir') === 'rtl') {
            $('textarea, input[type=text], input[type=search]').not('.ltr-field').attr('dir', 'rtl');
        }
    });

    var url_local = "{{ url('/') }}";
    const CURRENT_YEAR = {{ date('Y') }};
    $('#toggleFilter').on('click', function() {

        $('#filterSection').slideToggle(300);

        $(this).find('i').toggleClass(
            'fa-filter fa-times'
        );
    });

    flatpickr(".datepicker", {
        dateFormat: "{{ session('business_setting.date_format', 'd-m-Y') }}",
        allowInput: true,
        locale: CURRENT_LOCALE !== 'en' ? CURRENT_LOCALE : undefined
    });
</script>
