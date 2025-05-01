#!/bin/bash

# Usage: ./upload-release-asset.sh <tag> <file>
# Tagging suggestions:
#   - Use semantic versioning: v1.0.0, v2.3.4, etc.
#   - For pre-releases: v0.2.0-alpha, v5.9-beta.3, etc.
#   - See https://semver.org/ for more info.

TAG=$1
FILE=$2
REPO="addex12/School-CRM"

# Check for GitHub CLI
if ! command -v gh &> /dev/null; then
  echo "GitHub CLI (gh) not found. Please install it first."
  exit 1
fi

# Check if release exists
if ! gh release view "$TAG" --repo "$REPO" &>/dev/null; then
  echo "Release with tag $TAG does not exist. Creating release..."
  gh release create "$TAG" "$FILE" --repo "$REPO" --title "$TAG" --notes "Automated release for $TAG"
else
  # Upload asset to release
  gh release upload "$TAG" "$FILE" --repo "$REPO"
fi
