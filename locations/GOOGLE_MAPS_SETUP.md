# Google Maps API Setup for Locations Page

## Quick Setup Instructions

### 1. Get a Google Maps API Key

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project (or select existing)
3. Enable "Maps JavaScript API" for your project
4. Go to "Credentials" and create an API Key
5. (Optional but recommended) Restrict your API key:
   - Add HTTP referrer restrictions (your domain)
   - Limit to Maps JavaScript API only

### 2. Update the Location Page

Open `locations/index.php` and find this line (near the bottom):

```html
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&callback=initMap" async defer></script>
```

Replace `YOUR_GOOGLE_MAPS_API_KEY` with your actual API key:

```html
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyXXXXXXXXXXXXXXXXXXXX&callback=initMap" async defer></script>
```

### 3. Test the Map

1. Visit your locations page: `http://localhost/bakersfield/locations/`
2. You should see an interactive Google Map with a marker at your location
3. Click the marker to see location details
4. Test the "Get Directions" button

## Optional: Custom Map Marker

To use a custom marker icon:

1. Create a marker image (PNG, 50x50px recommended)
2. Save it as `images/map-marker.png`
3. The JavaScript will automatically use it (it's already configured in `js/locations.js`)

## Troubleshooting

**Map shows "This page can't load Google Maps correctly"**
- Check that your API key is correct
- Ensure Maps JavaScript API is enabled in Google Cloud Console
- Check browser console for specific error messages

**Map is gray/blank**
- Verify the latitude/longitude coordinates in the PHP file
- Current coordinates: 35.3917, -119.0134 (Bakersfield eSports Center)

**Need to update location coordinates?**

Edit `locations/index.php` and update:

```php
'lat' => 35.3917,  // Your latitude
'lng' => -119.0134, // Your longitude
```

## Free Tier Information

Google Maps offers **$200/month free credit**, which covers:
- Up to **28,500 map loads per month** (free)
- Plenty for most small to medium websites

## Alternative: No API Key (Temporary)

If you don't want to set up Google Maps API yet:

1. The page will still work, but the map section will show a placeholder
2. All other features (contact, transit info, events) work without the API key
3. You can add the API key later when ready

---

For more info: [Google Maps Platform Documentation](https://developers.google.com/maps/documentation/javascript/get-api-key)
