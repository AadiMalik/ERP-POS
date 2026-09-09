# Settings

All configuration lives under one **Settings** screen, organized into sections
(each independently saved):

- **Business** — company profile, address, logo, and general preferences,
  including **Table pagination position** (show page numbers at the bottom
  of list tables, the top, or both — bottom is the default), **Timezone**, and
  **Date/Time Format**. Every date and time shown anywhere in the app — lists,
  create/edit forms, reports, exports — is displayed in this timezone; records
  are always saved consistently underneath, so changing the Timezone here
  immediately re-displays all of your business's existing records (products,
  orders, purchases, everything with a date) in the new timezone without
  altering when they actually happened.
- **Tax** — tax rate and behavior, configured **per branch** rather than
  business-wide. For each branch, set an **Overall Tax Rate** (applied to
  cash and any other non-card payment) and a **Card Tax Rate** (applied
  automatically when an order is paid fully by card), plus a **Tax Type**
  of **Exclusive** (tax is added on top of the price) or **Inclusive** (the
  price already includes tax). Switching Tax Type never changes the price a
  customer sees or pays on POS, the website, or the mobile app — it only
  changes how the receipt and accounting reports break that price down
  between revenue and tax. Whatever rate/type a branch has configured
  applies uniformly to every order placed at that branch, on every channel
  (POS, POS Desktop, Website, Mobile App). Printed receipts (thermal and
  normal) show whether tax was Inclusive or Exclusive for that sale, and the
  accounting Journal Entry posted when an order completes books revenue and
  tax correctly for either mode.
- **Accounting** — default chart of accounts, tax settings, aging basis for
  receivables/payables, and the specific accounts used for inventory,
  cost-of-goods-sold, and service transactions. **Customer Account** and
  **Supplier Account** here are attached automatically when a customer or
  supplier is created (including website/API customer signup). Saving a change
  to either default also updates existing customers/suppliers for that business
  so credit sales and payments can post.
- **Language / Localization** — choose the **ERP Display Language** (the language the
  whole system — sidebar, menus, screens, buttons, messages — is shown in for everyone
  at your business), the **Default Input Language** (the default text direction for
  notes/description fields — codes, prices, emails and web addresses always stay
  left-to-right regardless), and the **Interface Direction** (leave on Auto to follow
  the Display Language automatically, or force Left-to-Right/Right-to-Left). Right-to-left
  languages such as Urdu, Arabic, Persian, Pashto, Sindhi, Balochi, Punjabi (Pakistan),
  and Hebrew automatically flip the whole layout — sidebar moves to the right, text
  aligns to the right, tables and menus mirror accordingly. **40+ languages** are
  available (including English, Urdu, Hindi, Arabic, Persian, French, Spanish,
  Punjabi India/Pakistan, Pashto, Sindhi, Balochi, and major world languages). **Settings
  screen labels** (every tab — Business, Accounting, Inventory, POS, Print, Theme,
  Website, Email/SMS/WhatsApp, and the rest) follow the Display Language along with the
  sidebar, shared buttons/toasts, Products and most business modules (purchases, sales,
  inventory, accounting lists, HRM including payroll/ESS/advances/assets, reports indexes
  and print/PDF column headers, POS cart/payment messages, and more). A few deep create
  forms and product/category names stored in the database may still show English — missing
  phrases fall back to English rather than showing a broken label.
- **Inventory** — stock-related preferences.
- **Customer** / **Supplier** — defaults for new customer/supplier records.
- **Email**, **SMS**, **WhatsApp** — the channels used to send documents (e.g.
  quotations, invoices) and notifications to customers/suppliers.
- **Firebase** — FCM service-account credentials for mobile push / broadcast
  notifications (Settings → Firebase tab).
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
- **Notification** — which in-app alerts you receive (Low Stock, New Order,
  Customer Credit Due, Supplier Payment Due, Order Status Updated), lead times
  for the due-date alerts, the notification sound on/off, and
  **"Website/Mobile App Order Notification to POS"** (on by default) — see
  [Notifications, Activity Log & Security](12-audit-security.md).

Only users with the `Manage Settings` permission can change these — everyone else
can browse the app but not alter business-wide configuration.

**For the Super Admin only:** a business selector appears above these tabs.
Pick any business from it to view and update *that* business's settings —
useful for helping a business configure something without needing their
login. Every other user only ever sees and edits their own business's settings.
