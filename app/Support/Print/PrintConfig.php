<?php

namespace App\Support\Print;

class PrintConfig
{
    protected array $header;
    protected array $footer;
    protected array $page;
    protected array $body;

    public function __construct(array $version = [])
    {
        $this->header = $version['header_config'] ?? [];
        $this->footer = $version['footer_config'] ?? [];
        $this->page   = $version['page_config'] ?? [];
        $this->body   = $version['body_config'] ?? [];
    }

    /**
     * Fail-open: a field that isn't present in the config renders exactly as it
     * always has, so a partial config (or a config predating a newly added field)
     * never silently hides existing document information.
     */
    public function isVisible(string $section, string $field): bool
    {
        $value = data_get($this->{$section}, "fields.{$field}.visible");
        if ($value === null) {
            $value = data_get($this->{$section}, "sections.{$field}.visible");
        }
        if ($value === null) {
            $value = data_get($this->{$section}, "{$field}.visible");
        }

        return $value === null ? true : (bool) $value;
    }

    public function fieldStyle(string $field): array
    {
        return $this->header['fields'][$field] ?? [];
    }

    /**
     * Header field keys ordered by their configured 'order', optionally
     * filtered to one alignment column ('left' or 'right').
     */
    public function orderedHeaderFields(?string $align = null): array
    {
        $fields = $this->header['fields'] ?? [];

        if ($align !== null) {
            $fields = array_filter($fields, fn ($f) => ($f['align'] ?? 'left') === $align);
        }

        uasort($fields, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        return array_keys($fields);
    }

    public function footerText(string $key): ?string
    {
        return data_get($this->footer, "sections.{$key}.text");
    }

    /**
     * A missing key or an explicit null both fall through to $default, so
     * call-sites can supply their own historical default (e.g. a report that
     * has always printed landscape) when a template hasn't customized a value.
     */
    public function page(string $key, $default = null)
    {
        $value = data_get($this->page, $key);

        return $value === null ? $default : $value;
    }

    public function watermark(): array
    {
        return $this->header['watermark'] ?? ['visible' => false];
    }

    public function signatureLabels(array $moduleDefault): array
    {
        $configured = data_get($this->footer, 'signature_lines.labels', []);

        return !empty($configured) ? $configured : $moduleDefault;
    }

    public function footerMeta(string $key, $default = null)
    {
        $value = data_get($this->footer, $key);

        return $value === null ? $default : $value;
    }
}
