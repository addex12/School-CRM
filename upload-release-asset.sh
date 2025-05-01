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

# Upload asset to release
gh release upload "$TAG" "$FILE" --repo "$REPO"
