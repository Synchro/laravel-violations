# Security practices

## Reporting a Vulnerability
If you discover a security vulnerability within this project, please report to the maintainers through their GitHub contact information.

## Advisories
If a security vulnerability is confirmed, an advisory will be published on the project's GitHub repository. This advisory will include details about the vulnerability, its impact, and any recommended mitigations or fixes, along with any applicable CVE or GitHub advisory reference.

## Safe usage
The ability to set headers in web responses can have a significant impact on security. It's important that you don't allow user-provided data to be injected into your reporting endpoint names, URLs, or other configuration values. This library is used in a privileged context, so mistakes can have serious consequences. Always sanitize and validate any user input that may affect the configuration or output of this library.
