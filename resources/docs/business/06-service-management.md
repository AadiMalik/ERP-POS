# Service Sales & Purchases

Some businesses buy or sell things that **aren't tracked as stock** — a gas
cylinder refill, an installation or delivery charge, a rental, a subscription
service. **Service Management** handles these as their own purchase/sale/return
documents, running entirely in parallel to the regular Purchasing and POS flows,
**without ever touching inventory or the stock ledger**.

- **Service Purchases** — buying a service from a supplier.
- **Service Purchase Returns** — reversing a service purchase.
- **Service Sales** — selling a service to a customer.
- **Service Sale Returns** — reversing a service sale.

Service Management is its own toggleable module (independent of Inventory and POS —
a business can have Service Management enabled without Inventory, or vice versa),
so it's a good fit for businesses that are wholly or partly service-based. Service
transactions still post to Accounting and appear in their own dedicated reports
(Service Sale Report, Service Purchase Report, Service Payment Report, Service
Transaction Summary — see [Reports](09-reports.md)).
