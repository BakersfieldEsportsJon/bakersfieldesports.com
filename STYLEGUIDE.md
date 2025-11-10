# CSS Style Guide

This project uses a small set of shared stylesheets. All color and spacing values are defined in `css/variables.css`.

## File Structure
- `css/variables.css` – Brand colors, spacing scale and breakpoints.
- `css/core.css` – Base elements and utility classes.
- `css/components.css` – Layout components such as navbar and hero sections.
- `css/admin.css` – Styles for the admin dashboard.
- `css/pages/` – Page level overrides.

## Naming Convention
Classes use **kebab‑case** with BEM style modifiers if needed (e.g. `hero-home`). Avoid inline styles and keep selectors descriptive.

## Linting
Run Stylelint via npm script:

```bash
npm run lint:css
```

Only source CSS files under `css/` are linted. Generated or vendor styles are
listed in `.stylelintignore`.

If a rule cannot be fixed automatically it will appear in the console. Add a `TODO` comment with details for human review.

## Brand Colors
- `--primary-color: #EC194D`
- `--dark-bg: #030202`
- `--light-color: #FBFBFB`

These variables should be used instead of hard‑coded values.
