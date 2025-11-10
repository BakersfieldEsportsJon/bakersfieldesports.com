<?php
/**
 * Schema.org JSON-LD markup generators
 */

if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/config.php';
}

/**
 * Generate Local Business Schema
 */
function getLocalBusinessSchema() {
    return json_encode([
        "@context" => "https://schema.org",
        "@type" => "LocalBusiness",
        "name" => SITE_NAME,
        "image" => SITE_URL . "/images/Asset%205-ts1621173277.png",
        "description" => "Your place to play or compete in your favorite games with friends and take part in exclusive tournaments and leagues.",
        "@id" => SITE_URL,
        "url" => SITE_URL,
        "telephone" => SITE_PHONE_LINK,
        "address" => [
            "@type" => "PostalAddress",
            "streetAddress" => "7104 Golden State Hwy",
            "addressLocality" => "Bakersfield",
            "addressRegion" => "CA",
            "postalCode" => "93308",
            "addressCountry" => "US"
        ],
        "geo" => [
            "@type" => "GeoCoordinates",
            "latitude" => 35.3733,
            "longitude" => -119.0694
        ],
        "openingHoursSpecification" => [
            [
                "@type" => "OpeningHoursSpecification",
                "dayOfWeek" => ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday"],
                "opens" => "12:00",
                "closes" => "23:00"
            ],
            [
                "@type" => "OpeningHoursSpecification",
                "dayOfWeek" => ["Friday", "Saturday"],
                "opens" => "12:00",
                "closes" => "00:00"
            ]
        ],
        "sameAs" => [
            FACEBOOK_URL,
            TWITTER_URL,
            INSTAGRAM_URL,
            TWITCH_URL,
            YOUTUBE_URL,
            TIKTOK_URL
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

/**
 * Generate Organization Schema
 */
function getOrganizationSchema() {
    return json_encode([
        "@context" => "https://schema.org",
        "@type" => "Organization",
        "name" => SITE_NAME,
        "url" => SITE_URL,
        "logo" => SITE_URL . "/images/Asset%205-ts1621173277.png",
        "description" => "Bakersfield's first locally owned and operated eSports and event center, providing a unique entertainment venue for all types of gamers.",
        "address" => [
            "@type" => "PostalAddress",
            "streetAddress" => "7104 Golden State Hwy",
            "addressLocality" => "Bakersfield",
            "addressRegion" => "CA",
            "postalCode" => "93308",
            "addressCountry" => "US"
        ],
        "contactPoint" => [
            "@type" => "ContactPoint",
            "telephone" => SITE_PHONE_LINK,
            "contactType" => "customer service"
        ],
        "sameAs" => [
            FACEBOOK_URL,
            TWITTER_URL,
            INSTAGRAM_URL,
            TWITCH_URL,
            YOUTUBE_URL,
            TIKTOK_URL
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

/**
 * Generate FAQ Schema
 */
function getFAQSchema($faqs = null) {
    if ($faqs === null) {
        // Default FAQs
        $faqs = [
            [
                "question" => "What are your hours of operation?",
                "answer" => "We are open Sunday through Thursday from 12 P.M. to 11 P.M., and Friday & Saturday from 12 P.M. to 12 A.M."
            ],
            [
                "question" => "What gaming equipment do you offer?",
                "answer" => "Our center features high-performance gaming PCs with the latest hardware, next-generation gaming consoles, immersive virtual reality stations, VR escape rooms, and dedicated spaces for trading card games."
            ],
            [
                "question" => "What types of events do you host?",
                "answer" => "We host weekly tournaments (including Friday Night Magic), special gaming events, birthday parties, and casual gaming sessions. Our events include both video gaming and trading card game tournaments."
            ],
            [
                "question" => "Do you offer birthday party packages?",
                "answer" => "Yes, we offer a Standard Party Package for $295 that includes a dedicated party host, 2 hours of gameplay for 10 players (+$10 per extra player), 1 hour in party area, drinks, and 2 large pizzas (Cheese or Pepperoni)."
            ],
            [
                "question" => "What trading card games do you support?",
                "answer" => "We support Magic: The Gathering, Pokémon TCG, Yu-Gi-Oh!, and casual card games like UNO. We host regular tournaments and friendly matches in our dedicated trading card game spaces."
            ],
            [
                "question" => "Where are you located?",
                "answer" => "We are located at 7104 Golden State Hwy, Bakersfield, CA 93308. Our 5,000 square foot facility is in the heart of Bakersfield."
            ],
            [
                "question" => "Do I need to make a reservation?",
                "answer" => "While walk-ins are welcome for casual gaming, we recommend reservations for birthday parties and tournament participation. You can book through our website or call us at " . SITE_PHONE . "."
            ]
        ];
    }

    $mainEntity = [];
    foreach ($faqs as $faq) {
        $mainEntity[] = [
            "@type" => "Question",
            "name" => $faq['question'],
            "acceptedAnswer" => [
                "@type" => "Answer",
                "text" => $faq['answer']
            ]
        ];
    }

    return json_encode([
        "@context" => "https://schema.org",
        "@type" => "FAQPage",
        "mainEntity" => $mainEntity
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

/**
 * Generate Products Schema (for rates/memberships)
 */
function getProductsSchema() {
    return json_encode([
        "@context" => "https://schema.org",
        "@type" => "ItemList",
        "itemListElement" => [
            [
                "@type" => "Product",
                "name" => "Gaming Station Access",
                "description" => "High-performance gaming PCs and next-gen consoles with the latest games",
                "offers" => [
                    "@type" => "AggregateOffer",
                    "lowPrice" => "7.00",
                    "highPrice" => "250.00",
                    "priceCurrency" => "USD",
                    "availability" => "https://schema.org/InStock",
                    "offerCount" => "6",
                    "offers" => [
                        [
                            "@type" => "Offer",
                            "name" => "Unlimited Membership",
                            "price" => "250.00",
                            "priceCurrency" => "USD",
                            "description" => "Unlimited access during operating hours"
                        ],
                        [
                            "@type" => "Offer",
                            "name" => "Hourly Rate",
                            "price" => "7.00",
                            "priceCurrency" => "USD",
                            "description" => "Per hour gaming rate"
                        ],
                        [
                            "@type" => "Offer",
                            "name" => "4-Hour Package",
                            "price" => "24.00",
                            "priceCurrency" => "USD",
                            "description" => "4 hours of gaming"
                        ],
                        [
                            "@type" => "Offer",
                            "name" => "Weekday Day Pass",
                            "price" => "35.00",
                            "priceCurrency" => "USD",
                            "description" => "Monday-Friday, up to 12 hours"
                        ],
                        [
                            "@type" => "Offer",
                            "name" => "Weekend Day Pass",
                            "price" => "40.00",
                            "priceCurrency" => "USD",
                            "description" => "Saturday-Sunday, up to 12 hours"
                        ],
                        [
                            "@type" => "Offer",
                            "name" => "Night Pass",
                            "price" => "14.00",
                            "priceCurrency" => "USD",
                            "description" => "Last 3 hours of operation"
                        ]
                    ]
                ]
            ],
            [
                "@type" => "Product",
                "name" => "Standard Party Package",
                "description" => "Complete gaming party experience with dedicated host, gaming time, and refreshments",
                "offers" => [
                    "@type" => "Offer",
                    "price" => "295.00",
                    "priceCurrency" => "USD",
                    "description" => "Includes: dedicated party host, 2 hours gameplay for 10 players (+$10 per extra player), 1 hour party area, drinks, 2 large pizzas (Cheese or Pepperoni)",
                    "availability" => "https://schema.org/InStock",
                    "url" => SITE_URL . "/rates-parties/#parties"
                ]
            ]
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
