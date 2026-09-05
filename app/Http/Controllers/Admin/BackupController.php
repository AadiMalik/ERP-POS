<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Models\BackupSetting;
use App\Services\Concrete\Admin\BackupRestoreService;
use App\Services\Concrete\Admin\BackupService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BackupController extends Controller
{
    use ResponseAPI;

    protected BackupService $backup_service;
    protected BackupRestoreService $restore_service;

    public function __construct(BackupService $backup_service, BackupRestoreService $restore_service)
    {
        $this->middleware('permission:backup.view')->only(['index']);
        $this->middleware('permission:backup.create')->only(['store']);
        $this->middleware('permission:backup.download')->only(['download']);
        $this->middleware('permission:backup.delete')->only(['destroy']);
        $this->middleware('permission:backup.restore')->only(['restore']);
        $this->middleware('permission:backup.manage')->only(['settingsEdit', 'settingsUpdate', 'cleanup']);

        $this->backup_service = $backup_service;
        $this->restore_service = $restore_service;
    }

    public function index()
    {
        $backups = $this->backup_service->listBackups();
        $setting = BackupSetting::current();
        $availableDisks = $this->backup_service->availableDisks();

        $diskUsageBytes = $backups->where('status', 'success')->sum('size_bytes');

        return view('admin.backups.index', compact('backups', 'setting', 'availableDisks', 'diskUsageBytes'));
    }

    public function store(Request $request)
    {
        try {
            $log = $this->backup_service->createManualBackup('manual', Auth::id());

            if ($log->status !== 'success') {
                return redirect()->route('backups.index')->with('error', 'Backup failed: ' . $log->error_message);
            }

            return redirect()->route('backups.index')->with('success', 'Backup created successfully.');
        } catch (Exception $e) {
            return redirect()->route('backups.index')->with('error', $e->getMessage());
        }
    }

    public function download($id)
    {
        try {
            [$disk, $path, $name] = $this->backup_service->downloadPath($id);

            return Storage::disk($disk)->download($path, $name);
        } catch (Exception $e) {
            return redirect()->route('backups.index')->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $this->backup_service->deleteBackup($id);

            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function restore(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'confirm_phrase' => 'required|string|in:RESTORE',
        ], [
            'confirm_phrase.in' => 'Type RESTORE exactly to confirm this action.',
        ]);

        if ($validate->fails()) {
            return redirect()->route('backups.index')->withErrors($validate);
        }

        try {
            $result = $this->restore_service->restore($id, Auth::id());

            return redirect()->route('backups.index')->with('success', 'Restore completed successfully. A safety backup of the previous state was created: ' . $result['safety_backup']->file_name);
        } catch (Exception $e) {
            return redirect()->route('backups.index')->with('error', $e->getMessage());
        }
    }

    public function cleanup()
    {
        $deleted = $this->backup_service->runCleanup();

        $message = $deleted ? count($deleted) . ' expired/over-limit backup(s) removed.' : 'No backups were due for cleanup.';

        return redirect()->route('backups.index')->with('success', $message);
    }

    public function settingsEdit()
    {
        $setting = BackupSetting::current();
        $availableDisks = $this->backup_service->availableDisks();

        return view('admin.backups.settings', compact('setting', 'availableDisks'));
    }

    public function settingsUpdate(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'frequency' => 'required|in:daily,weekly,monthly',
            'run_time' => 'required|date_format:H:i',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:28',
            'retention_days' => 'required|integer|min:1|max:3650',
            'max_storage_mb' => 'nullable|integer|min:100',
            'disks' => 'required|array|min:1',
        ]);

        if ($validate->fails()) {
            return redirect()->route('backup-settings.edit')->withErrors($validate)->withInput();
        }

        $setting = BackupSetting::where('is_deleted', 0)->first();

        $data = [
            'is_scheduled_enabled' => $request->boolean('is_scheduled_enabled'),
            'frequency' => $request->frequency,
            'run_time' => $request->run_time,
            'day_of_week' => $request->day_of_week,
            'day_of_month' => $request->day_of_month,
            'retention_days' => $request->retention_days,
            'max_storage_mb' => $request->max_storage_mb ?: null,
            'disks' => array_values(array_intersect($request->disks, array_keys($this->backup_service->availableDisks()))),
        ];

        if ($setting) {
            $data['updatedby_id'] = Auth::id();
            $data['date_updated'] = now();
            $setting->update($data);
        } else {
            $data['backup_setting_id'] = generateUuid();
            $data['is_deleted'] = 0;
            $data['createdby_id'] = Auth::id();
            $data['date_created'] = now();
            BackupSetting::create($data);
        }

        return redirect()->route('backup-settings.edit')->with('success', 'Backup settings updated successfully.');
    }
}
