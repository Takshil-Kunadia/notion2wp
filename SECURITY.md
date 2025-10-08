# Security Policy

## Supported Versions

We release patches for security vulnerabilities for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.x.x   | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

We take the security of Notion2WP seriously. If you have discovered a security vulnerability, please report it to us as described below.

### Please Do Not

- **Do not** open a public GitHub issue for security vulnerabilities
- **Do not** disclose the vulnerability publicly until it has been addressed

### Please Do

1. **Report via GitHub Security Advisories**
   - Go to the [Security tab](https://github.com/Takshil-Kunadia/notion2wp/security/advisories/new)
   - Click "Report a vulnerability"
   - Provide detailed information about the vulnerability

2. **Include in Your Report**
   - Type of vulnerability (e.g., XSS, SQL injection, CSRF)
   - Full paths of source file(s) related to the vulnerability
   - Location of the affected source code (tag/branch/commit or direct URL)
   - Step-by-step instructions to reproduce the issue
   - Proof-of-concept or exploit code (if possible)
   - Impact of the issue, including how an attacker might exploit it

3. **Expected Response Time**
   - **Initial Response**: Within 48 hours
   - **Status Update**: Within 7 days
   - **Fix Timeline**: Depends on severity (see below)

## Severity Levels and Response Times

### Critical (CVSS 9.0-10.0)
- **Response**: Immediate
- **Fix**: Within 24-48 hours
- **Examples**: Remote code execution, authentication bypass

### High (CVSS 7.0-8.9)
- **Response**: Within 24 hours
- **Fix**: Within 7 days
- **Examples**: SQL injection, privilege escalation

### Medium (CVSS 4.0-6.9)
- **Response**: Within 48 hours
- **Fix**: Within 30 days
- **Examples**: XSS, CSRF

### Low (CVSS 0.1-3.9)
- **Response**: Within 7 days
- **Fix**: Within 90 days
- **Examples**: Information disclosure, minor security issues

## Security Best Practices for Users

### Authentication
- Never share your Notion integration token
- Store integration tokens securely (WordPress will handle this)
- Rotate tokens regularly if compromised
- Use WordPress security best practices

### WordPress Security
- Keep WordPress core updated
- Keep Notion2WP plugin updated
- Use strong admin passwords
- Enable two-factor authentication
- Use HTTPS for your WordPress site
- Limit plugin installations to trusted sources

### Notion Integration Security
- Only grant necessary permissions to your integration
- Review connected pages regularly
- Use internal integrations (not public) when possible
- Monitor integration usage in Notion settings

### Server Security
- Keep PHP version updated (7.4+ required)
- Enable WordPress debug logging only in development
- Restrict file permissions appropriately
- Use secure hosting environment

## Security Features in Notion2WP

### Current Security Measures

1. **Authentication**
   - Secure token storage in WordPress database
   - WordPress nonce verification for all AJAX requests
   - Capability checks for admin operations

2. **Data Handling**
   - Input sanitization using WordPress functions
   - Output escaping to prevent XSS
   - Prepared statements for database queries (via WordPress API)

3. **API Security**
   - HTTPS required for Notion API calls
   - Token sent via secure Authorization header
   - No token exposure in client-side code

4. **WordPress Integration**
   - Follows WordPress coding standards
   - Uses WordPress security APIs
   - Capability-based access control

### Known Limitations

- Integration tokens are stored in WordPress database (encrypted via WordPress)
- Requires WordPress admin access for configuration
- Depends on WordPress security measures

## Disclosure Policy

When we receive a security vulnerability report, we will:

1. **Confirm Receipt**: Acknowledge receipt within 48 hours
2. **Assess Severity**: Evaluate the vulnerability and assign severity
3. **Develop Fix**: Create and test a patch
4. **Release Update**: Publish security update
5. **Public Disclosure**: After patch release, we will:
   - Credit the reporter (unless anonymity requested)
   - Publish security advisory
   - Update CHANGELOG with security fix details

## Security Updates

Security updates will be:
- Released as soon as safely possible
- Clearly marked as security releases in changelog
- Announced via GitHub releases and security advisories
- Backward compatible when possible

## Contact

For security concerns that don't qualify as vulnerabilities, you can:
- Open a [GitHub Discussion](https://github.com/Takshil-Kunadia/notion2wp/discussions)
- Email: (Add your email if you want to provide one)

## Recognition

We appreciate security researchers who help keep Notion2WP secure. With your permission, we will:
- Credit you in the security advisory
- List you in our security acknowledgments
- Thank you in the release notes

Thank you for helping keep Notion2WP and our users safe!
