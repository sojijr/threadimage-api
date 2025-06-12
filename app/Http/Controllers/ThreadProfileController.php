<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ImageService;
use App\Swagger\ApiDocumentation;

/**
 * @OA\PathItem(path="/threads-profile")
 */

class ThreadProfileController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * @see ApiDocumentation::threads_profile
     */
    public function index(Request $request)
    {
        $request->validate([
            'profile-url' => 'required|url'
        ]);

        $profileUrl = $request->input('profile-url');

        try {
            $data = $this->processThreadsProfile($profileUrl);
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing Threads profile: ' . $e->getMessage()
            ], 500);
        }
    }

    public function processThreadsProfile($profileUrl)
    {
        $ch = curl_init();

        // curl options to mimic a real browser
        curl_setopt($ch, CURLOPT_URL, $profileUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Accept-Encoding: gzip, deflate',
            'DNT: 1',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Cache-Control: max-age=0'
        ]);

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($html === false) {
            return response()->json([
                'error' => 'Failed to fetch profile data',
                'details' => $error ?: 'Unknown curl error'
            ], 400);
        }

        if ($httpCode !== 200) {
            return response()->json([
                'error' => 'HTTP request failed',
                'http_code' => $httpCode,
                'details' => "Received HTTP status code: $httpCode"
            ], 400);
        }

        // Parse HTML to extract profile information
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        return [
            'username' => $this->extractUsername($profileUrl),
            'display_name' => $this->extractDisplayName($xpath),
            'bio' => $this->extractBio($html),
            'follower_count' => $this->extractFollowerCount($xpath),
            'profile_image_url' => $this->imageService->processProfileImage($html, 'short_url'),
            'url' => $profileUrl,
        ];
    }

    private function extractUsername($threadsUrl)
    {
        $parsedUrl = parse_url($threadsUrl);
        $path = explode('/', $parsedUrl['path']);
        $username = isset($path[1]) ? $path[1] : '';
        return $username;
    }

    private function extractDisplayName($xpath)
    {
        $nodes = $xpath->query('//title/text()');
        if ($nodes->length > 0) {
            $title = $nodes->item(0)->nodeValue;
            $title = preg_replace('/\s*\|\s*Threads.*$/i', '', $title);
            $title = preg_replace('/\s*on\s+Threads.*$/i', '', $title);
            $title = preg_replace('/\s*\(@[^)]*\).*$/i', '', $title);
            return trim($title) ?: null;
        }

        return null;
    }

    private function extractBio($html)
    {
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)["\'][^>]*>/i', $html, $matches)) {
            $description = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
            if ($description && !empty($description)) {
                $description = preg_replace('/^[\d.]+[KMB]?\s+Followers?\s*•\s*[\d.]+[KMB]?\s+Threads?\s*•\s*/i', '', $description);

                $description = preg_replace('/\.\s*See the latest conversations with @[^.]*\.$/', '', $description);

                $description = trim($description);

                if ($description && !empty($description)) {
                    return $description;
                }
            }
        }

        return null;
    }

    private function extractFollowerCount($xpath)
    {
        $scriptTags = $xpath->query('//script');
        foreach ($scriptTags as $script) {
            $content = $script->textContent;
            if (preg_match('/follower[^0-9]*(\d+)/', $content, $matches)) {
                return (int)$matches[1];
            }
        }

        return null;
    }
}
