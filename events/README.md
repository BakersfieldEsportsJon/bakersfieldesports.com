# Local Development Setup

To view the events page locally:

1. Make sure your web server (Apache/XAMPP/WAMP) is running
2. Access the page through your web server URL, for example:
   - If using XAMPP: `http://localhost/bakersfield-esports/events/`
   - If using WAMP: `http://localhost:8080/bakersfield-esports/events/`
   - If using built-in PHP server: `php -S localhost:8000` in the project root directory, then visit `http://localhost:8000/events/`

## Important Notes

- Do not open the PHP files directly in your browser (file:/// URLs won't work)
- The events page must be accessed through index.php, not index.html
- Make sure your web server has PHP enabled
- The events.json file should be writable by your web server user

## File Structure

```
events/
├── data/
│   ├── events.json         # Event data
│   └── get_events.php      # JSON data endpoint
├── js/
│   └── events.js          # JavaScript functionality
├── index.php              # Main events page
└── README.md             # This file
```

## Adding/Updating Events

1. Edit `data/events.json`
2. Add new events in this format:
```json
{
  "id": "unique-event-id",
  "name": "Event Name",
  "location": "Location Name",
  "address": "Full Address",
  "date": "YYYY-MM-DDTHH:MM:SS",
  "image": "../images/events/image.png",
  "entryCost": "cost-in-dollars",
  "registrationLink": "registration-url",
  "notes": "Optional notes"
}
```
3. Place event images in `../images/events/`
