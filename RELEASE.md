# Release Process for Olkoo Payment OS

This document describes how to create and distribute releases of the Olkoo Payment OS plugin.

## Prerequisites

- Git repository initialized
- All changes committed
- Version number decided
- Changelog updated

## Building a Release

### Method 1: Using Build Script (Recommended)

```bash
# Make build script executable
chmod +x build.sh

# Build release ZIP
./build.sh 1.0.0
```

This will create: `releases/olkoo-payment-os-1.0.0.zip`

### Method 2: Manual ZIP Creation

```bash
# Create releases directory
mkdir -p releases

# Create ZIP (from parent directory)
cd ..
zip -r olkoo-payment-os/releases/olkoo-payment-os-1.0.0.zip olkoo-payment-os \
  -x "*/build/*" \
  -x "*/releases/*" \
  -x "*/.git/*" \
  -x "*/.gitignore" \
  -x "*/build.sh" \
  -x "*/PROJECT_SUMMARY.md" \
  -x "*/node_modules/*" \
  -x "*/.DS_Store"
```

## What Gets Included

### Included Files
- ✅ `olkoo-payment-os.php` (main plugin file)
- ✅ `includes/` (all PHP classes)
- ✅ `assets/` (CSS, JS, images)
- ✅ `README.md`
- ✅ `INSTALLATION.md`
- ✅ `EXTENDING.md`
- ✅ `QUICK_START.md`
- ✅ `CHANGELOG.md`
- ✅ `LICENSE`

### Excluded Files
- ❌ `.git/` (Git files)
- ❌ `.gitignore`
- ❌ `build/` (build directory)
- ❌ `releases/` (releases directory)
- ❌ `build.sh` (build script)
- ❌ `PROJECT_SUMMARY.md` (development docs)
- ❌ `node_modules/` (if exists)
- ❌ `.DS_Store`, `Thumbs.db` (system files)

## Distribution Methods

### 1. Direct Download

Upload the ZIP file to your website:

```bash
# Upload to your server
scp releases/olkoo-payment-os-1.0.0.zip user@server:/path/to/downloads/
```

Users can download and install via WordPress admin.

### 2. GitHub Releases

Create a GitHub release with the ZIP attached:

```bash
# Tag the release
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0

# Create release on GitHub
# 1. Go to repository → Releases → New Release
# 2. Select tag: v1.0.0
# 3. Title: Olkoo Payment OS v1.0.0
# 4. Description: Copy from CHANGELOG.md
# 5. Attach: releases/olkoo-payment-os-1.0.0.zip
# 6. Publish release
```

### 3. WordPress.org Plugin Directory (Optional)

To submit to WordPress.org:

1. Create account at https://wordpress.org/plugins/developers/
2. Submit plugin for review
3. Follow WordPress.org SVN instructions
4. Maintain separate repository for WordPress.org

## Version Management

### Update Version Numbers

Before building, update version in these files:

1. **olkoo-payment-os.php** (header)
   ```php
   * Version: 1.0.0
   ```

2. **olkoo-payment-os.php** (constant)
   ```php
   define('OLKOO_PAYMENT_OS_VERSION', '1.0.0');
   ```

3. **CHANGELOG.md**
   ```markdown
   ## [1.0.0] - 2025-01-17
   ```

4. **README.md** (badge)
   ```markdown
   [![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)]
   ```

### Version Numbering

Follow [Semantic Versioning](https://semver.org/):

- **MAJOR.MINOR.PATCH** (e.g., 1.0.0)
- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

Examples:
- `1.0.0` - Initial release
- `1.0.1` - Bug fix release
- `1.1.0` - New feature (TaraMoney + Stripe)
- `2.0.0` - Breaking change (new architecture)

## GitHub Release Template

### Title
```
Olkoo Payment OS v1.0.0
```

### Description Template
```markdown
# Olkoo Payment OS v1.0.0

🎉 Initial release of Olkoo Payment OS - Extensible WooCommerce payment gateway plugin!

## ✨ Features

- Extensible payment gateway framework
- TaraMoney payment gateway integration
  - Order link payments (WhatsApp, Telegram, SMS)
  - Mobile Money (Orange Money, MTN Money)
- Automatic webhook handling
- Comprehensive logging system
- Test/Production mode
- Secure API credential management

## 📦 Installation

1. Download `olkoo-payment-os-1.0.0.zip`
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the ZIP file
4. Activate the plugin
5. Configure TaraMoney gateway in WooCommerce → Settings → Payments

## 📖 Documentation

- [README](README.md)
- [Installation Guide](INSTALLATION.md)
- [Quick Start](QUICK_START.md)
- [Developer Guide](EXTENDING.md)

## 🔧 Requirements

- WordPress 5.8+
- WooCommerce 5.0+
- PHP 7.4+
- HTTPS enabled (for production webhooks)

## 🐛 Bug Reports

Found a bug? [Open an issue](https://github.com/okenlysolutions/olkoo-payment-os/issues)

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for full details.

---

**Full Changelog**: https://github.com/okenlysolutions/olkoo-payment-os/commits/v1.0.0
```

## Automated Release with GitHub Actions (Optional)

Create `.github/workflows/release.yml`:

```yaml
name: Create Release

on:
  push:
    tags:
      - 'v*'

jobs:
  build:
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v2

    - name: Build plugin ZIP
      run: |
        chmod +x build.sh
        ./build.sh ${GITHUB_REF#refs/tags/v}

    - name: Create Release
      uses: softprops/action-gh-release@v1
      with:
        files: releases/*.zip
        body_path: CHANGELOG.md
      env:
        GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
```

## Testing the Release

Before distributing:

1. **Extract and Verify**
   ```bash
   unzip -l releases/olkoo-payment-os-1.0.0.zip
   ```

2. **Test Installation**
   - Install on clean WordPress
   - Activate plugin
   - Configure settings
   - Test payment flow
   - Check for errors

3. **Verify Documentation**
   - All links work
   - Screenshots included
   - Instructions clear

## Checklist

Before creating a release:

- [ ] All features complete and tested
- [ ] Version numbers updated
- [ ] CHANGELOG.md updated
- [ ] README.md updated
- [ ] All tests passing
- [ ] No debug code or console logs
- [ ] Documentation reviewed
- [ ] Build script works
- [ ] ZIP file tested
- [ ] Git tag created
- [ ] GitHub release created
- [ ] Release announcement prepared

## Support

After release:

- Monitor GitHub issues
- Respond to support requests
- Track bug reports
- Plan next version

## Rolling Back

If issues found after release:

1. Don't delete the release
2. Fix issues in code
3. Create new patch version
4. Document in CHANGELOG.md
5. Release new version

---

**Remember**: Never modify a published release. Always create a new version.
