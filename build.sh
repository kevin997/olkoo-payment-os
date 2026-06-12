#!/bin/bash
#
# Build script for Olkoo Payment OS
# Creates a distributable WordPress plugin ZIP file
#
# Usage: ./build.sh [version]
# Example: ./build.sh 1.0.0
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Plugin details
PLUGIN_SLUG="olkoo-payment-os"
VERSION=${1:-"1.0.0"}
BUILD_DIR="build"
RELEASE_DIR="releases"
TEMP_DIR="${BUILD_DIR}/${PLUGIN_SLUG}"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Building Olkoo Payment OS v${VERSION}${NC}"
echo -e "${GREEN}========================================${NC}"

# Clean previous builds
echo -e "\n${YELLOW}→ Cleaning previous builds...${NC}"
rm -rf ${BUILD_DIR}
rm -rf ${RELEASE_DIR}/*.zip 2>/dev/null || true

# Create build directories
echo -e "${YELLOW}→ Creating build directories...${NC}"
mkdir -p ${BUILD_DIR}
mkdir -p ${RELEASE_DIR}
mkdir -p ${TEMP_DIR}

# Copy plugin files
echo -e "${YELLOW}→ Copying plugin files...${NC}"

# Copy PHP files
cp -r includes ${TEMP_DIR}/
cp olkoo-payment-os.php ${TEMP_DIR}/

# Copy assets
cp -r assets ${TEMP_DIR}/

# Copy documentation
cp README.md ${TEMP_DIR}/
cp INSTALLATION.md ${TEMP_DIR}/
cp EXTENDING.md ${TEMP_DIR}/
cp QUICK_START.md ${TEMP_DIR}/
cp CHANGELOG.md ${TEMP_DIR}/
cp LICENSE ${TEMP_DIR}/

# Copy .wordpress-org assets if they exist
if [ -d ".wordpress-org" ]; then
    cp -r .wordpress-org ${TEMP_DIR}/
fi

# Remove development files
echo -e "${YELLOW}→ Removing development files...${NC}"
find ${TEMP_DIR} -name ".DS_Store" -type f -delete
find ${TEMP_DIR} -name "Thumbs.db" -type f -delete
find ${TEMP_DIR} -name ".git*" -type f -delete
rm -f ${TEMP_DIR}/.gitignore
rm -f ${TEMP_DIR}/build.sh
rm -f ${TEMP_DIR}/PROJECT_SUMMARY.md
rm -rf ${TEMP_DIR}/node_modules
rm -rf ${TEMP_DIR}/vendor/bin
rm -rf ${TEMP_DIR}/tests

# Create ZIP file
echo -e "${YELLOW}→ Creating ZIP archive...${NC}"
cd ${BUILD_DIR}
zip -r "../${RELEASE_DIR}/${PLUGIN_SLUG}-${VERSION}.zip" ${PLUGIN_SLUG} -q
cd ..

# Calculate file size
FILE_SIZE=$(du -h "${RELEASE_DIR}/${PLUGIN_SLUG}-${VERSION}.zip" | cut -f1)

# Success message
echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}✓ Build completed successfully!${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "\n${GREEN}Plugin ZIP:${NC} ${RELEASE_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
echo -e "${GREEN}File Size:${NC} ${FILE_SIZE}"
echo -e "${GREEN}Version:${NC} ${VERSION}"

# Show contents
echo -e "\n${YELLOW}Archive contents:${NC}"
unzip -l "${RELEASE_DIR}/${PLUGIN_SLUG}-${VERSION}.zip" | head -20

echo -e "\n${GREEN}You can now upload this ZIP file to WordPress!${NC}\n"
