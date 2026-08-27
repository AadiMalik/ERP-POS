# Settings

All configuration lives under one **Settings** screen, organized into sections
(each independently saved):

- **Business** — company profile, address, logo, and general preferences.
- **Accounting** — default chart of accounts, tax settings, aging basis for
  receivables/payables, and the specific accounts used for inventory,
  cost-of-goods-sold, and service transactions. **Customer Account** and
  **Supplier Account** here are attached automatically when a customer or
  supplier is created (including website/API customer signup). Saving a change
  to either default also updates existing customers/suppliers for that business
  so credit sales and payments can post.
- **Inventory** — stock-related preferences.
- **Customer** / **Supplier** — defaults for new customer/supplier records.
- **Email**, **SMS**, **WhatsApp** — the channels used to send documents (e.g.
  quotations, invoices) and notifications to customers/suppliers.
- **FBR** and **PRA** — Pakistan tax-authority e-invoicing integration settings
  (Federal Board of Revenue and Punjab Revenue Authority).
- **POS** — point-of-sale behavior, including register open/close time windows and
  what's allowed at the register (e.g. whether cashiers can change prices or mix
  sale types in one order).
- **Print** and **Thermal Print** — document layout (paper size, orientation, which
  header fields appear) for standard printing and for thermal receipt printers,
  with a live preview for thermal settings.
- **Barcode** — default label size/format for printed barcodes.
- **Theme** — visual appearance; choose from built-in presets (each a complete
  look — colors, sidebar style, card style, table style) or fine-tune individual
  options.
- **Website Theme** — colors, typography, and button styles for your public
  online store.
- **Website Settings** — storefront tab icon (favicon), business hours,
  WhatsApp number, SEO title/description/keywords, OG image, free-delivery
  rules, and bank-transfer details. Upload a **Tab Icon** to brand the
  browser tab on your website; if you leave it empty, the Dukanaz default
  icon is shown instead. Business name, logo, email, phone, and address still
  come from the Business profile screen.
- **Notification** — which in-app/email alerts you receive.

Only users with the `Manage Settings` permission can change these — everyone else
can browse the app but not alter business-wide configuration.
