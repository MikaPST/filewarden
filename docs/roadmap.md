# Roadmap

- **0.1.0 Secure foundations:** plugin bootstrap, migrations, verified private storage, ACL, protected upload/download, automated tests.
- **0.2.0 Documents:** folders, document lifecycle, versions, trash and auditing.
- **0.3.0 Interface:** admin manager, document detail and previews.
- **0.4.0 Integrations:** shortcode builder, Gutenberg and Elementor.
- **0.9.0 Beta:** security review, compatibility tests, translations, documentation.
- **1.0.0 Stable:** release after security and functionality acceptance criteria are met.

No target release date has been committed.

## Current progress

The 0.1.0-dev foundation is implemented for single-site WordPress: schema/capabilities, private-storage diagnostics, inherited ACL, PDF/image upload, protected downloads, basic administration, audit retention, unit tests, integration workflow and development ZIP packaging.

**0.1.0 acceptance is pending:** run the WordPress/HTTP integration suite and validate private storage, aliases, caching, permissions and upload limits on the target preproduction host. Unit tests alone do not establish milestone acceptance. Milestones 0.2.0 onward remain unimplemented.
