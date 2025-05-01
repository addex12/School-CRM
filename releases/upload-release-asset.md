# Upload Release Asset Script

This script helps you create a GitHub release and upload an asset to it.

## Usage

```bash
./upload-release-asset.sh <tag> <file>
```

- `<tag>`: The release tag (e.g., `v1.0.0`, `v2.3.4`, `v0.2.0-alpha`)
- `<file>`: The file to upload as a release asset

## Tagging Suggestions

- Use [semantic versioning](https://semver.org/):  
  Examples: `v1.0.0`, `v2.3.4`
- For pre-releases:  
  Examples: `v0.2.0-alpha`, `v5.9-beta.3`
- A newly published release will automatically be labeled as the latest release for this repository.
- If 'Set as the latest release' is unchecked, the latest release will be determined by higher semantic version and creation date.

## Requirements

- [GitHub CLI (`gh`)](https://cli.github.com/) must be installed and authenticated.

## Example

```bash
./upload-release-asset.sh v1.0.0 SchoolCRM-setup.zip
```
