<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DOMDocument;
use App\Services\ImageService;

class ThreadImageController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Handle the threads URL input and process the data
     */
    public function index(Request $request)
    {
        $request->validate([
            'threads-url' => 'required|url'
        ]);

        $threadsUrl = $request->input('threads-url');

        try {
            $data = $this->processThreadsUrl($threadsUrl);
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing Threads URL: ' . $e->getMessage()
            ], 500);
        }
    }

    private function processThreadsUrl($threadsUrl)
    {
        // Initialize cURL session
        $curl = curl_init($threadsUrl);

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $html = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new \Exception("cURL Error #" . curl_errno($curl) . ": " . curl_error($curl));
        }

        curl_close($curl);

        // Extract @username from the Threads URL
        $parsedUrl = parse_url($threadsUrl);
        $path = explode('/', $parsedUrl['path']);
        $username = isset($path[1]) ? $path[1] : '';

        // Extract image links using regular expressions
        $imageLinks = $this->imageService->extractContentImages($html);

        // Get profile image URL using the service
        $profileImageUrl = $this->imageService->processProfileImage($html, 'short_url');

        // Use default profile image if none found
        if (!$profileImageUrl) {
            $defaultProfileUrl = "https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png";
            $base64Data = $this->imageService->convertImageToBase64($defaultProfileUrl);
            $profileImageUrl = $this->imageService->createShortUrl($base64Data, 'profile');
        }

        $doc = new DOMDocument();
        @$doc->loadHTML($html);

        $ogDescription = null;
        $metaTags = $doc->getElementsByTagName('meta');
        foreach ($metaTags as $tag) {
            if ($tag->getAttribute('property') === 'og:description') {
                $ogDescription = $tag->getAttribute('content');
                break;
            }
        }

        if ($ogDescription === null) {
            throw new \Exception("Threads page not found :(");
        }

        $text = $ogDescription;

        $text = htmlspecialchars_decode($text, ENT_QUOTES);

        // Check if there are image links and if the content has any links
        $hasImageLinks = !empty($imageLinks);
        $hasContentLinks = preg_match('/<a\s*[^>]*href="([^"]*)"[^>]*>.*<\/a>/i', $text);

        // Process content images using the service
        $imageDataUrl = $this->imageService->processContentImages($html, 'short_url');

        return [
            'text' => $text,
            'username' => $username,
            'image_url' => $imageDataUrl,
            'profile_image_url' => $profileImageUrl,
            'has_image_links' => $hasImageLinks,
            'has_content_links' => $hasContentLinks,
            'show_image' => !empty($imageLinks) && $hasImageLinks && !$hasContentLinks
        ];
    }

    /**
     * Serve media from shortened URLs
     */
    public function serveMedia($type, $shortId)
    {
        return $this->imageService->serveMedia($type, $shortId);
    }
}
