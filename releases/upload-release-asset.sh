#!/bin/bash

# Usage: ./upload-release-asset.sh <tag> <file>
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
