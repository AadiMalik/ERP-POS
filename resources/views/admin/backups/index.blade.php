@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center py-3 mb-4">
            <h4 class="fw-bold mb-0">Backup &amp; Restore</h4>
            <div>
                <a href="{{ route('backup-settings.edit') }}" class="btn btn-outline-secondary rounded-pill me-2">
                    <i class="fa fa-cog"></i> Settings
                </a>
                @can('backup.manage')
                    <form action="{{ route('backups.cleanup') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary rounded-pill me-2">
                            <i class="fa fa-broom"></i> Run Cleanup
                        </button>
                    </form>
                @endcan
                @can('backup.create')
                    <button type="button" id="createBackupBtn" class="btn btn-primary rounded-pill">
                        <i class="fa fa-plus"></i> Create Backup Now
                    </button>
                @endcan
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted small">Total Backups</div>
                        <div class="fs-4 fw-bold">{{ $backups->where('status', 'success')->count() }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted small">Storage Used</div>
                        <div class="fs-4 fw-bold">{{ number_format($diskUsageBytes / 1048576, 1) }} MB</div>
                        @if ($setting->max_storage_mb)
                            <div class="text-muted small">Limit: {{ $setting->max_storage_mb }} MB</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted small">Scheduled Backups</div>
                        <div class="fs-5 fw-bold">
                            @if ($setting->is_scheduled_enabled)
                                <span class="badge bg-success">Enabled</span> {{ ucfirst($setting->frequency) }} at {{ $setting->run_time }}
                            @else
                                <span class="badge bg-secondary">Disabled</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive text-nowrap p-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Date / Time</th>
                            <th>Size</th>
                            <th>Status</th>
                            <th>Storage Location</th>
                            <th>Retention</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($backups as $backup)
                            <tr>
                                <td>{{ $backup->file_name ?? '(' . ucfirst($backup->status) . ')' }}</td>
                                <td><span class="badge bg-label-info">{{ str_replace('_', ' ', ucfirst($backup->type)) }}</span></td>
                                <td>{{ localDateTime($backup->started_at) }}</td>
                                <td>{{ $backup->size_bytes ? number_format($backup->size_bytes / 1048576, 2) . ' MB' : '-' }}</td>
                                <td>
                                    @if ($backup->status === 'success' && $backup->file_missing)
                                        <span class="badge bg-danger">Missing File</span>
                                    @elseif ($backup->status === 'success')
                                        <span class="badge bg-success">Success</span>
                                    @elseif ($backup->status === 'failed')
                                        <span class="badge bg-danger" title="{{ $backup->error_message }}">Failed</span>
                                    @else
                                        <span class="badge bg-warning">Running</span>
                                    @endif
                                </td>
                                <td>{{ $availableDisks[explode(',', $backup->disk ?? '')[0]] ?? $backup->disk ?? '-' }}</td>
                                <td>
                                    @if ($backup->status === 'success')
                                        @if ($backup->is_expired)
                                            <span class="badge bg-label-warning">Expired</span>
                                        @else
                                            <span class="badge bg-label-success">Until {{ $backup->retention_expires_at?->format('d M Y') }}</span>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($backup->can_download)
                                        @can('backup.download')
                                            <a href="{{ route('backups.download', $backup->backup_log_id) }}" class="btn btn-sm btn-icon" title="Download"><i class="fa fa-download"></i></a>
                                        @endcan
                                    @endif
                                    @can('backup.restore')
                                        @if ($backup->status === 'success' && ! $backup->file_missing)
                                            <button type="button" class="btn btn-sm btn-icon restoreBackupBtn" title="Restore"
                                                data-id="{{ $backup->backup_log_id }}" data-name="{{ $backup->file_name }}">
                                                <i class="fa fa-undo text-warning"></i>
                                            </button>
                                        @endif
                                    @endcan
                                    @can('backup.delete')
                                        <button type="button" class="btn btn-sm btn-icon deleteBackupBtn" title="Delete" data-id="{{ $backup->backup_log_id }}">
                                            <i class="fa fa-trash text-danger"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No backups yet. Click "Create Backup Now" to make your first one.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Restore confirmation modal -->
    <div class="modal fade" id="restoreModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="" method="POST" id="restoreForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Restore</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger">
                            <strong>Warning:</strong> This will overwrite the current database and uploaded files with the contents of
                            <strong id="restoreFileName"></strong>. A safety backup of the current state will be created automatically first,
                            but this is still a major operation.
                        </div>
                        <label class="fw-semibold">Type <code>RESTORE</code> to confirm</label>
                        <input type="text" name="confirm_phrase" class="form-control" required autocomplete="off">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Restore Now</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <form id="createBackupForm" action="{{ route('backups.store') }}" method="POST" class="d-none">
        @csrf
    </form>
@endsection

@section('js')
    @if (session('success'))
        <script>successMessage("{{ session('success') }}");</script>
    @endif
    @if (session('error'))
        <script>errorMessage("{{ session('error') }}");</script>
    @endif
    <script>
        $('#createBackupBtn').on('click', function () {
            Swal.fire({
                title: 'Create a backup now?',
                text: 'This may take a moment depending on database and file size.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, create it',
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#createBackupForm').submit();
                }
            });
        });

        $('.restoreBackupBtn').on('click', function () {
            const id = $(this).data('id');
            const name = $(this).data('name');
            $('#restoreFileName').text(name);
            $('#restoreForm').attr('action', `${url_local}/admin/backups/${id}/restore`);
            $('#restoreModal').modal('show');
        });

        deleteRecord({
            buttonClass: '.deleteBackupBtn',
            url: url_local + '/admin/backups',
            text: 'This will permanently delete the backup file. This cannot be undone.',
            tableCallback: function () {
                location.reload();
            }
        });
    </script>
@endsection
