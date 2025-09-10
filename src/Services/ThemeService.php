<?php

namespace App\Services;

class ThemeService
{
    public static function getAllThemes(): array
    {
        return [
            [
                'id' => 'rosie',
                'name' => 'Rosie the Unicorn',
                'background_color' => '#FFB6C1',
                'character_image' => '1.png',
                'description' => 'Magical and sweet'
            ],
            [
                'id' => 'hooty',
                'name' => 'Hooty the Owl',
                'background_color' => '#40E0D0',
                'character_image' => '2.png',
                'description' => 'Wise and peaceful'
            ],
            [
                'id' => 'bruno',
                'name' => 'Bruno the Bear',
                'background_color' => '#DEB887',
                'character_image' => '3.png',
                'description' => 'Warm and comforting'
            ],
            [
                'id' => 'whiskers',
                'name' => 'Whiskers the Seal',
                'background_color' => '#D3D3D3',
                'character_image' => '4.png',
                'description' => 'Gentle and calm'
            ],
            [
                'id' => 'penny',
                'name' => 'Penny the Pig',
                'background_color' => '#F08080',
                'character_image' => '5.png',
                'description' => 'Cheerful and bright'
            ],
            [
                'id' => 'lily',
                'name' => 'Lily the Frog',
                'background_color' => '#98FB98',
                'character_image' => '6.png',
                'description' => 'Fresh and hopeful'
            ],
            [
                'id' => 'buzzy',
                'name' => 'Buzzy the Bee',
                'background_color' => '#FFD700',
                'character_image' => '7.png',
                'description' => 'Sunny and energetic'
            ],
            [
                'id' => 'panda',
                'name' => 'Panda the Panda',
                'background_color' => '#F5F5F5',
                'character_image' => '8.png',
                'description' => 'Classic and timeless'
            ]
        ];
    }

    public static function getThemeById(string $themeId): ?array
    {
        $themes = self::getAllThemes();
        foreach ($themes as $theme) {
            if ($theme['id'] === $themeId) {
                return $theme;
            }
        }
        return null;
    }
}