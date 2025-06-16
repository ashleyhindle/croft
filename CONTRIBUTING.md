# Contributing to Croft Laravel

Thank you for considering contributing! To keep our release automation and changelog working smoothly, please follow these standards:

## Pull Requests
- **Use squash merging**: All PRs must be squash merged. Only one commit will be added to the main branch per PR.
- **PR Title Format**: The pull request title must follow the [Conventional Commits](https://www.conventionalcommits.org/) specification. Example formats:
  - `feat: add new authentication method`
  - `fix: correct typo in error message`
  - `chore: update dependencies`
- The PR title will become the commit message for the squash merge and will be used in the changelog and release notes.

## Commit Messages
- While only the final squash merge message is required to follow Conventional Commits, we recommend using the same format for all commit messages for consistency.

## Example Types
- `feat`: A new feature
- `fix`: A bug fix
- `docs`: Documentation only changes
- `style`: Changes that do not affect the meaning of the code (white-space, formatting, etc)
- `refactor`: A code change that neither fixes a bug nor adds a feature
- `perf`: A code change that improves performance
- `test`: Adding missing tests or correcting existing tests
- `chore`: Changes to the build process or auxiliary tools

## Testing New Features
- All new features must include appropriate tests to verify their functionality.
- Add or update tests in the `tests/` directory to cover new code paths, edge cases, and expected behaviors.
- Pull requests that add features without sufficient tests may be blocked until adequate test coverage is provided.

## Automated Checks
- PR titles are automatically checked for Conventional Commits compliance.
- Releases and changelogs are automated using [semantic-release](https://github.com/semantic-release/semantic-release).

## Questions?
If you have any questions, open a new discussion in the discussions tab.
