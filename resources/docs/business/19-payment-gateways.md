# Payment Gateways (Website & Mobile App)

Payment Gateways let your customers pay online during Website or Mobile App
checkout - by card, mobile wallet, or bank-linked account - instead of only
Cash on Delivery or manual Bank Transfer. **This is separate from POS**: your
in-store cash, card, bank, credit, and wallet payment methods are unaffected
and never appear alongside gateways in this screen.

## Setting Up a Gateway

Under **Payment Gateways**, click **Add Gateway** and choose a provider
(JazzCash, Stripe, Easypaisa, PayFast, Safepay, NayaPay, SadaPay, or PayPal).
Each provider asks for its own credentials - a Merchant ID and Password for
JazzCash, API keys for Stripe, and so on.

**Only one active gateway per provider, per business.** You can't have two
active Stripe (or JazzCash, or any other single provider) configurations at
the same time. To switch to a new set of credentials for the same provider:
deactivate (or delete) the current one first, then add or reactivate the
other. Trying to activate a second one while the first is still active shows
a clear error telling you to deactivate the active one first.

- **Sandbox / Test** and **Live / Production** each keep their own separate
  credentials. Fill in Sandbox first, test a real order, then fill in Live
  when you're ready to accept real payments - switching the **Environment /
  Mode** dropdown never erases the other set of credentials.
- **Website** and **Mobile App** availability are separate switches - a
  gateway can be turned on for one, both, or neither. A gateway is **never**
  available in POS, no matter what you choose here.
- **Supported Currencies** and **Supported Payment Methods** describe what
  the gateway can actually accept - the checkout page only shows a gateway
  when the order's currency matches.
- Once saved, activate the gateway with the status toggle. **Test Connection**
  performs a safe check that your credentials are valid, without charging
  anything.

Your gateway's **webhook/callback URL** is shown on the Add/Edit Gateway
screen as soon as you've picked a Business and a Provider - **you don't need
to save first**. This matters because most providers (Stripe included) hand
you a webhook signing secret only after you've created a webhook on their own
dashboard using our URL, so getting the URL before saving avoids a catch-22.
Give this URL to the provider in their own merchant dashboard - this is how
JazzCash/Stripe/etc. notify us the moment a payment actually succeeds, so an
order is marked paid automatically, even if the customer closes their browser
before returning. The URL never changes for that Business + Provider
afterward, even if you later deactivate and swap in a different set of
credentials for the same provider.

## Secrets Are Never Shown Again

Once you save an API key, secret, or password, it is encrypted and the field
always shows as **configured** afterward - never the actual value, in this
screen, in reports, or anywhere else in the system. Leaving a field blank when
editing keeps the existing value; type a new value only to replace it.

## What the Customer Sees

At checkout, the customer sees every gateway you've activated for that
platform (Website vs Mobile App) alongside the existing Cash on Delivery and
Bank Transfer options, each with its own name/branding. Choosing a gateway
redirects them to that provider's own payment page (or an in-app payment
form); the order is only marked **Paid** after we've verified the payment
directly with the provider - never from the app alone saying "success".

## Refunds

A refund against a gateway-paid order is only offered for providers that
support it (shown when creating an Order Return and choosing that gateway's
method as the refund method). Approving the return calls the provider's real
refund API and records the outcome - if the provider can't be reached, the
return is not approved, so your records and the actual refund never fall out
of sync. Providers without refund support don't offer this option; choose a
different refund method (or leave it blank to credit the customer's Store
Credit) instead.

## Payment Transactions

**Payment Gateway Transactions** lists every gateway payment attempt -
filterable by business, order, gateway, environment (Sandbox/Live), payment
method, status, date range, reference/transaction ID, amount, and currency -
completely separate from POS's own payment reports. Open any transaction to
see its full status history, or issue a refund directly from the list.

## Limitations

- A gateway payment is currently one payment per order (no splitting a single
  order's payment across multiple gateways or multiple partial gateway
  payments) - the same limitation the existing Bank Transfer/COD checkout
  already has.
- Not every provider is fully connected yet - **NayaPay and SadaPay** appear
  in the provider list and accept configuration, but will show a clear
  "not yet implemented" message if you try to use them, since neither
  publishes a business/merchant API we could integrate against yet.
  **JazzCash, Stripe, PayPal, Safepay, Easypaisa, and PayFast are all fully
  implemented** - test each with its Sandbox credentials before switching to
  Live.
