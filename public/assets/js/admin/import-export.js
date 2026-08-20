/**
 * Generic Import/Export UI, shared by every CRUD module's listing page.
 * Driven entirely by data-module attributes on the Import/Export buttons
 * (see admin.partials.import-export-buttons) - no per-module JS needed.
 */
(function () {
    var currentModule = null;
    var currentLabel = null;
    var currentRefreshFn = null;
    var currentBatchId = null;
    var currentParamsSelector = '';

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    /**
     * Reads the page's own filter inputs (e.g. #filter_business_id, used by
     * Super Admin to pick which business they're acting on) so import/export
     * both operate on the same business/branch context, not just export.
     * Returns {name: value} pairs, e.g. {business_id: '...'}.
     */
    function readContextParams(selectorsStr) {
        var params = {};
        (selectorsStr || '').split(',').filter(Boolean).forEach(function (selector) {
            var el = $(selector.trim());
            if (el.length && el.val()) {
                var name = selector.trim().replace('#filter_', '').replace('#', '');
                params[name] = el.val();
            }
        });
        return params;
    }

    function resetModal() {
        currentBatchId = null;
        $('#importFileInput').val('');
        $('#importUploadError').hide().text('');
        $('#importStepUpload').show();
        $('#importStepPreview').hide();
        $('#importConfirmResult').hide().text('');
        $('#importUploadBtn').show();
        $('#importConfirmBtn').hide();
        $('#importAnotherBtn').hide();
    }

    $('body').on('click', '.import-export-import-btn', function () {
        currentModule = $(this).data('module');
        currentLabel = $(this).data('label') || currentModule;
        currentRefreshFn = $(this).data('refresh-fn') || null;
        currentParamsSelector = $(this).data('export-params-selector') || '';
        resetModal();
        $('#importExportModalLabel').text('Import ' + currentLabel);
        $('#importExportModal').modal('show');
    });

    $('body').on('click', '.import-export-export-btn', function () {
        var module = $(this).data('module');
        var params = readContextParams($(this).data('export-params-selector'));
        var query = Object.keys(params).map(function (name) {
            return name + '=' + encodeURIComponent(params[name]);
        });

        var url = url_local + '/admin/' + module + '/export';
        if (query.length) {
            url += '?' + query.join('&');
        }
        window.location.href = url;
    });

    $('#importDownloadSample').on('click', function () {
        if (!currentModule) {
            return;
        }
        window.location.href = url_local + '/admin/' + currentModule + '/import/sample';
    });

    $('#importUploadBtn').on('click', function () {
        var fileInput = document.getElementById('importFileInput');
        if (!fileInput.files.length) {
            $('#importUploadError').show().text('Please choose an Excel file to upload.');
            return;
        }

        var formData = new FormData();
        formData.append('file', fileInput.files[0]);
        var contextParams = readContextParams(currentParamsSelector);
        Object.keys(contextParams).forEach(function (name) {
            formData.append(name, contextParams[name]);
        });

        var btn = $(this);
        btn.prop('disabled', true).text('Uploading...');
        $('#importUploadError').hide().text('');

        $.ajax({
            url: url_local + '/admin/' + currentModule + '/import/preview',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': csrfToken() },
        }).done(function (response) {
            if (!response.status) {
                $('#importUploadError').show().text(response.message || 'Something went wrong while processing the file.');
                return;
            }
            currentBatchId = response.batch_id;
            renderPreview(response.summary);
            $('#importStepUpload').hide();
            $('#importStepPreview').show();
            $('#importUploadBtn').hide();
            $('#importConfirmBtn').show();
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong while processing the file.';
            $('#importUploadError').show().text(message);
        }).always(function () {
            btn.prop('disabled', false).text('Upload & Preview');
        });
    });

    function renderPreview(summary) {
        var badges = [
            { label: 'Total Rows', value: summary.row_count, cls: 'secondary' },
            { label: 'Will Create', value: summary.create_count, cls: 'success' },
            { label: 'Will Update', value: summary.update_count, cls: 'info' },
            { label: 'Invalid', value: summary.invalid_count, cls: 'danger' },
        ];
        var badgesHtml = badges.map(function (b) {
            return '<div class="col"><div class="p-2 border rounded"><h4 class="mb-0 text-' + b.cls + '">' + b.value +
                '</h4><small class="text-muted">' + b.label + '</small></div></div>';
        }).join('');
        $('#importSummaryBadges').html(badgesHtml);

        var validRows = '';
        var invalidRows = '';

        (summary.groups || []).forEach(function (group, idx) {
            var actionBadge = group.action === 'update'
                ? '<span class="badge bg-info">Update</span>'
                : (group.action === 'create' ? '<span class="badge bg-success">Create</span>' : '<span class="badge bg-danger">Invalid</span>');

            if (group.action !== 'invalid') {
                var rowId = 'importGroupChildren' + idx;
                var hasChildren = group.children && group.children.length > 0;
                var summaryText = summarizeRow(group);

                validRows += '<tr>' +
                    '<td>' + (hasChildren ? '<a href="javascript:void(0)" class="import-toggle-children" data-target="#' + rowId + '"><i class="fa fa-plus-square"></i></a>' : '') + '</td>' +
                    '<td>' + group.row_number + (group.group_key ? ' (' + escapeHtml(group.group_key) + ')' : '') + '</td>' +
                    '<td>' + actionBadge + '</td>' +
                    '<td>' + summaryText + '</td>' +
                    '</tr>';

                if (hasChildren) {
                    validRows += '<tr id="' + rowId + '" style="display:none;"><td></td><td colspan="3">' + renderChildrenTable(group.children) + '</td></tr>';
                }
            } else {
                (group.errors || []).forEach(function (err) {
                    invalidRows += '<tr><td>' + group.row_number + '</td><td>' + escapeHtml(err.column || '') + '</td><td>' + escapeHtml(err.value || '') + '</td><td>' + escapeHtml(err.reason || '') + '</td></tr>';
                });
                (group.children || []).forEach(function (child) {
                    (child.errors || []).forEach(function (err) {
                        invalidRows += '<tr><td>' + child.row_number + '</td><td>' + escapeHtml(err.column || '') + '</td><td>' + escapeHtml(err.value || '') + '</td><td>' + escapeHtml(err.reason || '') + '</td></tr>';
                    });
                });
            }
        });

        $('#importValidRowsBody').html(validRows || '<tr><td colspan="4" class="text-center text-muted">No valid rows.</td></tr>');
        $('#importInvalidRowsBody').html(invalidRows || '<tr><td colspan="4" class="text-center text-muted">No invalid rows.</td></tr>');

        $('#importConfirmBtn').prop('disabled', summary.create_count === 0 && summary.update_count === 0);
    }

    function renderChildrenTable(children) {
        var rows = children.map(function (child) {
            var status = child.action === 'invalid'
                ? '<span class="badge bg-danger">Invalid</span>'
                : '<span class="badge bg-success">OK</span>';
            var errorText = (child.errors || []).map(function (e) {
                return escapeHtml(e.column || '') + ': ' + escapeHtml(e.reason || '');
            }).join('<br>');

            return '<tr><td>' + child.row_number + '</td><td>' + status + '</td><td>' + (errorText || summarizeRow(child)) + '</td></tr>';
        }).join('');

        return '<table class="table table-sm table-bordered mb-0"><thead><tr><th>Row</th><th>Status</th><th>Detail</th></tr></thead><tbody>' + rows + '</tbody></table>';
    }

    function summarizeRow(row) {
        var parts = [];
        var raw = row.raw || {};
        var count = 0;
        for (var key in raw) {
            if (raw.hasOwnProperty(key) && raw[key] !== null && raw[key] !== '' && count < 4) {
                parts.push('<strong>' + escapeHtml(key) + ':</strong> ' + escapeHtml(String(raw[key])));
                count++;
            }
        }
        return parts.join(', ');
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    $('body').on('click', '.import-toggle-children', function () {
        var target = $($(this).data('target'));
        target.toggle();
        $(this).find('i').toggleClass('fa-plus-square fa-minus-square');
    });

    $('#importConfirmBtn').on('click', function () {
        if (!currentBatchId) {
            return;
        }
        var btn = $(this);
        btn.prop('disabled', true).text('Importing...');

        $.ajax({
            url: url_local + '/admin/' + currentModule + '/import/confirm',
            method: 'POST',
            data: { batch_id: currentBatchId, _token: csrfToken() },
        }).done(function (response) {
            if (!response.status) {
                errorMessage(response.message || 'Import failed.');
                return;
            }
            var s = response.summary;
            $('#importConfirmResult').show().html(
                'Import complete: <strong>' + s.created + '</strong> created, <strong>' + s.updated + '</strong> updated, ' +
                '<strong>' + (s.invalid + s.failed) + '</strong> skipped/failed out of ' + s.total_rows + ' total rows.'
            );
            successMessage('Import completed');
            $('#importConfirmBtn').hide();
            $('#importAnotherBtn').show();

            if (currentRefreshFn && typeof window[currentRefreshFn] === 'function') {
                window[currentRefreshFn]();
            }
            $(document).trigger('import-export:refreshed', [currentModule]);
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) || 'Import failed.';
            errorMessage(message);
        }).always(function () {
            btn.prop('disabled', false).text('Confirm Import');
        });
    });

    $('#importAnotherBtn').on('click', function () {
        resetModal();
    });
})();
