# Security policy

Security fixes target the latest 1.x release. Update before reporting a problem that has already been fixed.

Report suspected vulnerabilities privately to **hello@jabirkhan.com**. Include the package, Laravel, Filament, Livewire, and Pennant versions, reproduction steps using synthetic data, relevant configuration, and expected impact. Do not include credentials, personal data, or private feature values in public issues.

Reports are reviewed privately and confirmed fixes are distributed through releases. Coordinate disclosure with the maintainer to give users time to update. This volunteer-maintained package does not guarantee a fixed response time.

Feature management is an administrative capability. Restrict access to the host Filament panel and register a policy for `daacreators\PennantManager\Models\Feature` to control `viewAny`, `create`, `update`, and `delete`. No policy preserves Filament's existing permissive resource defaults for panel users. Configured `segment_columns` are enforced on the server; leaving the list empty retains free-column selection for authorized administrators.

CI runs regression tests and audits known runtime dependency advisories. These checks are not a security audit of every host application or configuration. Host applications must also audit and update their own dependencies.

Laravel 11 is retained as a compatibility target. Its resolved framework branch currently reports dependency advisories; the legacy CI job displays those audit failures without treating them as package-test failures. Laravel 12 audit jobs remain blocking. A passing legacy compatibility job is not evidence that its dependency graph is free of vulnerabilities.
