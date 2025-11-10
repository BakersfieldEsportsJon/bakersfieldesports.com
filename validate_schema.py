import json
import re
from datetime import datetime

def extract_json_ld(html_content):
    """Extract JSON-LD scripts from HTML content."""
    pattern = r'<script type="application/ld\+json">(.*?)</script>'
    matches = re.findall(pattern, html_content, re.DOTALL)
    return [json.loads(script.strip()) for script in matches]

def validate_event_schema(schema):
    """Validate Event Schema structure and required fields."""
    errors = []
    warnings = []
    
    # Check if it's an EventSeries
    if schema.get("@type") == "EventSeries":
        required_fields = ["name", "description", "location", "organizer"]
        for field in required_fields:
            if field not in schema:
                errors.append(f"Missing required field for EventSeries: {field}")
        
        # Check subEvents if present
        if "subEvent" in schema:
            for event in schema["subEvent"]:
                validate_single_event(event, errors, warnings)
                
    # Check if it's a single Event
    elif schema.get("@type") == "Event":
        validate_single_event(schema, errors, warnings)
    
    return errors, warnings

def validate_single_event(event, errors, warnings):
    """Validate a single Event object."""
    required_fields = ["name", "description", "startDate", "endDate"]
    for field in required_fields:
        if field not in event:
            errors.append(f"Missing required field for Event: {field}")
    
    # Validate dates
    if "startDate" in event and "endDate" in event:
        try:
            start = datetime.fromisoformat(event["startDate"].replace("Z", "+00:00"))
            end = datetime.fromisoformat(event["endDate"].replace("Z", "+00:00"))
            if end < start:
                errors.append(f"End date {end} is before start date {start} for event: {event.get('name')}")
        except ValueError as e:
            errors.append(f"Invalid date format in event {event.get('name')}: {str(e)}")
    
    # Validate offers
    if "offers" in event:
        offers = event["offers"]
        if not isinstance(offers, dict):
            errors.append(f"Invalid offers format in event {event.get('name')}")
        else:
            required_offer_fields = ["price", "priceCurrency", "availability"]
            for field in required_offer_fields:
                if field not in offers:
                    errors.append(f"Missing required field in offers for event {event.get('name')}: {field}")

def main():
    try:
        # Read the HTML file
        with open('index.html', 'r') as file:
            html_content = file.read()
        
        # Extract and validate all JSON-LD scripts
        schemas = extract_json_ld(html_content)
        
        print(f"\nFound {len(schemas)} JSON-LD scripts")
        
        for i, schema in enumerate(schemas, 1):
            print(f"\nValidating Schema #{i}:")
            print(f"Type: {schema.get('@type', 'Unknown')}")
            
            if schema.get("@type") in ["Event", "EventSeries"]:
                errors, warnings = validate_event_schema(schema)
                
                if errors:
                    print("\nErrors:")
                    for error in errors:
                        print(f"❌ {error}")
                if warnings:
                    print("\nWarnings:")
                    for warning in warnings:
                        print(f"⚠️ {warning}")
                if not errors and not warnings:
                    print("✅ Schema validation passed!")
            
            print("-" * 50)
        
    except Exception as e:
        print(f"Error during validation: {str(e)}")

if __name__ == "__main__":
    main()
