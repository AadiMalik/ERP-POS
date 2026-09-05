@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            {{ __('transfer_notes.title') }}
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>

                </div>
                <div class="d-flex gap-2">
                    @include('admin.partials.import-export-buttons', [
                        'importExportModule' => 'transfer-note',
                        'importExportLabel' => __('transfer_notes.title'),
                        'importExportRefreshFn' => 'initDataTabletransfer_note_table',
                        'importExportExportParamsSelector' => '#business_id',
                    ])
                    <a href="{{ url('admin/transfer-note/create') }}" class="btn btn-primary rounded-pill">
                        <i class="fa fa-plus"></i>
                        {{ __('common.add_new') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom" style="display:none;">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="form-label">{{ __('common.business') }}</label>
                                <select id="business_id" class="form-select">
                                    <option value="">{{ __('common.all_businesses') }}</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}">{{ isset($item->code) ? $item->code : '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">{{ __('transfer_notes.source_warehouse') }}</label>
                            <select id="source_warehouse_id" class="form-select">
                                <option value="">{{ __('common.all_warehouses') }}</option>
                                @foreach ($warehouses as $item)
                                    <option value="{{ $item->warehouse_id }}">{{ $item->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('transfer_notes.destination_warehouse') }}</label>
                            <select id="destination_warehouse_id" class="form-select">
                                <option value="">{{ __('common.all_warehouses') }}</option>
                                @foreach ($warehouses as $item)
                                    <option value="{{ $item->warehouse_id }}">{{ $item->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.status') }}</label>
                            <select id="status" class="form-select">
                                <option value="">{{ __('common.all_statuses') }}</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.date') }}</label>
                            @include('admin.partials.date_filter')
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">
                                {{ __('common.search') }}
                            </button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">
                                {{ __('common.reset') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive p-4">
                    <table id="transfer_note_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('transfer_notes.transfer_no') }}</th>
                                <th>{{ __('common.date') }}</th>
                                <th>{{ __('transfer_notes.source_warehouse') }}</th>
                                <th>{{ __('transfer_notes.destination_warehouse') }}</th>
                                <th>{{ __('common.products') }}</th>
                                <th>{{ __('common.total_value') }}</th>
                                <th>{{ __('common.status') }}</th>
                                <th>{{ __('common.business') }}</th>
                                <th>{{ __('common.action') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
        @include('admin.partials.import-export-modal')

        <div class="modal fade" id="receiveTransferModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('transfer_notes.receive_transfer') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-2">Enter the quantity actually received for each product. Defaults to
                            the full remaining quantity - reduce it to receive partially.</p>
                        <div class="table-responsive">
                            <table class="table" id="receiveTransferTable">
                                <thead>
                                    <tr>
                                        <th>{{ __('common.product') }}</th>
                                        <th>{{ __('common.variation') }}</th>
                                        <th>{{ __('common.unit') }}</th>
                                        <th class="text-end">{{ __('transfer_notes.sent') }}</th>
                                        <th class="text-end">{{ __('transfer_notes.already_received') }}</th>
                                        <th class="text-end">{{ __('transfer_notes.remaining') }}</th>
                                        <th style="width:140px;">{{ __('transfer_notes.receive_now') }}</th>
                                        <th style="width:220px;">{{ __('common.serial_numbers') }}</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="button" class="btn btn-primary" id="submitReceiveTransfer">{{ __('transfer_notes.receive') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="sendTransferModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('transfer_notes.send_transfer') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-2">This transfer note includes serial-tracked product(s) - select which
                            specific units are being sent for each.</p>
                        <div class="table-responsive">
                            <table class="table" id="sendTransferTable">
                                <thead>
                                    <tr>
                                        <th>{{ __('common.product') }}</th>
                                        <th>{{ __('common.variation') }}</th>
                                        <th class="text-end">{{ __('common.qty') }}</th>
                                        <th style="width:220px;">{{ __('common.serial_numbers') }}</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="button" class="btn btn-primary" id="submitSendTransfer">{{ __('transfer_notes.send') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="tnSerialPickerModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('transfer_notes.select_serial_numbers') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-2" id="tnSerialPickerHint"></p>
                        <div id="tnSerialPickerList" style="max-height:300px; overflow-y:auto;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="button" class="btn btn-primary" id="tnSerialPickerSaveBtn">{{ __('common.save') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
                        {data:'transfer_note_no',name:'transfer_note_no'},
                        {data:'transfer_note_date',name:'transfer_note_date'},
                        {data:'source_warehouse',name:'source_warehouse',sortable:false},
                        {data:'destination_warehouse',name:'destination_warehouse',sortable:false},
                        {data:'total_products',name:'total_products',sortable:false},
                        {data:'total_value',name:'total_value'},
                        {data:'status',name:'status',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'transfer-note/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'transfer_note_table',
        'variable' => 'transfer_note_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),source_warehouse_id:$('#source_warehouse_id').val(),destination_warehouse_id:$('#destination_warehouse_id').val(),status:$('#status').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#source_warehouse_id').select2();
            $('#destination_warehouse_id').select2();
            $('#status').select2();
        });
        $('#search_btn').click(function() {
            initDataTabletransfer_note_table();
        });
        //send
        let sendTransferNoteId = null;

        $(document).on('click', '.sendTransferNote', function() {
            sendTransferNoteId = $(this).data('id');

            ajaxRequest({
                url: url_local + "/admin/transfer-note/details/" + sendTransferNoteId,
                method: 'GET',
            }).then(function(res) {
                let lines = res.Data.details || [];
                let hasSerial = lines.some(l => l.track_serial_number);

                if (!hasSerial) {
                    if (!confirm('Send this transfer? Stock will be deducted from the source warehouse and held as in-transit.')) {
                        return;
                    }
                    submitSendTransfer(sendTransferNoteId, {});
                    return;
                }

                let rows = '';
                lines.forEach(function(line) {
                    rows += '<tr data-detail-id="' + line.transfer_note_detail_id + '" data-track-serial="' +
                        (line.track_serial_number ? 1 : 0) + '">' +
                        '<td>' + line.product_name + '</td>' +
                        '<td>' + line.product_variation_name + '</td>' +
                        '<td class="text-end">' + line.transfer_quantity + '</td>' +
                        '<td>' + (line.track_serial_number ?
                            '<button type="button" class="btn btn-sm btn-outline-danger tn-select-send-serials" data-context="send" data-qty="' +
                            line.transfer_quantity + '" data-detail-id="' + line.transfer_note_detail_id +
                            '"><span class="tn-serial-count-label">Select Serials (0/' + line.transfer_quantity + ')</span></button>' +
                            '<div class="tn-serial-hidden-inputs" style="display:none;"></div>' :
                            '<span class="text-muted">{{ __('common.na') }}</span>') +
                        '</td></tr>';
                });

                $('#sendTransferTable tbody').html(rows);
                new bootstrap.Modal(document.getElementById('sendTransferModal')).show();
            }).catch(function() {
                errorMessage('Unable to load transfer note details.');
            });
        });

        $('#submitSendTransfer').on('click', function() {
            let serials = {};
            let mismatch = false;

            $('#sendTransferTable tbody tr').each(function() {
                let row = $(this);
                if (row.data('track-serial') != 1) return;
                let detailId = row.data('detail-id');
                let expected = parseFloat(row.find('.tn-select-send-serials').data('qty')) || 0;
                let chosen = row.find('.tn-serial-hidden-inputs input').map(function() {
                    return $(this).val();
                }).get();
                if (chosen.length !== expected) {
                    mismatch = true;
                }
                serials[detailId] = chosen;
            });

            if (mismatch) {
                errorMessage('Select the exact serial number quantity for every product before sending.');
                return;
            }

            submitSendTransfer(sendTransferNoteId, serials);
        });

        function submitSendTransfer(transferNoteId, serials) {
            ajaxRequest({
                url: url_local + "/admin/transfer-note/" + transferNoteId + "/send",
                method: 'POST',
                data: {
                    serials: serials
                },
            }).then(function(res) {
                successMessage(res.Message);
                let sendModal = bootstrap.Modal.getInstance(document.getElementById('sendTransferModal'));
                if (sendModal) sendModal.hide();
                initDataTabletransfer_note_table();
            }).catch(function(err) {
                errorMessage(err.Message || 'Unable to send this transfer note.');
            });
        }

        //receive
        let receiveTransferNoteId = null;

        $(document).on('click', '.receiveTransferNote', function() {
            receiveTransferNoteId = $(this).data('id');

            ajaxRequest({
                url: url_local + "/admin/transfer-note/details/" + receiveTransferNoteId,
                method: 'GET',
            }).then(function(res) {
                let rows = '';

                (res.Data.details || []).forEach(function(line) {
                    if (parseFloat(line.remaining_quantity) <= 0) {
                        return;
                    }

                    rows += '<tr data-detail-id="' + line.transfer_note_detail_id + '" data-track-serial="' +
                        (line.track_serial_number ? 1 : 0) + '">' +
                        '<td>' + line.product_name + '</td>' +
                        '<td>' + line.product_variation_name + '</td>' +
                        '<td>' + line.unit_name + '</td>' +
                        '<td class="text-end">' + line.transfer_quantity + '</td>' +
                        '<td class="text-end">' + line.received_quantity + '</td>' +
                        '<td class="text-end">' + line.remaining_quantity + '</td>' +
                        '<td><input type="number" class="form-control form-control-sm receive-qty-input" min="0" max="' +
                        line.remaining_quantity + '" step="any" value="' + line.remaining_quantity + '"></td>' +
                        '<td>' + (line.track_serial_number ?
                            '<button type="button" class="btn btn-sm btn-outline-danger tn-select-receive-serials tn-serial-count-label" data-detail-id="' +
                            line.transfer_note_detail_id + '">Select Serials (0/' + line.remaining_quantity + ')</button>' +
                            '<div class="tn-serial-hidden-inputs" style="display:none;"></div>' :
                            '<span class="text-muted">{{ __('common.na') }}</span>') +
                        '</td></tr>';
                });

                $('#receiveTransferTable tbody').html(rows || '<tr><td colspan="8" class="text-center">{{ __('transfer_notes.nothing_left') }}</td></tr>');
                new bootstrap.Modal(document.getElementById('receiveTransferModal')).show();
            }).catch(function() {
                errorMessage('Unable to load transfer note details.');
            });
        });

        $(document).on('change keyup', '#receiveTransferTable .receive-qty-input', function() {
            refreshTnReceiveSerialLabel($(this).closest('tr'));
        });

        function refreshTnReceiveSerialLabel(row) {
            if (row.data('track-serial') != 1) return;
            let expected = decimal(row.find('.receive-qty-input').val()) || 0;
            let entered = row.find('.tn-serial-hidden-inputs input').length;
            row.find('.tn-select-receive-serials').text(`Select Serials (${entered}/${expected})`)
                .toggleClass('btn-outline-primary', entered == expected)
                .toggleClass('btn-outline-danger', entered != expected);
        }

        $('#submitReceiveTransfer').on('click', function() {
            let products = [];
            let mismatch = false;

            $('#receiveTransferTable tbody tr').each(function() {
                let row = $(this);
                let detailId = row.data('detail-id');
                let qty = parseFloat(row.find('.receive-qty-input').val()) || 0;

                if (!detailId || qty <= 0) {
                    return;
                }

                let entry = {
                    transfer_note_detail_id: detailId,
                    receive_quantity: qty
                };

                if (row.data('track-serial') == 1) {
                    let chosen = row.find('.tn-serial-hidden-inputs input').map(function() {
                        return $(this).val();
                    }).get();
                    if (chosen.length !== qty) {
                        mismatch = true;
                    }
                    entry.serial_numbers = chosen;
                }

                products.push(entry);
            });

            if (mismatch) {
                errorMessage('Select the exact serial number quantity for every serial-tracked product before receiving.');
                return;
            }

            if (!products.length) {
                errorMessage('Please enter a quantity to receive for at least one product.');
                return;
            }

            ajaxRequest({
                url: url_local + "/admin/transfer-note/receive",
                method: 'POST',
                data: {
                    transfer_note_id: receiveTransferNoteId,
                    products: products
                },
            }).then(function(res) {
                successMessage(res.Message);
                bootstrap.Modal.getInstance(document.getElementById('receiveTransferModal')).hide();
                initDataTabletransfer_note_table();
            }).catch(function(err) {
                errorMessage(err.Message || 'Unable to receive this transfer note.');
            });
        });
        // ======================================================
        // SHARED SERIAL PICKER (send: available at source warehouse;
        // receive: currently in transit on this line)
        // ======================================================
        var tnSerialPickerModal = null;
        var currentTnSerialButton = null;

        $(document).on('click', '.tn-select-send-serials, .tn-select-receive-serials', function() {
            currentTnSerialButton = $(this);
            let row = currentTnSerialButton.closest('tr');
            let detailId = row.data('detail-id');
            let isSend = currentTnSerialButton.hasClass('tn-select-send-serials');
            let expected = isSend ?
                (parseFloat(currentTnSerialButton.data('qty')) || 0) :
                (parseFloat(row.find('.receive-qty-input').val()) || 0);
            let alreadyChosen = row.find('.tn-serial-hidden-inputs input').map(function() {
                return $(this).val();
            }).get();

            $('#tnSerialPickerHint').text(`Select exactly ${expected} serial number(s).`);
            $('#tnSerialPickerList').html('<div class="text-muted">{{ __('common.loading') }}</div>');
            tnSerialPickerModal = tnSerialPickerModal || new bootstrap.Modal(document.getElementById('tnSerialPickerModal'));
            tnSerialPickerModal.show();

            ajaxRequest({
                url: url_local + (isSend ?
                    "/admin/transfer-note/available-serials-for-send/" :
                    "/admin/transfer-note/in-transit-serials/") + detailId,
                method: 'GET',
            }).then(function(res) {
                if (!res.Data || !res.Data.length) {
                    $('#tnSerialPickerList').html('<div class="text-muted">{{ __('transfer_notes.no_serials_found') }}</div>');
                    return;
                }
                let html = '';
                res.Data.forEach(function(s) {
                    let checked = alreadyChosen.includes(s.serial_no) ? 'checked' : '';
                    html += `
                        <div class="form-check">
                            <input class="form-check-input tn-serial-checkbox" type="checkbox" value="${s.serial_no}" id="tnSerial_${s.product_variation_serial_number_id}" ${checked}>
                            <label class="form-check-label" for="tnSerial_${s.product_variation_serial_number_id}">${s.serial_no}</label>
                        </div>
                    `;
                });
                $('#tnSerialPickerList').html(html);
            }).catch(function() {
                $('#tnSerialPickerList').html('<div class="text-danger">{{ __('transfer_notes.unable_load_serials') }}</div>');
            });
        });

        $('#tnSerialPickerSaveBtn').on('click', function() {
            if (!currentTnSerialButton) return;
            let row = currentTnSerialButton.closest('tr');
            let isSend = currentTnSerialButton.hasClass('tn-select-send-serials');
            let expected = isSend ?
                (parseFloat(currentTnSerialButton.data('qty')) || 0) :
                (parseFloat(row.find('.receive-qty-input').val()) || 0);
            let selected = $('.tn-serial-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (selected.length !== expected) {
                errorMessage(`Select exactly ${expected} serial number(s) (currently ${selected.length}).`);
                return;
            }

            row.find('.tn-serial-hidden-inputs').html(
                selected.map(sn => `<input type="hidden" value="${sn.replace(/"/g, '&quot;')}">`).join('')
            );

            if (isSend) {
                currentTnSerialButton.find('.tn-serial-count-label').text(`Select Serials (${selected.length}/${expected})`);
                currentTnSerialButton.toggleClass('btn-outline-primary', true).toggleClass('btn-outline-danger', false);
            } else {
                refreshTnReceiveSerialLabel(row);
            }

            tnSerialPickerModal.hide();
        });

        //delete
        deleteRecord({
            buttonClass: "#deleteTransferNote",
            url: url_local + "/admin/transfer-note",

            tableCallback: function() {
                initDataTabletransfer_note_table();
            }
        });
    </script>
@endsection
