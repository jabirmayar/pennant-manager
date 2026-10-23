# Changelog

All notable changes to `Filament Pennant` will be documented in this file.

## 1.1.0 - 2026-10-23

- Enforce feature policies and segment allowlists server-side; prevent scope actions from deleting another feature's overrides or a global record.
- Support custom model primary keys and Pennant morph-map serialization for new scope rows.
- Make repeated rollouts converge on the configured percentage, keep explicit overrides, and separate records from different scope models.
- Fix scheduled cache invalidation, isolate malformed schedules, and process schedules in batches with per-feature transactions.
- Use configured Pennant database-store tables and connections consistently while retaining `database` / `features` defaults.
- Add optional global fallback to the value helper (`inherit_global`, disabled by default), consistent native scalar decoding, and safe handling of malformed scheduled values.
- Make adoption metrics portable across database engines and exclude deleted or unconfigured scope models.
- Prevent duplicate feature creation/import from overwriting existing global state, fix facade autoloading, and guard optional stub publishing.
- Flush discovery caches after importing features and preserve the string variant `"0"` when saving settings.
- Add standalone regression tests, a Laravel 11/12 and Filament 4/5 CI matrix, dependency audits, and a security policy.

No database migration or JSON-format change is required. See the README upgrade notes for intentional authorization and rollout corrections. Existing globally/scoped stored values are not rewritten.

## 1.0.0 - 202X-XX-XX

- initial release
