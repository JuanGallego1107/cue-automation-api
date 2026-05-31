<?php

namespace App\Services;

use App\Exceptions\GoogleDriveException;
use App\Models\DocumentSubmission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GoogleDriveService
{
    private string $credentialsPath;
    private string $rootFolderId;
    private ?string $accessToken = null;

    public function __construct()
    {
        $this->credentialsPath = (string) config('services.google_drive.credentials_path');
        $this->rootFolderId    = (string) config('services.google_drive.root_folder_id');
    }

    /**
     * Upload a submitted document to the appropriate Google Drive folder.
     *
     * @param  DocumentSubmission $submission  The submission whose file should be uploaded
     * @return array{
     *   drive_file_id: string,
     *   drive_folder_id: string,
     *   drive_url: string,
     *   drive_filename: string,
     *   folder_path: string,
     *   file_size_bytes: int
     * }
     *
     * @throws GoogleDriveException
     */
    public function upload(DocumentSubmission $submission): array
    {
        Log::channel('daily')->info('[GoogleDrive] Uploading file', [
            'submission_id' => $submission->id,
            'file_path'     => $submission->file_path,
        ]);

        // Load the file from storage
        $fileContents = Storage::get($submission->file_path);

        if ($fileContents === null) {
            throw new GoogleDriveException("File not found in storage: {$submission->file_path}");
        }

        // Authenticate and get access token
        $this->ensureAuthenticated();

        // Build destination path: /{program_name}/{period_name}/{document_type_slug}/
        $submission->loadMissing(['user.program', 'period', 'documentType']);

        $programName  = $submission->user?->program?->name ?? 'Sin Programa';
        $periodName   = $submission->period?->name ?? 'Sin Periodo';
        $documentSlug = $submission->documentType?->slug ?? 'documentos';
        $folderPath   = "{$programName}/{$periodName}/{$documentSlug}";

        // Get or create the destination folder hierarchy
        $folderId = $this->getOrCreateFolder($folderPath, $this->rootFolderId);

        // Upload the file
        $fileName = $submission->original_filename;

        $uploadResponse = Http::withToken($this->accessToken)
            ->withHeaders(['Content-Type' => 'multipart/related; boundary=boundary'])
            ->withBody(
                "--boundary\r\n" .
                "Content-Type: application/json; charset=UTF-8\r\n\r\n" .
                json_encode([
                    'name'    => $fileName,
                    'parents' => [$folderId],
                ]) .
                "\r\n--boundary\r\n" .
                "Content-Type: {$submission->mime_type}\r\n\r\n" .
                $fileContents .
                "\r\n--boundary--",
                'multipart/related; boundary=boundary'
            )
            ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,webViewLink,size');

        if (!$uploadResponse->successful()) {
            Log::channel('daily')->error('[GoogleDrive] Upload failed', [
                'submission_id' => $submission->id,
                'status'        => $uploadResponse->status(),
                'body'          => $uploadResponse->body(),
            ]);
            throw new GoogleDriveException(
                "Google Drive upload failed: HTTP {$uploadResponse->status()} — {$uploadResponse->body()}",
                $uploadResponse->status()
            );
        }

        $uploadedFile = $uploadResponse->json();
        $driveFileId  = $uploadedFile['id'];
        $driveUrl     = $uploadedFile['webViewLink'] ?? "https://drive.google.com/file/d/{$driveFileId}/view";
        $fileSizeBytes = (int) ($uploadedFile['size'] ?? strlen($fileContents));

        Log::channel('daily')->info('[GoogleDrive] Upload successful', [
            'submission_id' => $submission->id,
            'drive_file_id' => $driveFileId,
        ]);

        return [
            'drive_file_id'   => $driveFileId,
            'drive_folder_id' => $folderId,
            'drive_url'       => $driveUrl,
            'drive_filename'  => $fileName,
            'folder_path'     => $folderPath,
            'file_size_bytes' => $fileSizeBytes,
        ];
    }

    /**
     * Traverse the folder path and get or create each segment, returning the final folder ID.
     * Uses a 24-hour cache to avoid redundant Drive API calls.
     *
     * @param  string $path      Slash-separated folder path, e.g. "Program/2025-1/planeador"
     * @param  string $parentId  The Drive ID of the root folder to start from
     * @return string            The Drive folder ID of the deepest folder
     *
     * @throws GoogleDriveException
     */
    private function getOrCreateFolder(string $path, string $parentId): string
    {
        $segments = array_filter(explode('/', $path));
        $currentParentId = $parentId;

        foreach ($segments as $segment) {
            $cacheKey = "gdrive_folder_{$currentParentId}_{$segment}";

            $folderId = Cache::remember($cacheKey, now()->addHours(24), function () use ($segment, $currentParentId) {
                return $this->findOrCreateFolderSegment($segment, $currentParentId);
            });

            $currentParentId = $folderId;
        }

        return $currentParentId;
    }

    /**
     * Find an existing folder by name in a parent, or create it if it doesn't exist.
     *
     * @throws GoogleDriveException
     */
    private function findOrCreateFolderSegment(string $name, string $parentId): string
    {
        // Search for existing folder
        $searchResponse = Http::withToken($this->accessToken)
            ->get('https://www.googleapis.com/drive/v3/files', [
                'q'      => "name='{$name}' and mimeType='application/vnd.google-apps.folder' and '{$parentId}' in parents and trashed=false",
                'fields' => 'files(id,name)',
            ]);

        if (!$searchResponse->successful()) {
            throw new GoogleDriveException("Drive folder search failed: {$searchResponse->body()}");
        }

        $files = $searchResponse->json('files', []);

        if (!empty($files)) {
            return $files[0]['id'];
        }

        // Create the folder
        $createResponse = Http::withToken($this->accessToken)
            ->post('https://www.googleapis.com/drive/v3/files', [
                'name'     => $name,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents'  => [$parentId],
            ]);

        if (!$createResponse->successful()) {
            throw new GoogleDriveException("Drive folder creation failed: {$createResponse->body()}");
        }

        return $createResponse->json('id');
    }

    /**
     * Ensure a valid access token is available by reading the service-account credentials file
     * and exchanging it for a short-lived OAuth 2.0 token.
     *
     * @throws GoogleDriveException
     */
    private function ensureAuthenticated(): void
    {
        if ($this->accessToken !== null) {
            return;
        }

        $cacheKey    = 'gdrive_access_token';
        $cachedToken = Cache::get($cacheKey);

        if ($cachedToken) {
            $this->accessToken = $cachedToken;
            return;
        }

        // Read service-account credentials
        $credentialsFile = base_path($this->credentialsPath);

        if (!file_exists($credentialsFile)) {
            throw new GoogleDriveException("Google Drive credentials file not found at: {$credentialsFile}");
        }

        $credentials = json_decode(file_get_contents($credentialsFile), true);

        if (!isset($credentials['client_email'], $credentials['private_key'])) {
            throw new GoogleDriveException('Invalid Google Drive credentials file format.');
        }

        // Build the JWT for the service account
        $now    = time();
        $header = base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = base64url_encode(json_encode([
            'iss'   => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/drive',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));

        $signingInput = "{$header}.{$claims}";
        openssl_sign($signingInput, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);
        $jwt = "{$signingInput}." . base64url_encode($signature);

        // Exchange JWT for access token
        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);

        if (!$tokenResponse->successful()) {
            throw new GoogleDriveException("Failed to obtain Google Drive access token: {$tokenResponse->body()}");
        }

        $token             = $tokenResponse->json('access_token');
        $expiresIn         = (int) $tokenResponse->json('expires_in', 3600);
        $this->accessToken = $token;

        // Cache the token slightly before expiry
        Cache::put($cacheKey, $token, now()->addSeconds($expiresIn - 60));
    }
}

if (!function_exists('base64url_encode')) {
    /**
     * Base64URL encode a string (no padding, URL-safe).
     */
    function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
