# The Wider Platform: Website, Intro Site & Mobile App

This ERP is the control center for your business, but it isn't the only piece
software your customers or prospective customers see. Three companion
applications connect to it. This page explains what each one is and how it
relates to what you manage here.

## Your Business's Website (the customer-facing storefront)

Every business on this platform can have its own online store — a website
where your customers browse products, add them to a cart, check out, track
their orders, and manage their account and addresses.

You do not build or maintain this website's code. Instead, you control what it
shows entirely from inside this ERP:

- **Website CMS** (under Settings) — your logo, theme colors, homepage
  sections, banners, FAQs, testimonials, social links, and static pages
  (About, policies, etc.).
- **Products** — anything marked visible on the website shows up there
  automatically, with the same price, stock, and images you manage for
  in-store/POS sales.
- **Orders placed online** land in the same Orders list as your POS sales, so
  your staff handle them the same way.

If a customer registers, logs in, adds items to a cart, applies a voucher, or
places an order on your website, that all happens through this same ERP in
the background — there is no separate customer database to keep in sync.

## The Mobile Shopping App

Alongside the website, customers can also shop through a dedicated mobile
app. It offers the same experience as your website — browsing, cart,
checkout, order tracking, wishlist, reviews — just as a phone app instead of
a browser tab. Because it draws on the exact same product, pricing, cart, and
order data as your website, whatever you set up in the Website CMS and
Products screens applies to the app as well; there is nothing extra to
configure for the app specifically.

## The Public Intro / Sign-Up Site

Separately from your own store, there is one shared public website that
introduces the ERP/POS/Website/Mobile product itself to *new* businesses who
are considering signing up. This is where a prospective customer:

- Reads about the available subscription packages and what each one includes.
- Registers their business and picks a package.
- Uploads a bank deposit slip / transfer screenshot as payment proof
  (stored with the pending subscription invoice for Super Admin review).
- Sends a general inquiry to be contacted about getting set up.

The content on this site (packages, pricing, module descriptions, blog posts,
testimonials, FAQs) is managed through the **Intro CMS** section of the
platform admin area — separate from your own business's Website CMS, since
this site represents the platform as a whole, not any one business.

## How the Pieces Fit Together

| Piece | Who it's for | Where you manage its content |
|---|---|---|
| This ERP | You and your staff — running the business | Everything, directly |
| Your Website | Your customers, shopping online | Website CMS + Products (in this ERP) |
| Mobile App | Your customers, shopping from their phone | Same as Website — no separate setup |
| Intro / Sign-Up Site | Prospective businesses considering the platform | Intro CMS (platform-level, not per-business) |

If something looks wrong on your website or app — a wrong price, an outdated
banner, a missing product — the fix is almost always made here in the ERP,
not in the website or app itself.

## Business Access Control (Super Admin only)

The Super Admin can enable or disable your business's access to each
platform — **ERP**, **Website & Mobile App**, **POS**, and **Offline POS** —
independently. Every platform is **on by default**; blocking is
whole-business, applying automatically to every branch and every user, with
no per-branch or per-user configuration needed or possible.

If a platform is blocked for your business:

- **ERP** — no user from your business can log in; they see "Your business
  access has been disabled. Please contact the administrator."
- **Website & Mobile App** — your storefront and app stop working for
  customers entirely (both together, since they share the same backend).
- **POS** — the POS screen and all POS operations stop working.
- **Offline POS** — the offline desktop POS client stops working.

This is separate from your subscription status (suspended/expired) and from
your subscription package's included modules — it's a direct, platform-level
switch the Super Admin uses when there's a reason to fully suspend one
specific channel. If you believe a platform has been blocked in error,
contact the administrator.

## System Feature Controls (Super Admin only)

Separately, the Super Admin can turn specific platform-wide features,
services, or third-party integrations on or off across *every* business at
once — for example, push notifications or online payment gateways, if a
service needs to be paused platform-wide (e.g. while credentials are
renewed). This is different from Business Access Control above: it affects
one integration everywhere, rather than one business's access to a whole
platform.
