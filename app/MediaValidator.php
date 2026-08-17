<?php

declare(strict_types=1);

namespace App;

final class MediaValidator
{
    private const RATIOS = [
        ['label' => '9:16', 'width' => 9, 'height' => 16],
        ['label' => '2:3', 'width' => 2, 'height' => 3],
        ['label' => '4:5', 'width' => 4, 'height' => 5],
        ['label' => '1:1', 'width' => 1, 'height' => 1],
        ['label' => '16:9', 'width' => 16, 'height' => 9],
        ['label' => '1.91:1', 'width' => 191, 'height' => 100],
    ];

    public static function inspect(array $media): array
    {
        $dimensions = self::dimensions($media);
        $ratio = $dimensions === null ? self::ratioFromMetadata($media) : self::nearestRatio(...$dimensions);
        $reason = self::invalidReason($media, $dimensions, $ratio);

        return [
            'selectable' => $reason === null,
            'reason' => $reason,
            'ratio' => $ratio['label'] ?? null,
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
        ];
    }

    private static function invalidReason(array $media, ?array $dimensions, ?array $ratio): ?string
    {
        if (($media['deleted'] ?? false) === true) {
            return 'Deleted media cannot be selected.';
        }

        $type = strtoupper(trim((string)($media['media_type'] ?? '')));
        if (!in_array($type, ['IMAGE', 'VIDEO', 'GIF'], true)) {
            return 'Unsupported media type.';
        }

        $status = strtoupper(trim((string)($media['media_status'] ?? '')));
        if (in_array($type, ['VIDEO', 'GIF'], true) && $status !== '' && $status !== 'TRANSCODE_COMPLETED') {
            return 'Media processing is not complete.';
        }

        if ($dimensions !== null && $ratio === null) {
            return 'Unsupported aspect ratio.';
        }

        if ($dimensions !== null && $ratio !== null && $dimensions[0] < 600) {
            $minimumHeight = (int)floor(600 * $ratio['height'] / $ratio['width']);

            if ($media['media_key'] == '13_2014963757282443264') {
                $a = 1;
            }

            return sprintf(
                'Image too small. Minimum 600×%d pixels required for %s.',
                $minimumHeight,
                $ratio['label']
            );
        }

        return null;
    }

    private static function dimensions(array $media): ?array
    {
        $width = filter_var($media['width'] ?? null, FILTER_VALIDATE_INT);
        $height = filter_var($media['height'] ?? null, FILTER_VALIDATE_INT);
        if ($width !== false && $height !== false && $width > 0 && $height > 0) {
            return [(int)$width, (int)$height];
        }

        $aspectRatio = trim((string)($media['aspect_ratio'] ?? ''));
        if (preg_match('/^(\d+):(\d+)$/', $aspectRatio, $matches)) {
            $ratioWidth = (int)$matches[1];
            $ratioHeight = (int)$matches[2];

            // X sometimes returns actual media dimensions in aspect_ratio, e.g. 540:937.
            if ($ratioWidth > 10 || $ratioHeight > 10) {
                return [$ratioWidth, $ratioHeight];
            }

            // Values such as 1:1 and 9:16 describe only the ratio. A size found
            // in media_url is a transcoded rendition and is not the source size
            // used by the X Ads media picker for validation.
            return null;
        }

        foreach (['media_url', 'poster_media_url'] as $field) {
            $url = (string)($media[$field] ?? '');
            if (preg_match('~/(\d{2,5})x(\d{2,5})/~', $url, $matches)) {
                return [(int)$matches[1], (int)$matches[2]];
            }
        }

        return null;
    }

    private static function ratioFromMetadata(array $media): ?array
    {
        $value = trim((string)($media['aspect_ratio'] ?? ''));
        if (!preg_match('/^(\d+(?:\.\d+)?):(\d+(?:\.\d+)?)$/', $value, $matches)) {
            return null;
        }

        return self::nearestRatio((float)$matches[1], (float)$matches[2]);
    }

    private static function nearestRatio(float $width, float $height): ?array
    {
        if ($width <= 0 || $height <= 0) {
            return null;
        }

        $value = $width / $height;
        $nearest = null;
        $smallestDifference = PHP_FLOAT_MAX;

        foreach (self::RATIOS as $ratio) {
            $expected = $ratio['width'] / $ratio['height'];
            $difference = abs($value - $expected) / $expected;
            if ($difference < $smallestDifference) {
                $smallestDifference = $difference;
                $nearest = $ratio;
            }
        }

        return $smallestDifference <= 0.06 ? $nearest : null;
    }
}
