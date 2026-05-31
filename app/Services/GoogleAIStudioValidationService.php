<?php

namespace App\Services;

use App\Exceptions\GoogleAIStudioValidationException;
use App\Models\DocumentType;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleAIStudioValidationService
{
    private string $apiKey;

    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent';

    /** Required top-level fields in the AI JSON response */
    private const REQUIRED_FIELDS = [
        'document_type',
        'is_valid',
        'confidence_score',
        'teacher',
        'missing_fields',
        'checks_performed',
        'checks_passed',
        'checks_failed',
        'issues',
        'inconsistencies',
        'summary',
        'recommendations',
    ];

    public function __construct()
    {
        $this->apiKey = (string) config('services.google_ai_studio.api_key');
    }

    /**
     * Send OCR data to Google AI Studio and obtain a structured validation result.
     *
     * @param  array        $ocrData       Structured data returned by Azure Document Intelligence
     * @param  DocumentType $documentType  The document type with its validation_rules
     * @return array                        Structured validation result from the AI
     *
     * @throws GoogleAIStudioValidationException
     */
    public function validate(array $ocrData, DocumentType $documentType): array
    {
        Log::channel('daily')->info('[Google AI Studio] Starting validation', [
            'document_type' => $documentType->slug,
        ]);

        $prompt = $this->buildPrompt($ocrData, $documentType->validation_rules);

        $parts = [
            [
                'text' => $prompt,
            ],
        ];

        if (isset($ocrData['base64_pdf'])) {
            $base64Pdf = $ocrData['base64_pdf'];
            $mimeType = $ocrData['mime_type'] ?? 'application/pdf';
            
            // Remove them from the OCR data so they don't pollute the prompt text
            unset($ocrData['base64_pdf']);
            unset($ocrData['mime_type']);
            
            // Re-build prompt without the huge base64 string
            $prompt = $this->buildPrompt($ocrData, $documentType->validation_rules);
            $parts[0]['text'] = $prompt;

            // Gemini expects inline_data first
            array_unshift($parts, [
                'inline_data' => [
                    'mime_type' => $mimeType,
                    'data'      => $base64Pdf,
                ]
            ]);
        }

        $response = Http::withHeaders([
            'x-goog-api-key'    => $this->apiKey,
            'content-type'      => 'application/json',
        ])->post(self::API_URL, [
            'contents'   => [
                [
                    'parts' => $parts,
                ],
            ],
        ]);

        if (!$response->successful()) {
            Log::channel('daily')->error('[AI Studio] API call failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new GoogleAIStudioValidationException(
                "AI Studio API call failed: HTTP {$response->status()} — {$response->body()}",
                $response->status()
            );
        }

        $responseBody = $response->json();
        $rawText      = $responseBody['candidates'][0]['content']['parts'][0]['text'] ?? '';

        Log::channel('daily')->info('[AI Studio] Response received', [
            'tokens_used' => $responseBody['usageMetadata']['totalTokenCount'] ?? null,
            'raw text' => $response,
            'response body' => $responseBody,
        ]);

        return $this->parseAndValidateResponse($rawText, $responseBody);
    }

    /**
     * Build the system prompt that instructs Claude to analyze the OCR data
     * according to the document-type rules.
     *
     * @param  array       $ocrData          OCR structured data
     * @param  array|null  $validationRules   Rules from DocumentType::validation_rules
     * @return string
     */
    private function buildPrompt(array $ocrData, ?array $validationRules): string
    {
        $rulesJson = $validationRules ? json_encode($validationRules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '{}';
        $ocrJson   = json_encode($ocrData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Eres un experto en revisión de documentos académicos colombianos. Tu tarea es analizar el texto
extraído de un documento mediante OCR y validarlo según las reglas específicas proporcionadas.

## Reglas de validación del tipo de documento:
{$rulesJson}

## Datos OCR del documento a analizar:
{$ocrJson}

## Instrucciones:
1. Analiza el contenido OCR según las reglas de validación.
2. Verifica cada campo requerido indicado en "required_fields".
3. Detecta inconsistencias de fechas, firmas ausentes, celdas vacías y otros problemas.
4. Extrae el nombre y correo electrónico del docente si aparecen en el documento.
5. Clasifica los problemas encontrados por severidad: "critico", "advertencia" o "informativo".
6. Responde ÚNICAMENTE con el siguiente JSON estructurado, sin texto adicional, sin backticks de markdown:

{
  "document_type": "planeador" | "registro_notas" | "desconocido",
  "is_valid": true | false,
  "confidence_score": 0.0,
  "teacher": {
    "name": "Nombre completo del docente o null",
    "email": "correo@ejemplo.edu o null"
  },
  "missing_fields": ["campo1", "campo2"],
  "checks_performed": ["descripción del chequeo 1", "descripción del chequeo 2"],
  "checks_passed": ["chequeo superado 1"],
  "checks_failed": ["chequeo fallido 1"],
  "issues": [
    {
      "field": "nombre_del_campo",
      "severity": "critico" | "advertencia" | "informativo",
      "description": "Descripción del problema",
      "recommendation": "Cómo corregirlo"
    }
  ],
  "inconsistencies": ["descripción de inconsistencia 1"],
  "summary": "Resumen general del análisis en 2-3 oraciones.",
  "recommendations": "Párrafo con las recomendaciones principales para el docente."
}

IMPORTANTE: "is_valid" debe ser false si hay al menos un issue con severidad "critico".
PROMPT;
    }

    /**
     * Parse the AI text response and validate that it contains all required fields.
     *
     * @param  string  $rawText       The text content from the Anthropic response
     * @param  array   $fullResponse  The full Anthropic API response (for token tracking)
     * @return array                  Parsed and enriched validation data
     *
     * @throws GoogleAIStudioValidationException
     */
    private function parseAndValidateResponse(string $rawText, array $fullResponse): array
    {
        // Strip markdown code fences if present
        $cleanText = preg_replace('/^```(?:json)?\s*/i', '', trim($rawText));
        $cleanText = preg_replace('/\s*```$/', '', $cleanText);
        $cleanText = trim($cleanText);

        $parsed = json_decode($cleanText, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::channel('daily')->error('[AI Studio] Failed to parse JSON response', [
                'raw_text' => $rawText,
                'error'    => json_last_error_msg(),
            ]);
            throw new GoogleAIStudioValidationException(
                'AI Studio returned an invalid JSON response: ' . json_last_error_msg()
            );
        }

        // Validate required fields
        foreach (self::REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $parsed)) {
                throw new GoogleAIStudioValidationException(
                    "Anthropic response is missing required field: '{$field}'"
                );
            }
        }

        // Enrich with metadata from the API response
        $parsed['_tokens_input']  = $fullResponse['usageMetadata']['promptTokenCount'] ?? null;
        $parsed['_tokens_output'] = $fullResponse['usageMetadata']['candidatesTokenCount'] ?? null;
        $parsed['_model']         = $fullResponse['modelVersion'] ?? 'gemini-3.5-flash';

        return $parsed;
    }
}
