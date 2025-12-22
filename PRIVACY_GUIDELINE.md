# Plugin Privacy and Data Collection Guidelines

These guidelines are mandatory for maintaining compliance with WordPress.org Plugin Repository rules and global privacy regulations (GDPR, CCPA) when collecting data, especially Personally Identifiable Information (PII) like email addresses, using any external service (e.g., OpenPanel Dev).

## 1. Core Principle: No Data Without Explicit Consent
**No data, PII or non-PII, shall be collected, transmitted, or logged by the plugin without the site administrator's explicit, informed, and active consent.** This consent mechanism **must be disabled by default.**

## 2. Implementing PII Consent (Email Collection)
Collecting the administrator's email address is considered collecting PII for a secondary purpose (product analytics/communication). This requires a separate, specific, and highly prominent opt-in.

| Compliance Element          | Implementation Requirement                                                                                                                                                                                                                                                        |
|-----------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Consent Gating**          | The entire analytics/tracking library (OpenPanel Dev/Composer Package) **must NOT track any data** until the admin has opted-in via the plugin settings.                                                                                                                          |
| **Dedicated Opt-in Notice** | The notice must be a standalone section, typically presented as an **Admin Notice upon activation** or on a **dedicated Settings Tab**.                                                                                                                                           |
| **Transparency Wording**    | The consent message must clearly state:<br>1. **What** is being collected (Usage Data **AND** Email Address).<br>2. **Why** it is being collected (To improve the plugin and for specific product communication).<br>3. The **name of the service** (OpenPanel Dev/Your Company). |

## 3. Mandatory Transparency and Disclosure
### A. Readme.txt Disclosure
- The plugin's primary readme.txt file (for the WordPress.org repository) must contain a clear Privacy Section.
- Explicitly state that the plugin includes an opt-in usage tracking module. 
- Name the third-party service (OpenPanel Dev) and link to its privacy policy. 
- List the types of data collected (e.g., WordPress version, PHP version, Administrator Email, plugin settings, page views). 
- State clearly that tracking is OFF by default and requires explicit opt-in.

### B. WordPress Privacy Policy Integration
Need to inclue message in wp dashboard. This text should detail:
- What data is collected.
- The purpose of data collection.
- The ability for the site administrator to opt out at any time.
- How a site visitor can request data erasure (Right to be Forgotten).

## 4. Implementation Logic Checklist

| Step                  | Requirement |
|-----------------------|-------------|
| **Initial State**     | The plugin **must not execute any tracking code** on activation. The database option for tracking **must be `false` or non-existent**. |
| **Opt-in Check**      | All tracking methods **must be wrapped** in a conditional check:<br>`if ( get_option( 'my_plugin_email_and_tracking_enabled' ) ) { // ... track data }` |

