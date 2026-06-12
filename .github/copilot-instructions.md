# Copilot Instructions

Use `AGENTS.md` as the canonical workflow for this repository.

When changing this WordPress plugin:

- Follow semantic versioning.
- Update `CHANGELOG.md`.
- Run PHP lint before release work.
- Build release ZIPs with `./build.sh <version>`.
- GitHub release assets must be named `olkoo-payment-os-<version>.zip`.
- The plugin update checker reads public GitHub releases and only surfaces versions greater than the installed plugin version.
- Create and close a GitHub issue for each release fix or feature when using the GitHub CLI.
