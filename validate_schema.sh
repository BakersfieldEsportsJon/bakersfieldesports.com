#!/bin/bash

# Extract JSON-LD content and validate structure
echo "Validating Schema Markup..."

# Count number of schema blocks
schema_count=$(grep -c 'type="application/ld+json"' index.html)
echo "Found $schema_count schema blocks"

# Check for required Event fields
echo -e "\nChecking Event Schema structure:"

# Check EventSeries fields
echo -e "\n1. Checking EventSeries fields..."
grep -A 2 '"@type": "EventSeries"' index.html > /dev/null && {
    echo "✓ Found EventSeries type"
    grep '"name":' index.html > /dev/null && echo "✓ Found name field"
    grep '"description":' index.html > /dev/null && echo "✓ Found description field"
    grep '"location":' index.html > /dev/null && echo "✓ Found location field"
    grep '"organizer":' index.html > /dev/null && echo "✓ Found organizer field"
} || echo "✗ No EventSeries found"

# Check Event fields
echo -e "\n2. Checking Event fields..."
grep -A 2 '"@type": "Event"' index.html > /dev/null && {
    echo "✓ Found Event type"
    grep '"startDate":' index.html > /dev/null && echo "✓ Found startDate field"
    grep '"endDate":' index.html > /dev/null && echo "✓ Found endDate field"
    grep '"eventStatus":' index.html > /dev/null && echo "✓ Found eventStatus field"
    grep '"eventAttendanceMode":' index.html > /dev/null && echo "✓ Found eventAttendanceMode field"
} || echo "✗ No Event found"

# Check Offer fields
echo -e "\n3. Checking Offer fields..."
grep -A 2 '"@type": "Offer"' index.html > /dev/null && {
    echo "✓ Found Offer type"
    grep '"price":' index.html > /dev/null && echo "✓ Found price field"
    grep '"priceCurrency":' index.html > /dev/null && echo "✓ Found priceCurrency field"
    grep '"availability":' index.html > /dev/null && echo "✓ Found availability field"
} || echo "✗ No Offer found"

# Validate JSON syntax
echo -e "\n4. Validating JSON syntax..."
for script in $(grep -n '<script type="application/ld+json">' index.html | cut -d: -f1); do
    end=$(tail -n +$script index.html | grep -n '</script>' | head -1 | cut -d: -f1)
    end=$((script + end - 1))
    sed -n "${script},${end}p" index.html | grep -v '<script' | grep -v '</script>' | grep -v '^[[:space:]]*$' > temp.json
    if jq empty temp.json 2>/dev/null; then
        echo "✓ JSON syntax is valid"
    else
        echo "✗ JSON syntax error found"
    fi
done

rm -f temp.json

echo -e "\nValidation complete!"
