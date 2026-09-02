<?php

namespace App\Support\Print;

class ThermalPrintConfig
{
    protected bool $is_enabled;
    protected int $paper_width_mm;
    protected array $field_config;
    protected array $footer_config;

    public function __construct(array $data = [])
    {
        $this->is_enabled = (bool) ($data['is_enabled'] ?? false);
        $this->paper_width_mm = (int) ($data['paper_width_mm'] ?? 80);
        $this->field_config = $data['field_config'] ?? [];
        $this->footer_config = $data['footer_config'] ?? [];
    }

    /**
     * Fail-open: a field that isn't present in the config renders exactly as
     * it always has, so a partial config (or a config predating a newly
     * added toggle) never silently hides existing receipt information.
     */
    public function isVisible(string $field): bool
    {
        $value = data_get($this->field_config, $field);

        return $value === null ? true : (bool) $value;
    }

    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    public function paperWidthMm(): int
    {
        return $this->paper_width_mm;
    }

    public function fieldConfig(): array
    {
        return $this->field_config;
    }

    public function footerConfig(): array
    {
        return $this->footer_config;
    }

    /**
     * A missing key or an explicit null both fall through to $default.
     */
    public function footerMeta(string $key, $default = null)
    {
        $value = data_get($this->footer_config, $key);

        return $value === null ? $default : $value;
    }
}
