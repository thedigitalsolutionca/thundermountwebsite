<?php

declare(strict_types=1);

/**
 * Canonical store seed data.
 *
 * Product names, models, SKUs and list prices are carried over from the
 * approved Thunder Mounts redesign demo, which sourced them from the public
 * thundermounts.com catalog. No promotional prices, shipping rates, tax rates,
 * inventory counts, dealer discounts or customer records are invented here:
 * those are merchant-controlled settings/data. See README.md.
 */

return [
    'categories' => [
        [
            'slug' => 'opener-mounts',
            'name' => 'Opener Mounts',
            'description' => 'Mounts that attach at the opener motor flanges. TMS, TMX and TML series in four maximum lengths.',
            'image' => '/assets/img/tm-product.jpg',
            'sort_order' => 1,
        ],
        [
            'slug' => 'marantec-mounts',
            'name' => 'Marantec Mounts',
            'description' => 'TMMRT series mounts built for Marantec-style overhead openers.',
            'image' => '/assets/img/tm-tmmrt-badge.jpg',
            'sort_order' => 2,
        ],
        [
            'slug' => 'track-mounts',
            'name' => 'Track Mount Sets',
            'description' => 'TMGT sets that support the overhead door track — one set per door.',
            'image' => '/assets/img/tm-track-glass.jpg',
            'sort_order' => 3,
        ],
        [
            'slug' => 'openers',
            'name' => 'Garage Door Openers',
            'description' => 'Openers available through Thunder Mounts, from leading manufacturers.',
            'image' => '/assets/img/liftmaster-98032.jpg',
            'sort_order' => 4,
        ],
        [
            'slug' => 'garage-doors',
            'name' => 'Garage Doors',
            'description' => 'Aluminum and frameless garage doors, sized and quoted for your opening.',
            'image' => '/assets/img/alumadoors.jpg',
            'sort_order' => 5,
        ],
        [
            'slug' => 'lighting',
            'name' => 'Lighting & Accessories',
            'description' => 'Thunder Lights low-voltage ring lighting, in development.',
            'image' => '/assets/img/tm-chrome-ring.jpg',
            'sort_order' => 6,
        ],
    ],

    'products' => [
        [
            'slug' => 'tms-12-opener-mount',
            'name' => 'TMS-12 Opener Mount',
            'sku' => 'TMS-12',
            'model' => 'TMS-12',
            'brand' => 'Thunder Mounts',
            'category' => 'opener-mounts',
            'price' => '490.00',
            'stock_status' => 'in_stock',
            'is_featured' => 1,
            'short_description' => 'Easy to assemble and install overhead garage door opener mount that attaches at the flanges of the motor. 12-inch maximum length.',
            'description' => 'The TMS-12 replaces conventional angle iron with a clean, symmetrical, engineered mounting solution. It assembles easily and attaches at the flanges of the opener motor. Maximum length: 12 inches. Opener not included.',
            'specs' => [
                'Maximum length' => '12 in.',
                'Attachment' => 'At opener motor flanges',
                'Series' => 'TMS',
                'Opener included' => 'No',
            ],
            'box_contents' => ['Mount set as listed', 'Mounting hardware', 'Online installation instructions'],
            'compatibility' => 'Fits overhead garage door openers that mount at the motor flanges. Contact Thunder Mounts to confirm fitment for a specific opener model.',
            'images' => [
                ['path' => '/assets/img/tm-product.jpg', 'alt' => 'Thunder Mounts TMS series opener mount'],
                ['path' => '/assets/img/tm-tms-install.jpg', 'alt' => 'Thunder Mounts opener mount installed under a garage ceiling'],
                ['path' => '/assets/img/tm-installation-chart.jpg', 'alt' => 'Thunder Mounts length selection chart'],
            ],
            'meta_title' => 'TMS-12 Garage Door Opener Mount | Thunder Mounts',
            'meta_description' => 'TMS-12 overhead garage door opener mount — 12 in. maximum length, attaches at the motor flanges. A symmetrical alternative to angle iron.',
        ],
        [
            'slug' => 'tmx-19-opener-mount',
            'name' => 'TMX-19 Opener Mount',
            'sku' => 'TMX-19',
            'model' => 'TMX-19',
            'brand' => 'Thunder Mounts',
            'category' => 'opener-mounts',
            'price' => '523.00',
            'stock_status' => 'in_stock',
            'is_featured' => 1,
            'short_description' => 'Opener mount attaching at the motor flanges. 19-inch maximum length.',
            'description' => 'The TMX-19 attaches at the flanges of the opener motor and delivers a clean, symmetrical installation. Maximum length: 19 inches. Opener not included.',
            'specs' => [
                'Maximum length' => '19 in.',
                'Attachment' => 'At opener motor flanges',
                'Series' => 'TMX',
                'Opener included' => 'No',
            ],
            'box_contents' => ['Mount set as listed', 'Mounting hardware', 'Online installation instructions'],
            'compatibility' => 'Fits overhead garage door openers that mount at the motor flanges. Contact Thunder Mounts to confirm fitment for a specific opener model.',
            'images' => [
                ['path' => '/assets/img/tm-tms-install.jpg', 'alt' => 'Thunder Mounts TMX opener mount installed'],
                ['path' => '/assets/img/tm-product.jpg', 'alt' => 'Thunder Mounts engineered opener mount'],
                ['path' => '/assets/img/tm-install-white.jpg', 'alt' => 'Thunder Mounts installation in a finished garage'],
            ],
            'meta_title' => 'TMX-19 Garage Door Opener Mount | Thunder Mounts',
            'meta_description' => 'TMX-19 overhead garage door opener mount — 19 in. maximum length, attaches at the motor flanges.',
        ],
        [
            'slug' => 'tml-24-opener-mount',
            'name' => 'TML-24 Opener Mount',
            'sku' => 'TML-24',
            'model' => 'TML-24',
            'brand' => 'Thunder Mounts',
            'category' => 'opener-mounts',
            'price' => '523.00',
            'stock_status' => 'in_stock',
            'is_featured' => 0,
            'short_description' => 'Opener mount attaching at the motor flanges. 24-inch maximum length.',
            'description' => 'The TML-24 attaches at the flanges of the opener motor and is designed for installations that need additional drop. Maximum length: 24 inches. Opener not included.',
            'specs' => [
                'Maximum length' => '24 in.',
                'Attachment' => 'At opener motor flanges',
                'Series' => 'TML',
                'Opener included' => 'No',
            ],
            'box_contents' => ['Mount set as listed', 'Mounting hardware', 'Online installation instructions'],
            'compatibility' => 'Fits overhead garage door openers that mount at the motor flanges. Contact Thunder Mounts to confirm fitment for a specific opener model.',
            'images' => [
                ['path' => '/assets/img/tm-install-white.jpg', 'alt' => 'Thunder Mounts TML opener mount installation'],
                ['path' => '/assets/img/tm-tms-install.jpg', 'alt' => 'Thunder Mounts opener mount under ceiling'],
                ['path' => '/assets/img/tm-product.jpg', 'alt' => 'Thunder Mounts opener mount product'],
            ],
            'meta_title' => 'TML-24 Garage Door Opener Mount | Thunder Mounts',
            'meta_description' => 'TML-24 overhead garage door opener mount — 24 in. maximum length, attaches at the motor flanges.',
        ],
        [
            'slug' => 'tml-29-opener-mount',
            'name' => 'TML-29 Opener Mount',
            'sku' => 'TML-29',
            'model' => 'TML-29',
            'brand' => 'Thunder Mounts',
            'category' => 'opener-mounts',
            'price' => '523.00',
            'stock_status' => 'in_stock',
            'is_featured' => 0,
            'short_description' => 'Opener mount attaching at the motor flanges. 29-inch maximum length.',
            'description' => 'The TML-29 is the longest opener mount in the TML series, attaching at the flanges of the opener motor. Maximum length: 29 inches. Opener not included.',
            'specs' => [
                'Maximum length' => '29 in.',
                'Attachment' => 'At opener motor flanges',
                'Series' => 'TML',
                'Opener included' => 'No',
            ],
            'box_contents' => ['Mount set as listed', 'Mounting hardware', 'Online installation instructions'],
            'compatibility' => 'Fits overhead garage door openers that mount at the motor flanges. Contact Thunder Mounts to confirm fitment for a specific opener model.',
            'images' => [
                ['path' => '/assets/img/tml-29.jpg', 'alt' => 'Thunder Mounts TML-29 opener mount'],
                ['path' => '/assets/img/tm-tms-install.jpg', 'alt' => 'Thunder Mounts long-drop opener mount installed'],
                ['path' => '/assets/img/tm-product.jpg', 'alt' => 'Thunder Mounts mount product detail'],
            ],
            'meta_title' => 'TML-29 Garage Door Opener Mount | Thunder Mounts',
            'meta_description' => 'TML-29 overhead garage door opener mount — 29 in. maximum length, attaches at the motor flanges.',
        ],
        [
            'slug' => 'tmmrt-12-marantec-mount',
            'name' => 'TMMRT-12 Marantec Mount',
            'sku' => 'TMMRT-12',
            'model' => 'TMMRT-12',
            'brand' => 'Thunder Mounts',
            'category' => 'marantec-mounts',
            'price' => '490.00',
            'stock_status' => 'in_stock',
            'is_featured' => 0,
            'short_description' => 'Thunder Mounts mount for Marantec-style openers. 12-inch maximum length.',
            'description' => 'The TMMRT series is designed for Marantec-style overhead openers. The TMMRT-12 has a maximum length of 12 inches. Opener not included. Contact Thunder Mounts to confirm compatibility with your opener.',
            'specs' => [
                'Maximum length' => '12 in.',
                'Series' => 'TMMRT',
                'Intended opener style' => 'Marantec-style overhead openers',
                'Opener included' => 'No',
            ],
            'box_contents' => ['Mount set as listed', 'Mounting hardware', 'Online installation instructions'],
            'compatibility' => 'Intended for Marantec-style overhead openers. Compatibility varies by model — contact Thunder Mounts before ordering.',
            'images' => [
                ['path' => '/assets/img/tm-tmmrt-badge.jpg', 'alt' => 'Thunder Mounts TMMRT mount plate'],
                ['path' => '/assets/img/tm-install-white.jpg', 'alt' => 'Thunder Mounts Marantec-style mount installed'],
                ['path' => '/assets/img/tmmrt-24-2.jpg', 'alt' => 'Thunder Mounts TMMRT mount'],
            ],
            'meta_title' => 'TMMRT-12 Marantec Garage Door Opener Mount | Thunder Mounts',
            'meta_description' => 'TMMRT-12 mount for Marantec-style overhead garage door openers — 12 in. maximum length.',
        ],
        [
            'slug' => 'tmmrt-19-marantec-mount',
            'name' => 'TMMRT-19 Marantec Mount',
            'sku' => 'TMMRT-19',
            'model' => 'TMMRT-19',
            'brand' => 'Thunder Mounts',
            'category' => 'marantec-mounts',
            'price' => '527.00',
            'stock_status' => 'in_stock',
            'is_featured' => 0,
            'short_description' => 'Thunder Mounts mount for Marantec-style openers. 19-inch maximum length.',
            'description' => 'The TMMRT-19 is designed for Marantec-style overhead openers, with a maximum length of 19 inches. Opener not included.',
            'specs' => [
                'Maximum length' => '19 in.',
                'Series' => 'TMMRT',
                'Intended opener style' => 'Marantec-style overhead openers',
                'Opener included' => 'No',
            ],
            'box_contents' => ['Mount set as listed', 'Mounting hardware', 'Online installation instructions'],
            'compatibility' => 'Intended for Marantec-style overhead openers. Contact Thunder Mounts to confirm compatibility before ordering.',
            'images' => [
                ['path' => '/assets/img/tm-install-white.jpg', 'alt' => 'Thunder Mounts TMMRT-19 installation'],
                ['path' => '/assets/img/tm-tmmrt-badge.jpg', 'alt' => 'Thunder Mounts mount plate detail'],
                ['path' => '/assets/img/tmmrt-24-2.jpg', 'alt' => 'Thunder Mounts Marantec mount'],
            ],
            'meta_title' => 'TMMRT-19 Marantec Garage Door Opener Mount | Thunder Mounts',
            'meta_description' => 'TMMRT-19 mount for Marantec-style overhead garage door openers — 19 in. maximum length.',
        ],
        [
            'slug' => 'tmmrt-24-marantec-mount',
            'name' => 'TMMRT-24 Marantec Mount',
            'sku' => 'TMMRT-24',
            'model' => 'TMMRT-24',
            'brand' => 'Thunder Mounts',
            'category' => 'marantec-mounts',
            'price' => '527.00',
            'stock_status' => 'in_stock',
            'is_featured' => 0,
            'short_description' => 'Thunder Mounts mount for Marantec-style openers. 24-inch maximum length.',
            'description' => 'The TMMRT-24 is designed for Marantec-style overhead openers, with a maximum length of 24 inches. Opener not included.',
            'specs' => [
                'Maximum length' => '24 in.',
                'Series' => 'TMMRT',
                'Intended opener style' => 'Marantec-style overhead openers',
                'Opener included' => 'No',
            ],
            'box_contents' => ['Mount set as listed', 'Mounting hardware', 'Online installation instructions'],
            'compatibility' => 'Intended for Marantec-style overhead openers. Contact Thunder Mounts to confirm compatibility before ordering.',
            'images' => [
                ['path' => '/assets/img/tmmrt-24-2.jpg', 'alt' => 'Thunder Mounts TMMRT-24 Marantec mount'],
                ['path' => '/assets/img/tm-tmmrt-badge.jpg', 'alt' => 'Thunder Mounts TMMRT mount detail'],
                ['path' => '/assets/img/tm-install-white.jpg', 'alt' => 'Thunder Mounts installation'],
            ],
            'meta_title' => 'TMMRT-24 Marantec Garage Door Opener Mount | Thunder Mounts',
            'meta_description' => 'TMMRT-24 mount for Marantec-style overhead garage door openers — 24 in. maximum length.',
        ],
        [
            'slug' => 'tmgt-12-track-mount-set',
            'name' => 'TMGT-12 Track Mount Set',
            'sku' => 'TMGT-12',
            'model' => 'TMGT-12',
            'brand' => 'Thunder Mounts',
            'category' => 'track-mounts',
            'price' => '715.00',
            'stock_status' => 'in_stock',
            'is_featured' => 1,
            'short_description' => 'Overhead garage door track mount set for one door. 12-inch maximum length. Instructions, chrome ring and hardware included.',
            'description' => 'The TMGT-12 has a maximum length of twelve inches and is sold as a set for one overhead door. Online instructions, chrome ring and hardware are included.',
            'specs' => [
                'Maximum length' => '12 in.',
                'Sold as' => 'Set for one overhead door',
                'Series' => 'TMGT (track)',
                'Included' => 'Chrome ring, hardware, online instructions',
            ],
            'box_contents' => ['Track mount set for one door', 'Chrome finishing ring', 'Mounting hardware', 'Online installation instructions'],
            'compatibility' => 'Supports the overhead door track on a single garage door. Confirm measurements before ordering.',
            'images' => [
                ['path' => '/assets/img/tm-track-install.jpg', 'alt' => 'Two installers fitting Thunder Mounts track mounts in a garage'],
                ['path' => '/assets/img/tm-track-glass.jpg', 'alt' => 'Thunder Mounts supporting a glass garage door track'],
                ['path' => '/assets/img/tm-chrome-ring.jpg', 'alt' => 'Chrome finishing ring on a Thunder Mounts set'],
                ['path' => '/assets/img/tmgt-1.jpg', 'alt' => 'Thunder Mounts TMGT track mount set'],
            ],
            'meta_title' => 'TMGT-12 Garage Door Track Mount Set | Thunder Mounts',
            'meta_description' => 'TMGT-12 overhead garage door track mount set for one door. Includes chrome ring, hardware and online instructions.',
        ],
        [
            'slug' => 'tmgt-19-track-mount-set',
            'name' => 'TMGT-19 Track Mount Set',
            'sku' => 'TMGT-19',
            'model' => 'TMGT-19',
            'brand' => 'Thunder Mounts',
            'category' => 'track-mounts',
            'price' => '743.00',
            'stock_status' => 'in_stock',
            'is_featured' => 0,
            'short_description' => 'Overhead garage door track mount set for one door. 19-inch maximum length.',
            'description' => 'The TMGT-19 has a maximum length of nineteen inches and is sold as a set for one overhead door. Online instructions, chrome ring and hardware are included.',
            'specs' => [
                'Maximum length' => '19 in.',
                'Sold as' => 'Set for one overhead door',
                'Series' => 'TMGT (track)',
                'Included' => 'Chrome ring, hardware, online instructions',
            ],
            'box_contents' => ['Track mount set for one door', 'Chrome finishing ring', 'Mounting hardware', 'Online installation instructions'],
            'compatibility' => 'Supports the overhead door track on a single garage door. Confirm measurements before ordering.',
            'images' => [
                ['path' => '/assets/img/tm-track-glass.jpg', 'alt' => 'Thunder Mounts TMGT-19 track support'],
                ['path' => '/assets/img/tm-track-install.jpg', 'alt' => 'Thunder Mounts track mount installation'],
                ['path' => '/assets/img/tm-chrome-ring.jpg', 'alt' => 'Chrome ring detail'],
            ],
            'meta_title' => 'TMGT-19 Garage Door Track Mount Set | Thunder Mounts',
            'meta_description' => 'TMGT-19 overhead garage door track mount set for one door, with chrome ring, hardware and online instructions.',
        ],
        [
            'slug' => 'tmgt-24-track-mount-set',
            'name' => 'TMGT-24 Track Mount Set',
            'sku' => 'TMGT-24',
            'model' => 'TMGT-24',
            'brand' => 'Thunder Mounts',
            'category' => 'track-mounts',
            'price' => '743.00',
            'stock_status' => 'in_stock',
            'is_featured' => 1,
            'short_description' => 'Overhead garage door track mount set for one door. 24-inch maximum length.',
            'description' => 'The TMGT-24 has a maximum length of twenty-four inches and is sold as a set for one overhead door. Online instructions, chrome ring and hardware are included.',
            'specs' => [
                'Maximum length' => '24 in.',
                'Sold as' => 'Set for one overhead door',
                'Series' => 'TMGT (track)',
                'Included' => 'Chrome ring, hardware, online instructions',
            ],
            'box_contents' => ['Track mount set for one door', 'Chrome finishing ring', 'Mounting hardware', 'Online installation instructions'],
            'compatibility' => 'Supports the overhead door track on a single garage door. Confirm measurements before ordering.',
            'images' => [
                ['path' => '/assets/img/tm-track-ceiling.jpg', 'alt' => 'Thunder Mounts track mount fixed to a garage ceiling'],
                ['path' => '/assets/img/tm-track-glass.jpg', 'alt' => 'Thunder Mounts track mount on a glass door'],
                ['path' => '/assets/img/tmgt-1.jpg', 'alt' => 'Thunder Mounts TMGT track mount set'],
            ],
            'meta_title' => 'TMGT-24 Garage Door Track Mount Set | Thunder Mounts',
            'meta_description' => 'TMGT-24 overhead garage door track mount set for one door, with chrome ring, hardware and online instructions.',
        ],
        [
            'slug' => 'tmgt-29-track-mount-set',
            'name' => 'TMGT-29 Track Mount Set',
            'sku' => 'TMGT-29',
            'model' => 'TMGT-29',
            'brand' => 'Thunder Mounts',
            'category' => 'track-mounts',
            'price' => '754.00',
            'stock_status' => 'in_stock',
            'is_featured' => 0,
            'short_description' => 'Overhead garage door track mount set for one door. 29-inch maximum length.',
            'description' => 'The TMGT-29 has a maximum length of twenty-nine inches and is sold as a set for one overhead door. Online instructions, chrome ring and hardware are included.',
            'specs' => [
                'Maximum length' => '29 in.',
                'Sold as' => 'Set for one overhead door',
                'Series' => 'TMGT (track)',
                'Included' => 'Chrome ring, hardware, online instructions',
            ],
            'box_contents' => ['Track mount set for one door', 'Chrome finishing ring', 'Mounting hardware', 'Online installation instructions'],
            'compatibility' => 'Supports the overhead door track on a single garage door. Confirm measurements before ordering.',
            'images' => [
                ['path' => '/assets/img/tm-install-white.jpg', 'alt' => 'Thunder Mounts long track mounts in a white garage'],
                ['path' => '/assets/img/tm-track-install.jpg', 'alt' => 'Thunder Mounts track mount set installation'],
                ['path' => '/assets/img/tm-chrome-ring.jpg', 'alt' => 'Chrome ring on a Thunder Mounts set'],
            ],
            'meta_title' => 'TMGT-29 Garage Door Track Mount Set | Thunder Mounts',
            'meta_description' => 'TMGT-29 overhead garage door track mount set for one door, with chrome ring, hardware and online instructions.',
        ],
        [
            'slug' => 'thunder-lights-ring-light',
            'name' => 'Thunder Lights Ring Light',
            'sku' => 'TMRLED',
            'model' => 'TMRLED',
            'brand' => 'Thunder Mounts',
            'category' => 'lighting',
            'price' => '149.99',
            'stock_status' => 'preorder',
            'manage_stock' => 0,
            'is_featured' => 0,
            'short_description' => 'In development: low-voltage ring lights made for Thunder Mounts, connecting to openers with a connection port.',
            'description' => 'Thunder Lights are a new line of low-voltage ring lights made for Thunder Mounts, designed to connect to openers that have a connection port. Now in development — join the notification list and we will email you when ordering opens.',
            'specs' => [
                'Type' => 'Low-voltage LED ring light',
                'Power' => 'Opener connection port',
                'Status' => 'In development',
            ],
            'box_contents' => ['Ring light', 'Connection lead as supplied'],
            'compatibility' => 'Requires an opener with a compatible connection port. Details will be published at launch.',
            'images' => [
                ['path' => '/assets/img/tm-chrome-ring.jpg', 'alt' => 'Chrome finish ring detail used on Thunder Mounts products'],
                ['path' => '/assets/img/tmgt-1.jpg', 'alt' => 'Thunder Mounts accessory detail'],
            ],
            'meta_title' => 'Thunder Lights Ring Light (In Development) | Thunder Mounts',
            'meta_description' => 'Thunder Lights low-voltage ring light for Thunder Mounts, in development. Join the notification list.',
        ],
        [
            'slug' => 'sommer-overhead-opener',
            'name' => 'Sommer Overhead Opener',
            'sku' => 'SOMMER-OH',
            'model' => 'Sommer',
            'brand' => 'Sommer',
            'category' => 'openers',
            'price' => '450.00',
            'stock_status' => 'in_stock',
            'is_featured' => 0,
            'short_description' => 'Sommer overhead garage door opener as listed in the Thunder Mounts catalog.',
            'description' => 'Sommer overhead garage door opener. Contact Thunder Mounts for current model details, drive type, horsepower and warranty information before ordering.',
            'specs' => [
                'Brand' => 'Sommer',
                'Type' => 'Overhead opener',
                'Configuration' => 'Contact us for current model details',
            ],
            'box_contents' => ['Opener unit', 'Manufacturer accessories as supplied'],
            'compatibility' => 'Pairs with Thunder Mounts opener mounts — confirm model fitment with Thunder Mounts.',
            'images' => [
                ['path' => '/assets/img/sommer-opener.jpg', 'alt' => 'Sommer overhead garage door opener installed'],
            ],
            'meta_title' => 'Sommer Overhead Garage Door Opener | Thunder Mounts',
            'meta_description' => 'Sommer overhead garage door opener available through Thunder Mounts. Contact us for current model specifications.',
        ],
        [
            'slug' => '6690l-overhead-opener',
            'name' => '6690L Overhead Opener',
            'sku' => '6690L',
            'model' => '6690L',
            'brand' => 'LiftMaster',
            'category' => 'openers',
            'price' => '650.00',
            'stock_status' => 'in_stock',
            'is_featured' => 0,
            'short_description' => 'Model 6690L overhead garage door opener as listed in the Thunder Mounts catalog.',
            'description' => 'Model 6690L overhead garage door opener. Contact Thunder Mounts for current manufacturer specifications, accessories and warranty information.',
            'specs' => [
                'Model' => '6690L',
                'Type' => 'Overhead opener',
                'Specifications' => 'Contact us for current details',
            ],
            'box_contents' => ['Opener unit', 'Manufacturer accessories as supplied'],
            'compatibility' => 'Pairs with Thunder Mounts opener mounts — confirm model fitment with Thunder Mounts.',
            'images' => [
                ['path' => '/assets/img/6690l-opener.jpg', 'alt' => '6690L overhead garage door opener'],
            ],
            'meta_title' => '6690L Overhead Garage Door Opener | Thunder Mounts',
            'meta_description' => '6690L overhead garage door opener available through Thunder Mounts.',
        ],
        [
            'slug' => 'liftmaster-98032-side-mount',
            'name' => 'LiftMaster 98032 DC Heavy-Duty Side-Mount',
            'sku' => 'LM-98032',
            'model' => '98032',
            'brand' => 'LiftMaster',
            'category' => 'openers',
            'price' => '999.99',
            'stock_status' => 'in_stock',
            'is_featured' => 0,
            'short_description' => 'LiftMaster 98032 DC Heavy Duty Premium Series side-mount sectional opener.',
            'description' => 'LiftMaster 98032 DC Heavy Duty Premium Series Side-Mount Sectional opener. Contact Thunder Mounts for complete manufacturer specifications and installation requirements.',
            'specs' => [
                'Brand' => 'LiftMaster',
                'Model' => '98032',
                'Type' => 'Side-mount (jackshaft) sectional',
            ],
            'box_contents' => ['Opener unit', 'Manufacturer accessories as supplied'],
            'compatibility' => 'Wall-mounted jackshaft opener. Confirm door and ceiling requirements with Thunder Mounts.',
            'images' => [
                ['path' => '/assets/img/liftmaster-98032.jpg', 'alt' => 'LiftMaster 98032 side-mount garage door opener'],
            ],
            'meta_title' => 'LiftMaster 98032 Side-Mount Garage Door Opener | Thunder Mounts',
            'meta_description' => 'LiftMaster 98032 DC Heavy-Duty side-mount sectional garage door opener available through Thunder Mounts.',
        ],
        [
            'slug' => 'marantec-synergy-380',
            'name' => 'Marantec Synergy 380',
            'sku' => 'Marantec380',
            'model' => 'Synergy 380',
            'brand' => 'Marantec',
            'category' => 'openers',
            'price' => '750.00',
            'stock_status' => 'in_stock',
            'is_featured' => 0,
            'short_description' => 'Marantec Synergy 380 overhead garage door opener.',
            'description' => 'Marantec Synergy 380 overhead garage door opener. Contact Thunder Mounts for current manufacturer specifications and accessory details.',
            'specs' => [
                'Brand' => 'Marantec',
                'Model' => 'Synergy 380',
                'Type' => 'Overhead opener',
            ],
            'box_contents' => ['Opener unit', 'Manufacturer accessories as supplied'],
            'compatibility' => 'Suggested pairing: TMMRT series mounts. Confirm fitment with Thunder Mounts.',
            'images' => [
                ['path' => '/assets/img/marantec-synergy-380.jpg', 'alt' => 'Marantec Synergy 380 garage door opener'],
            ],
            'meta_title' => 'Marantec Synergy 380 Garage Door Opener | Thunder Mounts',
            'meta_description' => 'Marantec Synergy 380 overhead garage door opener available through Thunder Mounts.',
        ],
        [
            'slug' => 'genie-edge-mounted-opener',
            'name' => 'Genie Edge Mounted Opener',
            'sku' => 'Genie #41410W',
            'model' => '41410W',
            'brand' => 'Genie',
            'category' => 'openers',
            'price' => '725.00',
            'stock_status' => 'in_stock',
            'is_featured' => 0,
            'short_description' => 'Genie edge-mounted (wall-mount) garage door opener.',
            'description' => 'Genie Edge Mounted Garage Door Opener (Genie #41410W). Contact Thunder Mounts for current manufacturer specifications and installation requirements.',
            'specs' => [
                'Brand' => 'Genie',
                'Model' => '41410W',
                'Type' => 'Edge / wall-mount opener',
            ],
            'box_contents' => ['Opener unit', 'Manufacturer accessories as supplied'],
            'compatibility' => 'Wall-mounted opener. Confirm door and ceiling requirements with Thunder Mounts.',
            'images' => [
                ['path' => '/assets/img/genie-41410w.jpg', 'alt' => 'Genie 41410W wall-mounted garage door opener'],
            ],
            'meta_title' => 'Genie 41410W Edge Mounted Garage Door Opener | Thunder Mounts',
            'meta_description' => 'Genie 41410W edge-mounted (wall-mount) garage door opener available through Thunder Mounts.',
        ],
        [
            'slug' => 'alumadoors-aluminum-garage-door',
            'name' => 'Alumadoors Aluminum Garage Door',
            'sku' => 'ALUMA',
            'model' => 'Alumadoors',
            'brand' => 'Alumadoors',
            'category' => 'garage-doors',
            'price' => '0.00',
            'stock_status' => 'quote',
            'is_featured' => 0,
            'short_description' => 'Aluminum full-view style garage door. Available in multiple door sizes — request a quote.',
            'description' => 'Alumadoors aluminum garage door, available in multiple door sizes. Because every opening and glazing choice is different, this door is quoted individually. Send us your opening dimensions and finish preferences for a quote.',
            'specs' => [
                'Brand' => 'Alumadoors',
                'Style' => 'Aluminum full-view',
                'Options' => 'Door sizes, glazing and finish',
                'Pricing' => 'Quoted per opening',
            ],
            'box_contents' => ['Door sections and hardware as quoted'],
            'compatibility' => 'Sized to your garage opening. Request a quote with your measurements.',
            'images' => [
                ['path' => '/assets/img/alumadoors.jpg', 'alt' => 'Black aluminum full-view garage door'],
            ],
            'meta_title' => 'Alumadoors Aluminum Garage Door | Thunder Mounts',
            'meta_description' => 'Aluminum full-view garage doors from Alumadoors, sized and quoted for your opening by Thunder Mounts.',
        ],
        [
            'slug' => 'wayne-dalton-frameless-garage-door',
            'name' => 'Wayne Dalton Frameless Garage Door',
            'sku' => 'WD-FRAMELESS',
            'model' => 'Frameless',
            'brand' => 'Wayne Dalton',
            'category' => 'garage-doors',
            'price' => '2200.00',
            'stock_status' => 'in_stock',
            'is_featured' => 0,
            'short_description' => 'Wayne Dalton frameless-style garage door as listed in the Thunder Mounts catalog.',
            'description' => 'Wayne Dalton Frameless Garage Door. Sizes, glazing and finish options are confirmed with you before the order is placed. Contact Thunder Mounts with your opening dimensions.',
            'specs' => [
                'Brand' => 'Wayne Dalton',
                'Style' => 'Frameless',
                'Sizes' => 'Confirmed per order',
            ],
            'box_contents' => ['Door sections and hardware as ordered'],
            'compatibility' => 'Sized to your garage opening. Contact us to confirm options before ordering.',
            'images' => [
                ['path' => '/assets/img/wayne-dalton-frameless.jpg', 'alt' => 'Wayne Dalton frameless glass garage door'],
            ],
            'meta_title' => 'Wayne Dalton Frameless Garage Door | Thunder Mounts',
            'meta_description' => 'Wayne Dalton frameless garage door available through Thunder Mounts. Contact us for sizes and options.',
        ],
    ],

    'pages' => [
        [
            'slug' => 'about',
            'title' => 'About Thunder Mounts',
            'show_in_footer' => 0,
            'sort_order' => 1,
            'meta_title' => 'About Thunder Mounts Garage Systems',
            'meta_description' => 'Thunder Mounts builds fits-all overhead garage door and opener mounting systems in Simi Valley, California.',
            'content' => <<<'HTML'
<p class="lead">Thunder Mounts makes the first-of-its-kind fits-all overhead garage mount system. Our solution upgrades the punched-out angle iron found in homes today and replaces it with a precision-engineered mount built for value, symmetry and safety.</p>
<h2>A new standard in garage installation</h2>
<p>For years, overhead installations relied on angle iron — common, but outdated. Thunder Mounts is a modern solution tailored for today’s garages: clean, symmetrical mounting for openers, door tracks, storage and lighting. The result is a safer, more professional garage that enhances property value.</p>
<p>Thunder Mounts are designed, engineered and made in America by Thunder Mount Garage Systems LLC in Simi Valley, California. We also install garage doors and openers from leading manufacturers, and we support installers with a dealer program.</p>
<h2>Value. Symmetry. Safety.</h2>
<ul class="checklist">
  <li><strong>Value</strong> — an engineered mount that elevates the whole installation.</li>
  <li><strong>Symmetry</strong> — clean, balanced lines instead of improvised angle iron.</li>
  <li><strong>Safety</strong> — purpose-built strength for the loads doors and openers place on the ceiling.</li>
</ul>
<h2>Talk to us</h2>
<p>Call <a href="tel:8053386680">805-338-6680</a> or email <a href="mailto:thunder@thundermounts.com">thunder@thundermounts.com</a>. We are at 4212 E Los Angeles Ave, Simi Valley, CA 93063.</p>
HTML,
        ],
        [
            'slug' => 'contact',
            'title' => 'Contact Us',
            'show_in_footer' => 0,
            'sort_order' => 2,
            'meta_title' => 'Contact Thunder Mounts',
            'meta_description' => 'Call 805-338-6680 or email thunder@thundermounts.com. Thunder Mounts is in Simi Valley, California.',
            'content' => <<<'HTML'
<p class="lead">Questions about a mount, an opener or a project? Call, email or send us a message — we are happy to help you choose the right system.</p>
<p><strong>Mobile:</strong> <a href="tel:8053386680">805-338-6680</a><br>
<strong>Office:</strong> <a href="tel:8052622895">805-262-2895</a><br>
<strong>Email:</strong> <a href="mailto:thunder@thundermounts.com">thunder@thundermounts.com</a><br>
<strong>Address:</strong> 4212 E Los Angeles Ave, Simi Valley, CA 93063</p>
<p><strong>Hours:</strong> Monday–Friday 9:00am–4:00pm; Saturday 10:00am–2:00pm; Sunday closed.</p>
HTML,
        ],
        [
            'slug' => 'dealer-program',
            'title' => 'Dealer Program',
            'show_in_footer' => 1,
            'sort_order' => 3,
            'meta_title' => 'Thunder Mounts Dealer Program',
            'meta_description' => 'Apply for a Thunder Mounts dealer account for trade pricing, dealer ordering and support.',
            'content' => <<<'HTML'
<p class="lead">Installers and distributors can apply for a Thunder Mounts dealer account. Approved dealers can sign in to see their pricing, place orders and manage their account online.</p>
<h2>How it works</h2>
<ol>
  <li>Create your account and submit the dealer application with your business details.</li>
  <li>Thunder Mounts reviews the application. We may contact you to confirm your business and resale information.</li>
  <li>Once approved, sign in to the dealer portal to see your pricing and place orders.</li>
</ol>
<p>Dealer pricing and terms are set by Thunder Mounts and shown only to approved dealers after sign-in.</p>
<p><a class="btn btn-primary" href="/dealer/apply">Apply for a dealer account</a></p>
HTML,
        ],
        [
            'slug' => 'shipping',
            'title' => 'Shipping',
            'show_in_footer' => 1,
            'sort_order' => 4,
            'meta_title' => 'Shipping | Thunder Mounts',
            'meta_description' => 'Thunder Mounts orders ship via UPS from California. Shipping is calculated at checkout.',
            'content' => <<<'HTML'
<p class="lead">Thunder Mounts orders ship from California via UPS. Shipping is calculated at checkout based on your delivery address and the method you select. Prices shown exclude sales tax.</p>
<h2>Processing</h2>
<p>Orders are processed during business hours. If an item is on backorder or a lead time applies, we will contact you before shipping.</p>
<h2>Tracking</h2>
<p>When your order ships, we email a tracking number. You can also find it in your account under <a href="/account/orders">Orders</a>.</p>
<h2>Large items</h2>
<p>Garage doors and openers may require freight handling or delivery arrangements. We will confirm the details with you before the order is released.</p>
<p>Questions about a delivery? Call <a href="tel:8053386680">805-338-6680</a> or email <a href="mailto:thunder@thundermounts.com">thunder@thundermounts.com</a>.</p>
HTML,
        ],
        [
            'slug' => 'returns',
            'title' => 'Returns &amp; Warranty',
            'show_in_footer' => 1,
            'sort_order' => 5,
            'meta_title' => 'Returns & Warranty | Thunder Mounts',
            'meta_description' => 'Contact Thunder Mounts about a return, warranty question or problem with your order.',
            'content' => <<<'HTML'
<p class="lead">If something is not right with your order, contact us first so we can help. Please have your order number and a description of the issue ready.</p>
<p><strong>Phone:</strong> <a href="tel:8053386680">805-338-6680</a><br>
<strong>Email:</strong> <a href="mailto:thunder@thundermounts.com">thunder@thundermounts.com</a></p>
<p>Return eligibility, restocking and warranty terms depend on the product and its condition. Thunder Mounts will confirm the applicable terms with you before any return is authorized. Please do not ship an item back without an authorized return.</p>
<p>Manufacturer warranties for openers and doors are provided by the manufacturer; contact us and we will help you with a claim.</p>
HTML,
        ],
        [
            'slug' => 'privacy',
            'title' => 'Privacy Policy',
            'show_in_footer' => 1,
            'sort_order' => 6,
            'meta_title' => 'Privacy Policy | Thunder Mounts',
            'meta_description' => 'How Thunder Mounts collects, uses and protects customer account and order information.',
            'content' => <<<'HTML'
<p class="lead">This policy explains the information Thunder Mounts collects when you use this website and how it is used.</p>
<h2>Information we collect</h2>
<ul>
  <li>Account details you provide: name, email address, phone number, company and password (stored as a one-way hash).</li>
  <li>Order details: products, delivery and billing addresses, order notes and contact information.</li>
  <li>Dealer applications: business details and resale information you submit.</li>
  <li>Technical information needed to operate and secure the site, such as your IP address in server logs.</li>
</ul>
<h2>Payments</h2>
<p>Online payments are processed by PayPal. Your card number and security code are entered into PayPal’s secure payment fields and are not stored on this website. PayPal’s privacy policy applies to the payment information you provide there.</p>
<h2>How we use information</h2>
<p>We use your information to process orders, provide support, manage dealer accounts, send order-related email and keep the site secure. We do not sell your personal information.</p>
<h2>Cookies and sessions</h2>
<p>The site uses a session cookie to keep you signed in and to remember your cart. It does not use advertising cookies.</p>
<h2>Your choices</h2>
<p>You can request access to, correction of, or deletion of your account information by emailing <a href="mailto:thunder@thundermounts.com">thunder@thundermounts.com</a>. Order records may be retained where required for accounting, tax or legal purposes.</p>
<h2>Contact</h2>
<p>Thunder Mount Garage Systems LLC, 4212 E Los Angeles Ave, Simi Valley, CA 93063. Phone <a href="tel:8053386680">805-338-6680</a>.</p>
HTML,
        ],
        [
            'slug' => 'terms',
            'title' => 'Terms of Service',
            'show_in_footer' => 1,
            'sort_order' => 7,
            'meta_title' => 'Terms of Service | Thunder Mounts',
            'meta_description' => 'Terms that apply to use of the Thunder Mounts website and online ordering.',
            'content' => <<<'HTML'
<p class="lead">These terms apply to your use of this website and to orders placed through it. By using the site, you agree to these terms.</p>
<h2>Orders</h2>
<p>Your order is an offer to buy. We confirm acceptance when we send an order confirmation after payment. We may decline or cancel an order if an item is unavailable, if a price or description was listed in error, or if we suspect fraud. If we cancel after payment, we refund the amount paid.</p>
<h2>Pricing</h2>
<p>Prices are shown in US dollars and exclude sales tax unless stated otherwise. Shipping is calculated at checkout. We try to keep prices and product information accurate; if an error is found before your order ships, we will contact you before charging or shipping.</p>
<h2>Product information</h2>
<p>Product images are illustrative. Dimensions, compatibility and included hardware are described on each product page. If you are unsure whether a mount fits your opener or door, contact us before ordering.</p>
<h2>Installation and safety</h2>
<p>Garage doors and openers store significant mechanical energy. Installation should be performed by a qualified installer following the manufacturer’s instructions and local code. Thunder Mounts is not responsible for injury or damage caused by improper installation or by using a product outside its intended application.</p>
<h2>Accounts</h2>
<p>You are responsible for keeping your account credentials secure and for activity under your account. Dealer accounts are subject to approval and to the pricing and terms communicated by Thunder Mounts.</p>
<h2>Contact</h2>
<p>Questions about these terms: <a href="mailto:thunder@thundermounts.com">thunder@thundermounts.com</a>, 805-338-6680.</p>
HTML,
        ],
        [
            'slug' => 'installation',
            'title' => 'Installation &amp; Sizing',
            'show_in_footer' => 1,
            'sort_order' => 8,
            'meta_title' => 'Installation & Sizing | Thunder Mounts',
            'meta_description' => 'How to measure for a Thunder Mounts opener or track mount and what to expect during installation.',
            'content' => <<<'HTML'
<p class="lead">Choosing the right maximum length starts with two measurements: from the ceiling to the opener attachment bolts, or from the ceiling to the door track bolt holes.</p>
<h2>How to measure</h2>
<ol>
  <li>Measure from the ceiling to the garage door track bolt holes, or</li>
  <li>Measure from the ceiling to the door opener attachment bolts.</li>
  <li>Choose the model whose maximum length covers the distance. Opener mounts (TMS, TMX, TML, TMMRT) attach at the opener motor flanges; TMGT sets support the overhead track.</li>
</ol>
<p>For mounting cleaner inside the ceiling, also measure the supports above the drywall and the drywall thickness. If you are between sizes or unsure, call us before ordering.</p>
<p><img src="/assets/img/tm-installation-chart.jpg" alt="Thunder Mounts length chart showing TMGT and TMS/TMX/TML models on a seven-foot door" loading="lazy"></p>
<h2>What’s in the box</h2>
<p>Every mount ships with its mounting hardware and access to online installation instructions. TMGT track sets also include the chrome finishing ring. Openers and doors are not included with mounts.</p>
<h2>Installation instructions</h2>
<p>Model-specific installation instructions are provided online after purchase. Need a copy before you buy, or help planning an installation? Call <a href="tel:8053386680">805-338-6680</a> or email <a href="mailto:thunder@thundermounts.com">thunder@thundermounts.com</a> with your model number.</p>
HTML,
        ],
        [
            'slug' => 'faq',
            'title' => 'Frequently Asked Questions',
            'show_in_footer' => 1,
            'sort_order' => 9,
            'meta_title' => 'Thunder Mounts FAQ',
            'meta_description' => 'Answers about mount lengths, fitment, what is included and dealer accounts.',
            'content' => <<<'HTML'
<details><summary>Is the opener or door included with a mount?</summary><p>No. Mounts are not sold with openers or doors attached or included.</p></details>
<details><summary>How do I choose the right length?</summary><p>Measure from the ceiling to the opener attachment bolts, or from the ceiling to the door track bolt holes, and choose a model whose maximum length covers the distance. The 12, 19, 24 and 29 inch models cover different drops. Contact us if you are unsure.</p></details>
<details><summary>What is included with a TMGT track mount set?</summary><p>A set for one overhead door, the chrome finishing ring, mounting hardware and online installation instructions.</p></details>
<details><summary>Do Thunder Mounts work with my opener?</summary><p>TMS, TMX and TML mounts attach at the opener motor flanges. TMMRT mounts are intended for Marantec-style openers. Because fitment varies by model, contact Thunder Mounts to confirm compatibility before ordering.</p></details>
<details><summary>Do you offer dealer pricing?</summary><p>Yes. Installers and distributors can apply for a dealer account. Approved dealers see their pricing after signing in. Apply on the dealer program page.</p></details>
<details><summary>Where do orders ship from?</summary><p>Orders ship from California via UPS. Shipping is calculated at checkout.</p></details>
<details><summary>How do I get help with an order?</summary><p>Call 805-338-6680 or email thunder@thundermounts.com with your order number.</p></details>
HTML,
        ],
    ],

    'settings' => [
        ['store.name', 'Thunder Mounts', 'store'],
        ['store.legal_name', 'Thunder Mount Garage Systems LLC', 'store'],
        ['store.tagline', 'Value. Symmetry. Safety. VSS™', 'store'],
        ['store.phone', '805-338-6680', 'store'],
        ['store.office_phone', '805-262-2895', 'store'],
        ['store.email', 'thunder@thundermounts.com', 'store'],
        ['store.notification_email', 'thunder@thundermounts.com', 'store'],
        ['store.address', '4212 E Los Angeles Ave, Simi Valley, CA 93063', 'store'],
        ['store.hours', 'Mon–Fri 9:00am–4:00pm · Sat 10:00am–2:00pm', 'store'],
        ['store.currency', 'USD', 'store'],
        ['store.announcement', 'Dealer program now accepting applications — apply online in minutes.', 'store'],
        ['store.announcement_enabled', '1', 'store'],
        ['store.announcement_link', '/dealer/apply', 'store'],
        ['store.newsletter_enabled', '1', 'store'],

        ['seo.title', 'Thunder Mounts | Garage Door & Opener Mounting Systems', 'seo'],
        ['seo.description', 'Engineered mounting systems for garage doors and openers. Replace angle iron with a clean, symmetrical, safe installation. Made in America.', 'seo'],
        ['seo.og_image', '/assets/img/hero-garage.jpg', 'seo'],
        ['seo.organization_type', 'LocalBusiness', 'seo'],

        ['shipping.origin_country', 'US', 'shipping'],
        ['shipping.origin_state', 'CA', 'shipping'],
        ['shipping.origin_city', 'Simi Valley', 'shipping'],
        ['shipping.origin_postcode', '93063', 'shipping'],

        ['tax.enabled', '1', 'tax'],
        ['tax.shipping_taxable', '0', 'tax'],

        ['paypal.enabled', '0', 'paypal'],
        ['paypal.mode', 'sandbox', 'paypal'],
        ['encrypted.paypal.client_id', '', 'paypal'],
        ['encrypted.paypal.client_secret', '', 'paypal'],
        ['encrypted.paypal.webhook_id', '', 'paypal'],
        ['paypal.brand_name', 'Thunder Mounts', 'paypal'],

        ['checkout.manual_payment_enabled', '1', 'checkout'],
        ['checkout.manual_payment_label', 'Pay by invoice', 'checkout'],
        ['checkout.manual_payment_instructions', 'No payment is taken online. We will review your order and email you with payment instructions. We release orders once payment is confirmed.', 'checkout'],
        ['checkout.terms_note', 'By placing your order you agree to our Terms of Service.', 'checkout'],

        ['mail.transport', 'mail', 'mail'],
        ['encrypted.mail_host', '', 'mail'],
        ['mail.port', '587', 'mail'],
        ['mail.encryption', 'tls', 'mail'],
        ['encrypted.mail_username', '', 'mail'],
        ['encrypted.mail_password', '', 'mail'],
        ['mail.from_address', 'no-reply@thundermounts.com', 'mail'],

        ['reviews.auto_approve', '0', 'reviews'],
        ['dealer.auto_approve', '0', 'dealer'],
        ['inventory.low_stock_threshold', '5', 'inventory'],
    ],
];
