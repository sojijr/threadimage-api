<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ImageService
{
    /**
     * Extract profile image from HTML and convert to base64
     */
    public function extractAndConvertProfileImage($html, $patterns = null)
    {
        if ($patterns === null) {
            $patterns = [
                '/https?:\/\/scontent-[^.]+\.cdninstagram\.com\/v\/t51\.2885-19\/[^?\s]+\?[^\'"\s]*/i',
                '/https?:\/\/instagram\.[^.]+\.fna\.fbcdn\.net\/v\/t51\.2885-19\/[^?\s]+\?[^\'"\s]*/i'
            ];
        }

        $profilePictureLinks = [];

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $html, $matches);
            if (!empty($matches[0])) {
                foreach ($matches[0] as $link) {
                    $profilePictureLinks[] = $link;
                }
            }
        }

        if (empty($profilePictureLinks)) {
            return null;
        }

        return $this->convertImageToBase64($profilePictureLinks[0]);
    }

    /**
     * Extract content images from HTML
     */
    public function extractContentImages($html, $pattern = null)
    {
        if ($pattern === null) {
            $pattern = '/https?:\/\/instagram\.[^.]+\.fna\.fbcdn\.net\/v\/t51\.(?!2885-19)[^?\s]+\?[^\'"\s]*/i';
        }

        $imageLinks = [];
        preg_match_all($pattern, $html, $matches);

        if (!empty($matches[0])) {
            foreach ($matches[0] as $link) {
                // Filter out small thumbnails
                if (strpos($link, '640x640') === false) {
                    $imageLinks[] = $link;
                }
            }
        }

        return $imageLinks;
    }

    /**
     * Convert image URL to base64 data URL
     */
    public function convertImageToBase64($imageUrl)
    {
        if (empty($imageUrl)) {
            return null;
        }

        // Clean the URL
        $cleanUrl = rtrim($imageUrl, '"');
        $cleanUrl = html_entity_decode($cleanUrl);

        try {
            $imageContents = file_get_contents($cleanUrl);
            if ($imageContents !== false) {
                return 'data:image/jpeg;base64,' . base64_encode($imageContents);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch image: ' . $e->getMessage(), ['url' => $cleanUrl]);
        }

        return null;
    }

    /**
     * Create a shortened URL for base64 data by storing it temporarily
     */
    public function createShortUrl($base64Data, $type = 'image')
    {
        if (empty($base64Data)) {
            return null;
        }

        $shortId = substr(md5($base64Data), 0, 8);
        $cacheKey = "short_url_{$type}_{$shortId}";

        // Store in cache for 30 minutes
        cache()->put($cacheKey, $base64Data, 1800);

        return url("api/media/{$type}/{$shortId}");
    }

    /**
     * Serve media from shortened URLs
     */
    public function serveMedia($type, $shortId)
    {
        $cacheKey = "short_url_{$type}_{$shortId}";
        $base64Data = cache()->get($cacheKey);

        if (!$base64Data) {
            return response()->json(['error' => 'Media not found or expired'], 404);
        }

        // Extract the actual base64 content (remove data:image/jpeg;base64, prefix)
        $base64Content = substr($base64Data, strpos($base64Data, ',') + 1);
        $imageData = base64_decode($base64Content);

        return response($imageData)
            ->header('Content-Type', 'image/jpeg')
            ->header('Cache-Control', 'public, max-age=1800');
    }

    /**
     * Process profile image and return either base64 or short URL
     */
    public function processProfileImage($html, $returnType = 'base64', $patterns = null)
    {
        $base64Data = $this->extractAndConvertProfileImage($html, $patterns);

        if (!$base64Data) {
            return null;
        }

        if ($returnType === 'short_url') {
            return $this->createShortUrl($base64Data, 'profile');
        }

        return $base64Data;
    }

    /**
     * Process content images and return either base64 or short URLs
     */
    public function processContentImages($html, $returnType = 'short_url', $pattern = null)
    {
        $imageLinks = $this->extractContentImages($html, $pattern);

        if (empty($imageLinks)) {
            return null;
        }

        $base64Data = $this->convertImageToBase64($imageLinks[0]);

        if (!$base64Data) {
            return null;
        }

        if ($returnType === 'short_url') {
            return $this->createShortUrl($base64Data, 'image');
        }

        return $base64Data;
    }
}
