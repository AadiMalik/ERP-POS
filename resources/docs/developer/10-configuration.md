# Configuration Reference

Custom config files beyond Laravel's defaults, all under `config/`:

| File | Purpose |
|---|---|
| `theme_presets.php` | Built-in Theme/Appearance presets for Business Settings — each is a complete visual identity (colors, sidebar/header/footer behavior, card/table/button/form/filter style, animation level), not just an accent color. `sneat_default` ("Style 1 · Dukanaz Modern") is the seeded default. |
| `print_defaults.php` | Default print/document layout settings (paper size, orientation, header fields) used before a business customizes its own Print Settings. |
| `thermal_print_defaults.php` | Default layout for thermal receipt printers. |
| `barcode_label_defaults.php` | Default barcode label size/format. |
| `datatables.php` | `yajra/laravel-datatables` configuration for server-side list screens. |
| `permission.php` | `spatie/laravel-permission` package configuration (guard, model bindings — see [Permissions & Access Control](05-permissions-access-control.md) for how the `Role` model is customized on top of this). |

Standard Laravel config (`app.php`, `auth.php`, `database.php`, `mail.php`,
`queue.php`, `sanctum.php`, `session.php`, `cors.php`, `filesystems.php`,
`broadcasting.php`, `cache.php`, `hashing.php`, `logging.php`, `services.php`,
`view.php`) is unmodified from framework defaults except for standard `.env`-driven
values.
