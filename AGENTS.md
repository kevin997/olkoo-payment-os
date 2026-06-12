# Agent Workflow

Follow this workflow for every bug fix, hotfix, feature, patch, minor release, or release asset change.

## Release Rules

- Use semantic versioning. WordPress only shows an update when the available release version is greater than the installed plugin version.
- Do not rely on replacing a ZIP under the same version to trigger WordPress updates on installed sites. Same-version replacement is only for correcting the current GitHub release asset.
- Future public update releases must use a higher version tag, for example `v1.2.1`, `v1.3.0`, or `v2.0.0`.
- The GitHub release ZIP asset must be named `olkoo-payment-os-<version>.zip`. The built-in updater looks for this asset in public GitHub releases.

## Required Workflow

1. Inspect the current worktree with `git status --short --branch --ignored`.
2. Make the source change in tracked files. Do not edit generated `build/` output manually.
3. Update `CHANGELOG.md` under the correct version.
4. Run PHP lint:
   - `php -l olkoo-payment-os.php`
   - `find includes -name '*.php' -print0 | xargs -0 -n1 php -l`
5. Build the ZIP with `./build.sh <version>`.
6. Verify the ZIP contains the changed files:
   - `unzip -l releases/olkoo-payment-os-<version>.zip`
   - use `unzip -p` plus `rg` for targeted checks.
7. Commit the tracked source/docs changes.
8. Push `main`.
9. Create or update the GitHub issue for the work using `gh issue create`, then close it with the fixing commit.
10. Create or move the matching tag to the release commit:
    - new version: `git tag -a v<version> -m "Olkoo Payment OS v<version>"`
    - current release patch: `git tag -fa v<version> -m "Olkoo Payment OS v<version> hotfix"`
11. Push the tag. If retargeting an existing release tag, use `git push origin v<version> --force`.
12. Upload the built ZIP to the GitHub release:
    - replace existing asset with `gh release delete-asset ...` then `gh release upload ...`
13. Verify the GitHub release asset digest matches the local ZIP:
    - `sha256sum releases/olkoo-payment-os-<version>.zip`
    - `gh release view v<version> --json assets,tagName,targetCommitish,url`

## GitHub Updater

The plugin includes a GitHub release updater in `includes/class-olkoo-payment-updater.php`.

- It reads public GitHub releases from `kevin997/olkoo-payment-os`.
- It selects the highest non-draft, non-prerelease semver tag with a plugin ZIP asset.
- It feeds WordPress the release asset URL so admins can click the normal plugin update button.
- It only works for installations that already have an updater-enabled build installed.
