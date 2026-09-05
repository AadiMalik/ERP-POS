# Backup & Restore

This feature is only visible to the **Super Admin** (the platform operator).
Individual businesses do not manage their own backups - the Super Admin is
responsible for protecting everyone's data.

## Backup Dashboard

Under **Backup & Restore → Dashboard** you can see every backup that has been
attempted, with:

- **Name** - the archive's file name (or its current state if it hasn't
  produced a file yet).
- **Type** - Manual, Scheduled, or Pre Restore (an automatic safety copy taken
  right before a restore).
- **Date / Time** - when the backup started.
- **Size** - the archive's size once completed.
- **Status** - Success, Failed, Running, or Missing File (the database record
  exists but the file is no longer on disk).
- **Storage Location** - where the file is stored (Local Private Storage, or
  Amazon S3 / Object Storage if configured).
- **Retention** - how long the backup will be kept before automatic cleanup,
  or "Expired" if it is now past that window (it will be removed the next
  time cleanup runs, unless it is the most recent successful backup - that one
  is never automatically deleted).

From this screen a Super Admin can:
- **Create Backup Now** - runs an on-demand backup immediately.
- **Download** - only available for backups stored on local server storage.
- **Restore** - restores the system to that backup's point in time (see below).
- **Delete** - permanently removes a backup file.
- **Run Cleanup** - immediately applies the retention/storage-limit rules
  instead of waiting for the next scheduled check.

A backup is only ever marked **Success** after the system has re-opened the
archive and confirmed it is a valid, non-corrupted zip file containing a
database dump. A backup that produced a broken or incomplete file is marked
**Failed**, never Success.

## Backup Settings

Under **Backup & Restore → Settings** a Super Admin configures:

- **Automatic Schedule** - turn scheduled backups on/off, and choose Daily,
  Weekly (with a day of week), or Monthly (with a day of month), plus the time
  of day it should run.
- **Retention** - how many days to keep backups, and an optional maximum total
  storage size (MB) for all backups combined. Once storage exceeds the limit,
  the oldest backups are removed first (the newest successful backup is always
  protected).
- **Storage Destination** - which storage location(s) new backups are written
  to. Local Private Storage is always available; Amazon S3 (or another
  S3-compatible service) appears automatically once it has been configured by
  a developer/administrator - no credentials are ever entered or shown on this
  screen.

## Restoring a Backup

Restoring replaces the current database and uploaded files with the contents
of the selected backup. Because this is a major operation:

1. You must type **RESTORE** exactly into the confirmation box before the
   button becomes usable.
2. The system automatically creates a fresh **Pre Restore** safety backup of
   the current state *before* touching anything. If that safety backup fails
   for any reason, the restore is cancelled and nothing is changed.
3. If the restore itself fails partway, the safety backup from step 2 is
   still available in the dashboard so the previous state can be brought back.

Only Super Admins with the Restore permission can perform this action, and
every attempt (successful or failed) is written to the Activity Log together
with who initiated it.

## Notifications

Super Admins receive an in-app notification whenever a backup succeeds,
fails, or a restore completes or fails, so problems (a failed scheduled
backup, running out of disk space, a corrupted archive) are noticed quickly
rather than discovered during an emergency.
