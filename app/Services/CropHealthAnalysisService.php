<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\ValueObjects\Media\Image;

class CropHealthAnalysisService
{
    public function analyzeImage(string $imagePath, string $cropName): array
    {
        $fullPath = Storage::disk('public')->path($imagePath);

        \Log::info('Starting crop image analysis', [
            'image_path' => $imagePath,
            'full_path' => $fullPath,
            'crop_name' => $cropName,
            'file_exists' => file_exists($fullPath),
        ]);

        $systemPrompt = <<<'PROMPT'
You are an agricultural expert specializing in crop health diagnosis.
Analyze crop images to identify health issues, diseases, and provide actionable recommendations.

For each analysis, provide:
1. Overall health status (healthy, warning, or diseased)
2. List of identified diseases or issues (if any)
3. Confidence level (0-1)
4. Specific care recommendations

Return your response as a JSON object with this structure:
{
  "health_status": "healthy|warning|diseased",
  "diseases": ["disease1", "disease2"],
  "confidence": 0.95,
  "recommendations": ["recommendation1", "recommendation2"]
}
PROMPT;

        $userPrompt = "Analyze this {$cropName} crop image for health status, diseases, and provide care recommendations.";

        try {
            \Log::info('Calling Prism API', [
                'model' => 'gpt-4o-mini',
                'prompt_length' => strlen($userPrompt),
            ]);

            $response = Prism::text()
                ->using(Provider::OpenAI, 'gpt-4o-mini')
                ->withSystemPrompt($systemPrompt)
                ->withPrompt(
                    $userPrompt,
                    [Image::fromLocalPath($fullPath)]
                )
                ->withMaxTokens(1000)
                ->withClientOptions([
                    'timeout' => 60,
                    'connect_timeout' => 10,
                ])
                ->asText();

            \Log::info('Prism API call completed');

            // Extract JSON from markdown code blocks if present
            $text = $response->text;

            \Log::info('Raw AI response received', [
                'raw_response' => $text,
                'length' => strlen($text),
            ]);

            // Remove markdown code blocks (```json ... ``` or ``` ... ```)
            $text = preg_replace('/```(?:json)?\s*(.*?)\s*```/s', '$1', $text);

            // Trim whitespace
            $text = trim($text);

            \Log::info('Cleaned AI response', [
                'cleaned_text' => $text,
                'length' => strlen($text),
            ]);

            $analysis = json_decode($text, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                \Log::error('Failed to parse AI response', [
                    'raw_response' => $response->text,
                    'cleaned_text' => $text,
                    'json_error' => json_last_error_msg(),
                ]);
                throw new \Exception('Failed to parse AI response as JSON: '.json_last_error_msg());
            }

            \Log::info('Successfully parsed AI response', [
                'health_status' => $analysis['health_status'] ?? 'missing',
                'diseases_count' => count($analysis['diseases'] ?? []),
            ]);

            return [
                'health_status' => $analysis['health_status'] ?? 'unknown',
                'diseases' => $analysis['diseases'] ?? [],
                'confidence' => $analysis['confidence'] ?? 0.0,
                'recommendations' => $analysis['recommendations'] ?? [],
            ];
        } catch (\Exception $e) {
            \Log::error('Crop health analysis failed', [
                'image' => $imagePath,
                'crop' => $cropName,
                'error' => $e->getMessage(),
            ]);

            return [
                'health_status' => 'error',
                'diseases' => [],
                'confidence' => 0.0,
                'recommendations' => ['Analysis failed. Please try again or consult an agronomist.'],
            ];
        }
    }
}
