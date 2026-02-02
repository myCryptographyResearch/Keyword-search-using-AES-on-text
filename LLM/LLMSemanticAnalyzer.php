<?php

/**
 * LLM-based Text Processing Agent for Semantic Analysis
 * 
 * This class provides an interface for interacting with Large Language Models
 * through the Liara AI API to perform semantic keyword extraction.
 * 
 * @version 1.6
 * @author [Sajjad Ranjbar]
 * @license MIT
 */
class LLMSemanticAnalyzer {
    
    /**
     * @var string API endpoint URL
     */
    private $apiEndpoint = "https://ai.liara.ir/api/692dd4b351830273cb888a1e/v1/chat/completions";
    
    /**
     * @var string API authentication key
     */
    private $apiKey = "";
    
    /**
     * @var string Selected language model identifier
     */
    private $languageModel = "";
    
    /**
     * @var string System prompt defining agent behavior
     */
    private $systemPrompt = "";
    
    /**
     * Configure the language model
     * 
     * @param string $modelIdentifier Model identifier (e.g., "google/gemini-2.0-flash-001")
     * @return void
     */
    public function configureModel(string $modelIdentifier): void {
        $this->languageModel = $modelIdentifier;
    }
    
    /**
     * Set the system prompt for semantic analysis
     * 
     * @param string $prompt System prompt defining agent behavior
     * @return void
     */
    public function setSystemPrompt(string $prompt): void {
        $this->systemPrompt = $prompt;
    }
    
    /**
     * Execute semantic analysis on input text
     * 
     * @param string $inputText Text to be analyzed
     * @return string|array Extracted keywords or error information
     */
    public function analyzeText(string $inputText) {
        $requestPayload = $this->constructRequestPayload($inputText);
        $apiResponse = $this->executeApiRequest($requestPayload);
        
        return $this->processApiResponse($apiResponse);
    }
    
    /**
     * Construct API request payload
     * 
     * @param string $userInput User-provided text for analysis
     * @return array Structured request payload
     */
    private function constructRequestPayload(string $userInput): array {
        return [
            "model" => $this->languageModel,
            "messages" => [
                [
                    "role" => "system",
                    "content" => $this->systemPrompt
                ],
                [
                    "role" => "user",
                    "content" => $userInput
                ]
            ]
        ];
    }
    
    /**
     * Execute HTTP request to LLM API
     * 
     * @param array $payload Request payload
     * @return array Response data with content, HTTP status, and potential errors
     */
    private function executeApiRequest(array $payload): array {
        $curlHandler = curl_init($this->apiEndpoint);
        
        curl_setopt_array($curlHandler, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $this->apiKey
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $rawResponse = curl_exec($curlHandler);
        $httpStatusCode = curl_getinfo($curlHandler, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curlHandler);
        
        curl_close($curlHandler);
        
        return [
            'raw_response' => $rawResponse,
            'http_status' => $httpStatusCode,
            'curl_error' => $curlError
        ];
    }
    
    /**
     * Process and validate API response
     * 
     * @param array $apiResponse Raw API response data
     * @return string|array Processed result or error information
     */
    private function processApiResponse(array $apiResponse) {
        // Handle CURL execution errors
        if ($apiResponse['raw_response'] === false) {
            return [
                'error' => 'Network communication failure',
                'details' => $apiResponse['curl_error']
            ];
        }
        
        // Decode JSON response
        $decodedResponse = json_decode($apiResponse['raw_response'], true);
        
        // Handle JSON parsing errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'error' => 'Malformed API response',
                'http_status' => $apiResponse['http_status'],
                'raw_content' => substr($apiResponse['raw_response'], 0, 200) // Truncated for brevity
            ];
        }
        
        // Extract and return successful response
        if (isset($decodedResponse['choices'][0]['message']['content'])) {
            return trim($decodedResponse['choices'][0]['message']['content']);
        }
        
        // Handle API-level errors
        if (isset($decodedResponse['error'])) {
            return [
                'error' => 'API service error',
                'details' => $decodedResponse['error']
            ];
        }
        
        // Handle unexpected response formats
        return [
            'error' => 'Unexpected response structure',
            'response_sample' => array_slice($decodedResponse, 0, 3) // Sample for debugging
        ];
    }
}

/**
 * Configuration constants for the semantic analysis task
 */
define('LANGUAGE_MODEL', '<Set_your_LLM_ex: GPT 5.2>');
define('SYSTEM_PROMPT', 'You are an expert indexer and semantic analyst. Your task is to process input sentences (often questions or statements) and output only the core one to three keywords that encapsulate the primary intent or emotional essence.
Core Instructions & Absolute Rules:
1. Language Fidelity: Output keywords must match the input language exactly
2. Conceptual Precision: Keywords must reflect main subject, action, need, or emotion
3. Maximum Brevity: Output 1-3 keywords only
4. Pure Format: Keywords only, comma-separated if multiple
5. Intelligent Extraction: Use or combine words from input, avoid filler words
6. Priority Handling: Select most specific, actionable, or emotionally charged concepts');

/**
 * Example implementation demonstrating keyword extraction from Persian text
 */
function demonstrateKeywordExtraction(): void {
    $analyzer = new LLMSemanticAnalyzer();
    $analyzer->configureModel(LANGUAGE_MODEL);
    $analyzer->setSystemPrompt(SYSTEM_PROMPT);
    
    $inputText = "بسیار غمگین هستم و نیاز به کمک تو دارم. آیا راه حلی برای بهتر شدن ساعت مطالعه من داری؟";
    $analysisResult = $analyzer->analyzeText($inputText);
    
    echo "Input Text: " . $inputText . "\n";
    echo "Extracted Keywords: " . (is_string($analysisResult) ? $analysisResult : print_r($analysisResult, true)) . "\n";
}

// Execute demonstration
demonstrateKeywordExtraction();

?>