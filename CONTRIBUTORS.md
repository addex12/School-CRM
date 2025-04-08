# Contributors

This project is made possible by the contributions of the following individuals:

<!-- The list below is automatically generated. Do not edit manually. -->

## Project Owner

- **addex12** (Project Owner)

## Contributors List

To fetch the latest list of contributors, run the following command:

```bash
curl -s https://api.github.com/repos/addex12/School-CRM/contributors | jq -r '.[] | "- \(.login) (\(.contributions) contributions)"'
```

## Visualizing Contributions

To generate a commit graph and see who is actively contributing, use the following command:

```bash
git log --pretty=format:"%h %an %ad %s" --date=short | gource --log-format git
```

This will create a visual representation of the repository's commit history.

## Special Thanks

Special thanks to the project owner and maintainers for their guidance and support.
