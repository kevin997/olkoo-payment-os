# Automated Release with GitHub Actions

This guide explains how the automated release process works with GitHub Actions.

## How It Works

When you push a version tag to GitHub, the release workflow automatically:

1. ✅ Checks out the code
2. ✅ Extracts version from tag
3. ✅ Builds the plugin ZIP file
4. ✅ Generates release notes from CHANGELOG
5. ✅ Creates GitHub release
6. ✅ Uploads ZIP file to the release
7. ✅ Uploads ZIP as artifact (90-day retention)

## Quick Release Process

### Step 1: Update Version

Update version in these files:

```bash
# 1. olkoo-payment-os.php (header and constant)
# 2. README.md (badge)
# 3. CHANGELOG.md (add new version section)
```

### Step 2: Commit Changes

```bash
git add .
git commit -m "Bump version to 1.0.1"
git push origin main
```

### Step 3: Create and Push Tag

```bash
# Create tag
git tag -a v1.0.1 -m "Release 1.0.1 - Bug fixes and improvements"

# Push tag to trigger release workflow
git push origin v1.0.1
```

### Step 4: Wait for Automation

GitHub Actions will automatically:
- Build the ZIP file
- Create the release
- Attach the ZIP file

Check progress at: `https://github.com/YOUR_USERNAME/olkoo-payment-os/actions`

### Step 5: Verify Release

Visit: `https://github.com/YOUR_USERNAME/olkoo-payment-os/releases`

The new release should be published with:
- ✅ Release title: "Olkoo Payment OS v1.0.1"
- ✅ Description from CHANGELOG
- ✅ Attached ZIP file
- ✅ Downloadable artifact

## Workflows

### 1. Release Workflow (`.github/workflows/release.yml`)

**Trigger**: Push to tags matching `v*.*.*`

**Actions**:
- Build plugin ZIP
- Generate release notes from CHANGELOG
- Create GitHub release
- Upload ZIP file

### 2. Test Build Workflow (`.github/workflows/test.yml`)

**Trigger**: Push to `main` or `develop` branches, or pull requests

**Actions**:
- Test build script
- Verify ZIP contents
- Upload test artifact

## Tag Naming Convention

Use semantic versioning with `v` prefix:

- `v1.0.0` - Major release
- `v1.0.1` - Patch release
- `v1.1.0` - Minor release
- `v2.0.0` - Major breaking change

## Examples

### Patch Release (Bug Fix)

```bash
# Fix bugs in code
git commit -am "Fix webhook validation issue"

# Update version to 1.0.1
# Update CHANGELOG.md

git commit -am "Bump version to 1.0.1"
git push

# Create and push tag
git tag -a v1.0.1 -m "Release 1.0.1 - Fix webhook validation"
git push origin v1.0.1
```

### Minor Release (New Feature)

```bash
# Add new gateway
git add includes/gateways/class-olkoo-gateway-stripe.php
git commit -m "Add Stripe gateway support"

# Update version to 1.1.0
# Update CHANGELOG.md

git commit -am "Bump version to 1.1.0"
git push

# Create and push tag
git tag -a v1.1.0 -m "Release 1.1.0 - Add Stripe gateway"
git push origin v1.1.0
```

### Major Release (Breaking Changes)

```bash
# Make breaking changes
git commit -am "Refactor: New gateway architecture (breaking)"

# Update version to 2.0.0
# Update CHANGELOG.md with migration guide

git commit -am "Bump version to 2.0.0"
git push

# Create and push tag
git tag -a v2.0.0 -m "Release 2.0.0 - Major architecture update"
git push origin v2.0.0
```

## Monitoring Workflow

### View Workflow Status

```bash
# Using GitHub CLI
gh run list

# Or visit GitHub web interface
# https://github.com/YOUR_USERNAME/olkoo-payment-os/actions
```

### View Workflow Logs

```bash
# Using GitHub CLI
gh run view <run-id> --log

# Or click on workflow run in GitHub web interface
```

## Troubleshooting

### Build Failed

1. Check workflow logs
2. Verify `build.sh` has execute permissions
3. Ensure all required files exist
4. Test build locally first

### Release Not Created

1. Verify tag name matches pattern `v*.*.*`
2. Check `GITHUB_TOKEN` permissions
3. Review workflow logs for errors

### ZIP File Missing

1. Check build step completed successfully
2. Verify `releases/` directory created
3. Review artifact upload logs

## Manual Fallback

If automation fails, you can still create release manually:

```bash
# Build ZIP locally
./build.sh 1.0.1

# Upload to GitHub releases manually
# Or use GitHub CLI:
gh release create v1.0.1 \
  releases/olkoo-payment-os-1.0.1.zip \
  --title "Olkoo Payment OS v1.0.1" \
  --notes-file CHANGELOG.md
```

## Best Practices

1. **Always test locally** before pushing tags
2. **Update CHANGELOG** before releasing
3. **Use descriptive** tag messages
4. **Follow semver** strictly
5. **Test the ZIP** file from releases
6. **Monitor workflow** execution
7. **Keep workflows** up to date

## Workflow Permissions

Ensure your repository has these settings:

1. Go to Settings → Actions → General
2. Workflow permissions: "Read and write permissions"
3. Allow GitHub Actions to create pull requests: ✅

## Customization

### Modify Release Notes Format

Edit `.github/workflows/release.yml`:

```yaml
- name: Generate release notes
  run: |
    # Customize this section
    cat > release_notes.md << 'EOF'
    # Your custom format here
    EOF
```

### Add Pre-release Option

For beta releases, use tags like `v1.0.0-beta.1`:

```bash
git tag -a v1.0.0-beta.1 -m "Beta release"
git push origin v1.0.0-beta.1
```

Modify workflow to detect beta:

```yaml
prerelease: ${{ contains(github.ref, 'beta') }}
```

## Benefits of Automation

- ⚡ **Fast**: Release in seconds
- 🎯 **Consistent**: Same process every time
- 🔒 **Secure**: No manual file handling
- 📦 **Reliable**: Tested build process
- 📝 **Documented**: Auto-generated notes
- ✅ **Verifiable**: Workflow logs available

---

**Automate your releases and focus on coding! 🚀**
