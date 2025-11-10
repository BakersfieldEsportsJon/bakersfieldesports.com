# System Architecture Patterns

## Database Access Patterns

### SQL Pagination
- Use direct integer injection for LIMIT/OFFSET:
```php
$sql = "... LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
```
- Type cast all numeric parameters
- Keep prepared statements for other parameters
- Validate input ranges (e.g., perPage limits)

## Security Patterns

### Content Security Policy
```apache
Content-Security-Policy: default-src "self"; 
                        script-src "self" "unsafe-inline" www.googletagmanager.com connect.facebook.net js.stripe.com; 
                        style-src "self" "unsafe-inline" fonts.googleapis.com; 
                        font-src "self" fonts.gstatic.com; 
                        connect-src "self" *.google-analytics.com *.facebook.com *.doubleclick.net *.stripe.com;
                        frame-src "self" js.stripe.com checkout.stripe.com *.facebook.com;
                        child-src "self" js.stripe.com checkout.stripe.com;
                        img-src "self" data: *.facebook.com *.google-analytics.com *.doubleclick.net;
                        worker-src "self";
                        manifest-src "self";
                        frame-ancestors "none";
                        form-action "self";
                        base-uri "self";
```

- Allow specific external resources as needed
- Restrict other resources to same origin
- Use data: URIs for images only
- Prevent frame embedding
- Restrict form submissions

### Database Security
- Use PDO with prepared statements
- Type cast numeric parameters
- Validate and sanitize input
- Use environment variables for credentials

## UI Patterns

### Hero Sections
- Use inline background-image in HTML for page-specific images:
```html
<section class="hero" style="background-image: url('path/to/image.jpg');">
```
- Keep common hero styles in CSS:
  - background-size: cover
  - background-position: center center
  - background-repeat: no-repeat
  - width: 100%
- Use ::before pseudo-element for overlays
- Maintain responsive behavior

## Error Handling
- Log errors in production
- Display errors in development only
- Use custom error pages
- Implement proper exception handling

## File Organization
- Keep configuration in includes/
- Use .htaccess for server configuration
- Maintain separation of concerns
- Follow consistent naming patterns
