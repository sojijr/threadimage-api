<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DOMDocument;
use App\Services\ImageService;
use App\Swagger\ApiDocumentation;
use Illuminate\Support\Facades\Log;

/**
 * @OA\PathItem(path="/threads-post")
 */

class ThreadImageController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * @see ApiDocumentation::threads_post
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

        // Get profile image URL using the service
        $profileImageUrl = $this->imageService->processProfileImage($html, 'short_url');

        // no profile image found, fetch from user's profile page
        if (!$profileImageUrl && !empty($username)) {
            $profileImageUrl = $this->fetchProfileImageFromUserPage($username);
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

        // Process content images using the service
        $imageDataUrl = $this->imageService->processContentImages($html, 'short_url');

        return [
            'text' => $text,
            'username' => $username,
            'profile_image_url' => $profileImageUrl,
            'image_post_url' => $imageDataUrl
        ];
    }

    public function serveMedia($type, $shortId)
    {
        return $this->imageService->serveMedia($type, $shortId);
    }

    /**
     * Fetch profile image from user's Threads profile page
     */
    private function fetchProfileImageFromUserPage($username)
    {
        if (empty($username)) {
            return null;
        }

        $profileUrl = "https://www.threads.com/{$username}";

        try {
            $curl = curl_init($profileUrl);

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'Mozilla/5.0',
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $profileHtml = curl_exec($curl);

            if (curl_errno($curl)) {
                curl_close($curl);
                return null;
            }

            curl_close($curl);

            $profileImageUrl = $this->imageService->processProfileImage($profileHtml, 'short_url');

            return $profileImageUrl;
        } catch (\Exception $e) {
            Log::warning('Failed to fetch profile image from user page: ' . $e->getMessage(), [
                'username' => $username,
                'profile_url' => $profileUrl
            ]);
            return null;
        }
    }
}
