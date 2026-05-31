<?php

namespace App\Services;

use App\Exceptions\AzureOcrException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AzureDocumentIntelligenceService
{
    private string $endpoint;
    private string $key;
    private string $model;

    public function __construct()
    {
        $this->endpoint = rtrim((string) config('services.azure.endpoint'), '/');
        $this->key      = (string) config('services.azure.key');
        $this->model    = (string) config('services.azure.model', 'prebuilt-document');
    }

    /**
     * Analyze a document file stored locally and return the full structured Azure result.
     *
     * @param  string $filePath  Relative path in the default storage disk (e.g. "submissions/1/...")
     * @param  string $mimeType  e.g. "application/pdf"
     * @return array             Structured JSON result from Azure
     *
     * @throws AzureOcrException
     */
    public function analyze(string $filePath, string $mimeType): array
    {
        Log::channel('daily')->info('[Azure OCR] Starting analysis', [
            'file_path' => $filePath,
            'mime_type' => $mimeType,
        ]);

        // 1. Read file and encode in base64
        $fileContents = Storage::get($filePath);

        if ($fileContents === null) {
            throw new AzureOcrException("File not found in storage: {$filePath}");
        }

        $base64File = base64_encode($fileContents);

        // 2. Submit analysis request
        $analyzeUrl = "{$this->endpoint}/documentintelligence/documentModels/{$this->model}:analyze?_overload=analyzeDocument&api-version=2024-02-29-preview";

        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $this->key,
            'Content-Type'               => 'application/json',
        ])->post($analyzeUrl, [
            'base64Source' => $base64File,
        ]);

        if (!$response->successful()) {
            Log::channel('daily')->error('[Azure OCR] Submit failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new AzureOcrException(
                "Azure Document Intelligence submit failed: HTTP {$response->status()} — {$response->body()}",
                $response->status()
            );
        }

        // 3. Get the async operation URL from the response header
        $operationUrl = $response->header('Operation-Location');

        if (empty($operationUrl)) {
            throw new AzureOcrException('Azure response did not include an Operation-Location header.');
        }

        Log::channel('daily')->info('[Azure OCR] Polling operation', ['url' => $operationUrl]);

        // 4. Poll until the result is ready
        return $this->pollResult($operationUrl);
    }

    /**
     * Poll an Azure async operation URL until the result status is "succeeded".
     *
     * @param  string $operationUrl  URL from the Operation-Location header
     * @return array                 The "analyzeResult" portion of the Azure response
     *
     * @throws AzureOcrException
     */
    private function pollResult(string $operationUrl): array
    {
        $maxAttempts = 1;
        $attempts    = 0;

        while ($attempts < $maxAttempts) {
            sleep(20);
            $attempts++;

            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->key,
            ])->get($operationUrl);

            if (!$response->successful()) {
                throw new AzureOcrException(
                    "Azure polling request failed: HTTP {$response->status()} — {$response->body()}",
                    $response->status()
                );
            }

            $body   = $response->json();
            $status = $body['status'] ?? 'unknown';

            Log::channel('daily')->info('[Azure OCR] Poll attempt', [
                'attempt' => $attempts,
                'status'  => $status,
            ]);

            if ($status === 'succeeded') {
                return $body['analyzeResult'] ?? $body;
            }

            if ($status === 'failed') {
                $errorMsg = $body['error']['message'] ?? 'Unknown error';
                throw new AzureOcrException("Azure analysis failed: {$errorMsg}");
            }

            // Status is 'running' or 'notStarted' — keep polling
        }

        throw new AzureOcrException(
            "Azure Document Intelligence timed out after {$maxAttempts} polling attempts."
        );
    }
}
