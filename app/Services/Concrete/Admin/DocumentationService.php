<?php

namespace App\Services\Concrete\Admin;

use Illuminate\Support\Facades\File;
use League\CommonMark\CommonMarkConverter;

/**
 * Renders the in-app Documentation portal (Business & Developer docs) from the
 * Markdown source under resources/docs/{business,developer}/*.md.
 *
 * The section list below is the single source of truth for portal navigation,
 * the reader view, and PDF export ordering. See CLAUDE.md ("Documentation") for
 * the rule that this manifest/content must be kept in sync with the codebase.
 */
class DocumentationService
{
    public function sections(string $audience): array
    {
        return $audience === 'developer' ? $this->developerSections() : $this->businessSections();
    }

    protected function businessSections(): array
    {
        return [
            ['slug' => 'welcome', 'title' => 'Welcome', 'file' => '00-welcome.md'],
            ['slug' => 'getting-started', 'title' => 'Getting Started', 'file' => '01-getting-started.md'],
            ['slug' => 'roles-permissions', 'title' => 'Roles, Permissions & Team Access', 'file' => '02-roles-permissions.md'],
            ['slug' => 'sales-pos', 'title' => 'Sales & Point of Sale (POS)', 'file' => '03-sales-pos.md'],
            ['slug' => 'purchasing-suppliers', 'title' => 'Purchasing & Suppliers', 'file' => '04-purchasing-suppliers.md'],
            ['slug' => 'inventory', 'title' => 'Inventory & Warehouses', 'file' => '05-inventory.md'],
            ['slug' => 'service-management', 'title' => 'Service Sales & Purchases', 'file' => '06-service-management.md'],
            ['slug' => 'accounting', 'title' => 'Accounting & Bookkeeping', 'file' => '07-accounting.md'],
            ['slug' => 'hrm-payroll', 'title' => 'Human Resources & Payroll', 'file' => '08-hrm-payroll.md'],
            ['slug' => 'reports', 'title' => 'Reports', 'file' => '09-reports.md'],
            ['slug' => 'settings', 'title' => 'Settings', 'file' => '10-settings.md'],
            ['slug' => 'subscription-billing', 'title' => 'Subscription & Billing', 'file' => '11-subscription-billing.md'],
            ['slug' => 'audit-security', 'title' => 'Notifications, Activity Log & Security', 'file' => '12-audit-security.md'],
            ['slug' => 'push-notifications', 'title' => 'Push Notifications (FCM)', 'file' => '13-push-notifications.md'],
            ['slug' => 'platform-ecosystem', 'title' => 'The Wider Platform: Website, Intro Site & Mobile App', 'file' => '14-platform-ecosystem.md'],
            ['slug' => 'loyalty-program', 'title' => 'Loyalty Program', 'file' => '15-loyalty-program.md'],
            ['slug' => 'manufacturing', 'title' => 'Manufacturing & Production', 'file' => '16-manufacturing.md'],
            ['slug' => 'serial-number-tracking', 'title' => 'Serial Number Tracking', 'file' => '17-serial-number-tracking.md'],
            ['slug' => 'backup-restore', 'title' => 'Backup & Restore', 'file' => '18-backup-restore.md'],
            ['slug' => 'payment-gateways', 'title' => 'Payment Gateways (Website & Mobile App)', 'file' => '19-payment-gateways.md'],
        ];
    }

    protected function developerSections(): array
    {
        return [
            ['slug' => 'architecture', 'title' => 'Architecture & Overview', 'file' => '00-architecture.md'],
            ['slug' => 'dev-setup', 'title' => 'Development Setup', 'file' => '01-dev-setup.md'],
            ['slug' => 'database-schema', 'title' => 'Database Schema & Relationships', 'file' => '02-database-schema.md'],
            ['slug' => 'modules-controllers-services', 'title' => 'Modules, Controllers & Services', 'file' => '03-modules-controllers-services.md'],
            ['slug' => 'routes-apis', 'title' => 'Routes & APIs', 'file' => '04-routes-apis.md'],
            ['slug' => 'permissions-access-control', 'title' => 'Permissions & Access Control System', 'file' => '05-permissions-access-control.md'],
            ['slug' => 'reports-infrastructure', 'title' => 'Reports Infrastructure', 'file' => '06-reports-infrastructure.md'],
            ['slug' => 'settings-system', 'title' => 'Settings System', 'file' => '07-settings-system.md'],
            ['slug' => 'subscription-module-gating', 'title' => 'Subscription & Module Gating', 'file' => '08-subscription-module-gating.md'],
            ['slug' => 'jobs-commands', 'title' => 'Jobs, Commands & Scheduling', 'file' => '09-jobs-commands.md'],
            ['slug' => 'configuration', 'title' => 'Configuration Reference', 'file' => '10-configuration.md'],
            ['slug' => 'coding-conventions', 'title' => 'Coding Conventions', 'file' => '11-coding-conventions.md'],
            ['slug' => 'documentation-system', 'title' => 'The Documentation System Itself', 'file' => '12-documentation-system.md'],
            ['slug' => 'fcm-broadcast-notifications', 'title' => 'FCM Broadcast Notifications', 'file' => '13-fcm-broadcast-notifications.md'],
            ['slug' => 'platform-ecosystem', 'title' => 'Platform Ecosystem: Companion Repos & Their API Contracts', 'file' => '14-platform-ecosystem.md'],
            ['slug' => 'loyalty-program', 'title' => 'Loyalty Program', 'file' => '15-loyalty-program.md'],
            ['slug' => 'manufacturing', 'title' => 'Manufacturing & Production', 'file' => '16-manufacturing.md'],
            ['slug' => 'waste-damage-expiry', 'title' => 'Waste / Damage / Expiry', 'file' => '17-waste-damage-expiry.md'],
            ['slug' => 'serial-number-tracking', 'title' => 'Serial Number Tracking', 'file' => '18-serial-number-tracking.md'],
            ['slug' => 'backup-restore', 'title' => 'Backup, Restore & Disaster Recovery', 'file' => '19-backup-restore.md'],
            ['slug' => 'payment-gateway-framework', 'title' => 'Payment Gateway Framework', 'file' => '20-payment-gateway-framework.md'],
        ];
    }

    public function render(string $audience, ?string $slug = null): array
    {
        $sections = $this->sections($audience);

        $current = $slug
            ? collect($sections)->firstWhere('slug', $slug)
            : ($sections[0] ?? null);

        if (!$current) {
            abort(404);
        }

        return [
            'sections' => $sections,
            'current'  => $current,
            'html'     => $this->renderFile($audience, $current['file']),
        ];
    }

    public function renderAll(string $audience): array
    {
        return collect($this->sections($audience))
            ->map(fn ($section) => [
                'title' => $section['title'],
                'html'  => $this->renderFile($audience, $section['file']),
            ])
            ->all();
    }

    protected function renderFile(string $audience, string $file): string
    {
        $path = resource_path("docs/{$audience}/{$file}");

        if (!File::exists($path)) {
            return '';
        }

        return (new CommonMarkConverter())->convert(File::get($path))->getContent();
    }
}
