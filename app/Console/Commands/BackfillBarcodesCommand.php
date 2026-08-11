<?php

namespace App\Console\Commands;

use App\Models\ProductVariation;
use App\Services\Concrete\Admin\BarcodeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillBarcodesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'barcode:backfill {--business=} {--dry-run} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate missing barcodes/QR codes for existing product variations. Safe to re-run (skips variations that already have a barcode).';

    protected $barcode_service;

    public function __construct(BarcodeService $barcode_service)
    {
        parent::__construct();
        $this->barcode_service = $barcode_service;
    }

    public function handle()
    {
        $businessId = $this->option('business');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        // --force additionally fills the qr_code column for variations that already have a
        // barcode (from before this feature existed) but no qr_code yet.
        $noBarcode = fn ($q) => $q->whereNull('barcode')->orWhere('barcode', '');

        $query = ProductVariation::where('is_deleted', 0)
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->when(!$force, fn ($q) => $q->where($noBarcode))
            ->when($force, fn ($q) => $q->where(function ($q) use ($noBarcode) {
                $q->where($noBarcode)->orWhereNull('qr_code');
            }));

        $total = $query->count();

        if ($total === 0) {
            $this->info('No product variations need backfilling.');
            return 0;
        }

        $this->info("Found {$total} variation(s) to backfill.");

        if ($dryRun) {
            $this->info('Dry run - no changes made.');
            return 0;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;

        $query->chunkById(500, function ($variations) use (&$processed, $bar) {
            DB::transaction(function () use ($variations, &$processed, $bar) {
                foreach ($variations as $variation) {
                    $this->barcode_service->generateForVariation($variation, null, null, false);
                    $processed++;
                    $bar->advance();
                }
            });
        }, 'product_variation_id');

        $bar->finish();
        $this->newLine();
        $this->info("Backfilled {$processed} variation(s).");

        return 0;
    }
}
