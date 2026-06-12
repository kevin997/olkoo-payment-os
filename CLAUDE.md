# Claude Instructions

Use `AGENTS.md` as the canonical workflow for this repository.

For every fix, feature, hotfix, patch, minor update, or release:

- Follow the release rules and required workflow in `AGENTS.md`.
- Keep `CHANGELOG.md` updated.
- Build with `./build.sh <version>`.
- Create and resolve a GitHub issue with `gh`.
- Ensure the GitHub release ZIP asset is named `olkoo-payment-os-<version>.zip`.
- Remember that WordPress update checks require a version greater than the installed plugin version.
