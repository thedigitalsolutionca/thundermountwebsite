<?php

declare(strict_types=1);

namespace App\Content;

/** Original short guides for the new journal. Do not imply that the Wix blog
 *  archive was imported: its full articles and media remain merchant-owned.
 */
final class Journal
{
    public static function all(): array
    {
        return [
            'mounting-hardware-options' => [
                'slug' => 'mounting-hardware-options',
                'title' => 'Choosing garage door mounting hardware',
                'category' => 'Planning',
                'summary' => 'A good installation starts with a look at what supports the track and the opener — not just which door you choose.',
                'image' => '/assets/img/tm-track-glass.jpg',
                'image_alt' => 'Black track supports above a full-view garage door',
                'sections' => [
                    [
                        'heading' => 'Look above the door',
                        'paragraphs' => [
                            'The track and opener are different parts of the installation. A track mount supports the overhead door track; an opener mount supports the motor assembly. Treat them as separate decisions when you plan your project.',
                            'Conventional punched angle iron can be cut to length on site, but the finished result depends heavily on the layout and the installer. Thunder Mounts offers purpose-built systems with a consistent, symmetrical appearance.',
                        ],
                    ],
                    [
                        'heading' => 'Match the support to the job',
                        'paragraphs' => [
                            'Our TMGT track sets are sold for one door. TMS, TMX and TML opener mounts attach at the opener motor flanges; TMMRT is intended for Marantec-style openers. The mount is not an opener or a garage door.',
                            'Before you order, measure the required drop and check your opener model, track layout and ceiling structure with a qualified installer. The right hardware depends on the actual building and equipment, not a photograph of another garage.',
                        ],
                    ],
                ],
                'link' => '/size-guide',
                'link_label' => 'Compare mount lengths',
            ],
            'choosing-an-opener-mount' => [
                'slug' => 'choosing-an-opener-mount',
                'title' => 'How to choose an opener mount',
                'category' => 'Sizing',
                'summary' => 'Start with the motor attachment points and ceiling-to-opener distance, then check compatibility before ordering.',
                'image' => '/assets/img/tm-tms-install.jpg',
                'image_alt' => 'Ceiling-mounted opener in a garage',
                'sections' => [
                    [
                        'heading' => 'Measure the drop',
                        'paragraphs' => [
                            'Measure from the finished ceiling to the opener motor flange attachment points. Compare that measurement with the maximum lengths listed on the product pages: 12, 19, 24 or 29 inches, depending on the model. A maximum length is not a promise that every opener fits.',
                            'Check what is above the ceiling too. Supporting structure, drywall thickness and the placement of electrical wiring can change how a mount should be fitted.',
                        ],
                    ],
                    [
                        'heading' => 'Check the opener style',
                        'paragraphs' => [
                            'TMS, TMX and TML mounts are intended for overhead openers that attach at the motor flanges. TMMRT mounts are intended for Marantec-style openers. If the motor attachment pattern is unclear, share the opener brand, model and a photo with our team before buying.',
                            'Garage door systems carry considerable force. Follow the instructions for the exact mount and opener, and have a qualified installer handle work you are not trained to do.',
                        ],
                    ],
                ],
                'link' => '/resources/instructions',
                'link_label' => 'Prepare for installation',
            ],
            'track-mounting-basics' => [
                'slug' => 'track-mounting-basics',
                'title' => 'Track mounting: what to plan first',
                'category' => 'Installation',
                'summary' => 'Plan the track layout, ceiling conditions and required drop before starting overhead work.',
                'image' => '/assets/img/after.jpg',
                'image_alt' => 'Overhead garage door track supported above fitted cabinetry',
                'sections' => [
                    [
                        'heading' => 'Start with the structure',
                        'paragraphs' => [
                            'Track support is part of the full garage door system. A clean finish matters, but secure attachment to suitable structure comes first. Check the mounting surface and the door manufacturer’s requirements before deciding where the hardware will sit.',
                            'TMGT sets support the overhead track and are offered in four maximum lengths. The correct model depends on the ceiling-to-track distance and how the door moves through its full travel.',
                        ],
                    ],
                    [
                        'heading' => 'Use the right instructions',
                        'paragraphs' => [
                            'This journal is a planning overview, not an installation manual. Garage door springs and moving components can cause serious injury. Ask for the instructions for your exact model and follow the door and opener manufacturers’ guidance; if in doubt, hire a qualified installer.',
                            'Have your door height, track configuration, photos and measured drop ready when you contact Thunder Mounts. We can help you choose a suitable starting point.',
                        ],
                    ],
                ],
                'link' => '/resources/instructions',
                'link_label' => 'Request model-specific instructions',
            ],
        ];
    }

    public static function find(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }

    public static function legacyPath(string $slug): ?string
    {
        return match ($slug) {
            'choosing-garage-door-mounting-hardware-options' => '/blog/mounting-hardware-options',
            'how-to-choose-the-right-garage-door-opener-and-mounting-system-for-safety-and-style' => '/blog/choosing-an-opener-mount',
            'installing-an-overhead-track-installation-a-step-by-step-guide' => '/blog/track-mounting-basics',
            default => null,
        };
    }
}
