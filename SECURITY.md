# Security Policy

## Reporting a Vulnerability

Please do not publish security vulnerabilities in a public issue.

Report suspected vulnerabilities privately to:

- **Email:** baahe0044@gmail.com

Include the affected area, steps to reproduce the issue, and any relevant screenshots or logs. Do not include real passwords, private keys, or personal data in the report.

## Sensitive Information

Contributors and reviewers must not commit:

- Database passwords
- API keys or access tokens
- Session secrets
- Production credentials
- Personally identifiable user data
- Private configuration files

Use environment variables or local configuration files excluded by `.gitignore`.

## Supported Version

BrightCV is currently under active development. Security fixes are applied to the latest version on the default branch.
