<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Threads Processing",
 *     description="API Endpoints for processing Threads posts and profiles"
 * )
 */

class ApiDocumentation
{
    /**
     * Process Threads Post URL
     * 
     * Extract images, profile information, and text content from a Threads post URL
     * 
     * @OA\Post(
     *     path="/api/threads-post",
     *     summary="Process Threads post URL",
     *     description="Extracts images, profile information, and text content from a given Threads post URL",
     *     tags={"Threads Processing"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="threads-url",
     *                     type="string",
     *                     format="url",
     *                     description="The Threads post URL to process",
     *                     example="https://www.threads.com/@username/post/123456789"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully processed Threads URL",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="text", type="string", description="Extracted post text"),
     *                 @OA\Property(property="username", type="string", description="Thread author username"),
     *                 @OA\Property(property="profile_image_url", type="string", format="url", description="Profile image URL"),
     *                 @OA\Property(property="image_post_url", type="string", format="url", description="Post content image URL")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - invalid URL",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The threads-url field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="threads-url",
     *                     type="array",
     *                     @OA\Items(type="string", example="The threads-url field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error during processing",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error processing Threads URL: ...")
     *         )
     *     )
     * )
     */
    public function threads_post() {}

    /**
     * Process Threads Profile URL
     * 
     * Extract profile information and data from a Threads user profile URL
     * 
     * @OA\Post(
     *     path="/api/threads-profile",
     *     summary="Process Threads profile URL",
     *     description="Extracts profile information and data from a given Threads user profile URL",
     *     tags={"Threads Processing"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="profile-url",
     *                     type="string",
     *                     format="url",
     *                     description="The Threads profile URL to process",
     *                     example="https://www.threads.com/@username"
     *                 )
     *             )
     *           )
     *         ),  
     *         @OA\Response(
     *         response=200,
     *         description="Successfully processed Threads profile URL",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="username", type="string", description="Thread author username"),
     *                 @OA\Property(property="display_name", type="string", description="Thread author display name"),
     *                 @OA\Property(property="bio", type="string", description="Thread author biography"),
     *                 @OA\Property(property="follower_count", type="string", description="Number of followers"),
     *                 @OA\Property(property="profile_image_url", type="string", format="url", description="Profile image URL"),
     *                 @OA\Property(property="url", type="string", format="url", description="Profile URL"),
     *                 description="Profile data extracted from the Threads profile page"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - invalid URL",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The profile-url field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="profile-url",
     *                     type="array",
     *                     @OA\Items(type="string", example="The profile-url field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error during processing",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error processing Threads profile: ...")
     *         )
     *     )
     * )
     */
    public function threads_profile() {}
}
