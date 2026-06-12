# Changelog

All notable changes to Olkoo Payment OS will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.1] - 2026-06-12

### Added
- WooCommerce Checkout Block support for the TaraMoney gateway so the payment method appears on modern block-based checkout pages

## [1.2.0] - 2026-06-11

### Changed
- **Breaking**: Rebranded plugin from Olku Payment OS to Olkoo Payment OS
  - All class names, constants, hooks, file names, and the text domain renamed from `olku` to `olkoo`
  - Main plugin file renamed to `olkoo-payment-os.php` (deactivate/reactivate required after update)
- TaraMoney checkout now redirects to the unified payment page (`generalLink`) when available, falling back to card, Dikalo, WhatsApp, Telegram, then SMS links

### Added
- TaraMoney `generalLink` and `cardLink` response handling, stored as order meta
- Webhook authentication via shared-secret token query parameter (TaraMoney does not sign webhook payloads)
- Order ID passed back on the webhook URL so card payment webhooks (which carry no `productId`) resolve to the correct order
- Bundled TaraMoney logo (`assets/images/taramoney-logo.jpg`) used as the gateway checkout icon

### Fixed
- Fatal load-order bug: the gateway interface is now required before the abstract class that implements it
- Fatal gateway inheritance bug: removed the abstract `process_payment()` redeclaration that conflicts with WooCommerce's concrete `WC_Payment_Gateway::process_payment()` method
- TaraMoney credential fields now render as password inputs in WooCommerce settings so saved API keys, business IDs, and webhook secrets are not displayed in plain text
- WordPress admin can now discover future plugin updates from public GitHub release ZIP assets

## [1.1.0] - 2025-01-20

### Changed
- **Breaking**: Updated TaraMoney integration to use new Payment Links API
  - API endpoint changed from `/api/tara/order` to `/api/tara/paymentlinks`
  - Response status now uses lowercase `"success"` instead of uppercase
- Deprecated direct mobile money endpoint (`/api/tara/cmmobile`)
  - Mobile Money payments now handled via Tara's unified payment interface
  - Direct mobile money method falls back to payment links
- Updated admin form fields
  - Removed deprecated "Enable Mobile Money" option
  - Renamed "Enable Order Links" to "Enable Payment Links"
  - Added "Supported Payment Methods" info section

### Added
- Card payment webhook support
  - Automatic detection of card vs mobile money webhooks
  - Separate handling for card payment success/failure
- Enhanced webhook payload handling
  - Support for new collects (mobile money) webhook format
  - Support for card payment webhook format
  - Detailed transaction metadata storage
- New order meta fields for payment tracking
  - `_taramoney_collection_id`: Collection identifier
  - `_taramoney_webhook_type`: 'collects' or 'card'
  - `_taramoney_mobile_operator`: Mobile network operator
  - `_taramoney_transaction_type`: DEPOSIT or TRANSFER
  - `_taramoney_creation_date`: Transaction creation timestamp
  - `_taramoney_change_date`: Last status change timestamp

### Fixed
- Success response validation now handles both old and new API response formats
- Improved webhook type detection for better payment categorization

### Documentation
- Updated README with new API endpoints
- Added webhook payload examples for both payment types
- Documented breaking changes and migration notes

## [1.0.0] - 2025-01-17

### Added
- Initial release of Olkoo Payment OS
- Extensible payment gateway framework for WooCommerce
- Abstract base class for payment gateway implementations
- Payment gateway interface defining standard methods
- Factory pattern for gateway instantiation
- TaraMoney payment gateway integration
  - Order link payments (WhatsApp, Telegram, SMS, Dikalo)
  - Mobile Money payments (Orange Money, MTN Mobile Money)
  - Automatic webhook handling
  - Real-time payment status updates
- Comprehensive logging system
  - Multiple log levels (debug, info, warning, error, critical)
  - WooCommerce log integration
  - Sensitive data sanitization
- HTTP API client with built-in error handling
  - Support for GET, POST, PUT, DELETE requests
  - Automatic JSON encoding/decoding
  - Request/response logging
- Admin configuration interface
  - Gateway settings management
  - Webhook URL display
  - Test mode support
  - Payment options configuration
- Webhook signature validation
- Order status management
- Refund support framework
- Security features
  - HTTPS requirement for production webhooks
  - API credential encryption
  - Input sanitization
  - Nonce verification
- Comprehensive documentation
  - README with getting started guide
  - Installation instructions
  - Extension guide for developers
  - Architecture documentation
  - Code examples
- Developer-friendly features
  - PSR-4 autoloading ready
  - WordPress coding standards compliant
  - WooCommerce API compatibility
  - Action and filter hooks
  - Translation ready

### Security
- Implemented webhook signature validation
- Added HTTPS enforcement for production webhooks
- Sensitive data sanitization in logs
- Secure credential storage using WordPress options API

## [Unreleased]

### Planned
- Stripe gateway integration
- PayPal gateway integration
- Recurring payments support
- Payment analytics dashboard
- Multi-currency support with automatic conversion
- Subscription billing integration
- Payment retry mechanism for failed transactions
- Customer payment method management
- Split payments support
- Payment installments
- Mobile app SDK for iOS and Android
- GraphQL API support
- Bulk payment processing
- Payment reports and exports
- Custom payment success pages
- Email notification customization
- SMS notifications integration
- Two-factor authentication for high-value transactions

---

[1.2.1]: https://github.com/kevin997/olkoo-payment-os/releases/tag/v1.2.1
[1.2.0]: https://github.com/kevin997/olkoo-payment-os/releases/tag/v1.2.0
[1.1.0]: https://github.com/okenlysolutions/olkoo-payment-os/releases/tag/v1.1.0
[1.0.0]: https://github.com/okenlysolutions/olkoo-payment-os/releases/tag/v1.0.0
