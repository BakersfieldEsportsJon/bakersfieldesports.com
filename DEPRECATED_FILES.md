# Deprecated Files

This document tracks files that have been deprecated/renamed for security or code quality reasons.

## Files Deprecated

### proccess_form.php → proccess_form.php.deprecated
- **Date:** 2025-10-25
- **Reason:** Incomplete implementation with TODO placeholder
- **Replacement:** Use `contact-us/process_form.php` instead
- **Issue:** File had incomplete email sending logic and was a duplicate of the working contact form processor
- **Note:** Filename was also misspelled ("proccess" instead of "process")

### contact-us/captcha.php → contact-us/captcha.php.sample
- **Date:** 2025-10-25
- **Reason:** Sample/test code from Google reCAPTCHA documentation
- **Replacement:** N/A - Not used in production
- **Issue:** Contains placeholder values and TODO comments, appears to be example code for reCAPTCHA Enterprise
- **Note:** Uses different reCAPTCHA key than production code

---

## Instructions

If you need to restore any of these files:
1. Remove the `.deprecated` extension
2. Review the code for security issues before using
3. Ensure it doesn't conflict with existing functionality
