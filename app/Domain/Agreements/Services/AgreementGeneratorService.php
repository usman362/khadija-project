<?php

namespace App\Domain\Agreements\Services;

use App\Models\Agreement;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AgreementGeneratorService
{
    /**
     * Generate an AI agreement based on chat conversation and booking details.
     */
    public function generate(Booking $booking, User $requestedBy): Agreement
    {
        $booking->load(['event', 'client', 'supplier', 'conversation.messages.sender']);

        $conversation = $booking->conversation;
        $chatHistory = $this->extractChatHistory($conversation);
        $bookingContext = $this->buildBookingContext($booking);

        // Determine version
        $latestVersion = Agreement::forBooking($booking->id)->max('version') ?? 0;

        // Generate via AI or fallback to template
        $aiResult = $this->callAI($bookingContext, $chatHistory);

        $agreement = Agreement::create([
            'booking_id' => $booking->id,
            'conversation_id' => $conversation?->id,
            'generated_by' => $requestedBy->id,
            'title' => 'Service Agreement — ' . $booking->event->title,
            'content' => $aiResult['content'],
            'extracted_terms' => $aiResult['terms'],
            'status' => 'pending_review',
            'source' => 'ai',
            'version' => $latestVersion + 1,
            'ai_model_used' => $aiResult['model'],
            'ai_prompt_summary' => $aiResult['prompt_summary'],
        ]);

        return $agreement;
    }

    /**
     * Extract chat messages into a readable format for the AI.
     */
    private function extractChatHistory(?Conversation $conversation): string
    {
        if (!$conversation) {
            return 'No conversation history available.';
        }

        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->orderBy('created_at')
            ->limit(100)
            ->get();

        if ($messages->isEmpty()) {
            return 'No messages exchanged yet.';
        }

        $history = '';
        foreach ($messages as $msg) {
            $name = $msg->sender?->name ?? 'System';
            $time = $msg->created_at->format('M d, Y h:i A');
            $history .= "[{$time}] {$name}: {$msg->body}\n";
        }

        return $history;
    }

    /**
     * Build contextual information about the booking.
     */
    private function buildBookingContext(Booking $booking): array
    {
        return [
            'event_title' => $booking->event->title,
            'event_description' => $booking->event->description ?? 'Not specified',
            'event_start' => $booking->event->starts_at?->format('F d, Y h:i A') ?? 'TBD',
            'event_end' => $booking->event->ends_at?->format('F d, Y h:i A') ?? 'TBD',
            'client_name' => $booking->client?->name ?? 'Unknown',
            'client_email' => $booking->client?->email ?? 'Unknown',
            'supplier_name' => $booking->supplier?->name ?? 'Unknown',
            'supplier_email' => $booking->supplier?->email ?? 'Unknown',
            'booking_status' => $booking->status,
            'booking_notes' => $booking->notes ?? 'None',
            'booked_at' => $booking->booked_at?->format('F d, Y') ?? 'N/A',
        ];
    }

    /**
     * Call external AI API (OpenAI) or fall back to template generation.
     */
    private function callAI(array $context, string $chatHistory): array
    {
        $apiKey = Setting::get('openai.api_key') ?: config('services.openai.key');

        $prompt = $this->buildPrompt($context, $chatHistory);

        // If API key is configured, use OpenAI
        if ($apiKey) {
            try {
                return $this->callOpenAI($apiKey, $prompt, $context);
            } catch (\Exception $e) {
                Log::warning('AI agreement generation failed, falling back to template', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback: Smart template generation based on context + chat
        return $this->generateFromTemplate($context, $chatHistory, $prompt);
    }

    /**
     * Call OpenAI API.
     */
    private function callOpenAI(string $apiKey, string $prompt, array $context): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => Setting::get('openai.model') ?: config('services.openai.model', 'gpt-4o-mini'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional legal document assistant that generates service agreements for event bookings. Generate clear, professional agreements in HTML format. Extract specific terms mentioned in the conversation (dates, prices, deliverables, cancellation terms, etc.) into a structured JSON object alongside the agreement.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => (float) (Setting::get('openai.temperature') ?: 0.3),
            'max_tokens' => (int) (Setting::get('openai.max_tokens') ?: 4000),
            'response_format' => ['type' => 'json_object'],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('OpenAI API returned error: ' . $response->body());
        }

        $data = $response->json();
        $parsed = json_decode($data['choices'][0]['message']['content'] ?? '{}', true);

        return [
            'content' => $parsed['agreement_html'] ?? $this->generateFallbackContent($context),
            'terms' => $parsed['extracted_terms'] ?? $this->extractTermsFromChat($context),
            'model' => $data['model'] ?? 'gpt-4o-mini',
            'prompt_summary' => 'Generated from booking context + ' . strlen($prompt) . ' chars of chat history',
        ];
    }

    /**
     * Build the prompt for AI generation.
     */
    private function buildPrompt(array $context, string $chatHistory): string
    {
        return <<<PROMPT
Generate a professional service agreement based on the following booking details and conversation between the client and supplier.

## Booking Details:
- Event: {$context['event_title']}
- Description: {$context['event_description']}
- Event Start: {$context['event_start']}
- Event End: {$context['event_end']}
- Client: {$context['client_name']} ({$context['client_email']})
- Supplier/Vendor: {$context['supplier_name']} ({$context['supplier_email']})
- Booking Status: {$context['booking_status']}
- Booking Notes: {$context['booking_notes']}

## Conversation History:
{$chatHistory}

## Instructions:
1. Generate the agreement in HTML format with proper headings, sections, and styling.
2. Extract any specific terms discussed (prices, dates, deliverables, cancellation policy, etc.) from the conversation.
3. Include standard clauses: scope of services, payment terms, cancellation policy, liability, and signatures section.
4. If specific terms aren't discussed in the chat, use reasonable defaults marked with [TO BE CONFIRMED].

Return JSON with two fields:
- "agreement_html": The full HTML agreement
- "extracted_terms": An object with keys like "price", "deliverables", "cancellation_policy", "payment_schedule", "special_requests"
PROMPT;
    }

    /**
     * Fallback: Generate agreement from template when AI is unavailable.
     */
    private function generateFromTemplate(array $context, string $chatHistory, string $prompt): array
    {
        $content = $this->generateFallbackContent($context);
        $terms = $this->extractTermsFromChat($context);

        return [
            'content' => $content,
            'terms' => $terms,
            'model' => 'template-fallback',
            'prompt_summary' => 'Generated from template (AI API not configured). Set OPENAI_API_KEY in .env to enable AI generation.',
        ];
    }

    /**
     * Generate a professional agreement using templates.
     */
    private function generateFallbackContent(array $context): string
    {
        $date = now()->format('F d, Y');
        $agreementNumber = 'AGR-' . now()->format('Ymd') . '-' . rand(1000, 9999);

        return <<<HTML
<div class="agreement-document">
    <div style="text-align: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #333;">
        <h1 style="margin: 0; font-size: 1.5rem;">SERVICE AGREEMENT</h1>
        <p style="color: #666; margin: 4px 0;">Agreement #{$agreementNumber}</p>
        <p style="color: #666; margin: 4px 0;">Date: {$date}</p>
    </div>

    <h3>1. PARTIES</h3>
    <p>This Service Agreement ("Agreement") is entered into between:</p>
    <ul>
        <li><strong>Client:</strong> {$context['client_name']} ({$context['client_email']}), hereinafter referred to as "Client"</li>
        <li><strong>Service Provider:</strong> {$context['supplier_name']} ({$context['supplier_email']}), hereinafter referred to as "Vendor"</li>
    </ul>

    <h3>2. EVENT DETAILS</h3>
    <ul>
        <li><strong>Event Name:</strong> {$context['event_title']}</li>
        <li><strong>Description:</strong> {$context['event_description']}</li>
        <li><strong>Start Date/Time:</strong> {$context['event_start']}</li>
        <li><strong>End Date/Time:</strong> {$context['event_end']}</li>
    </ul>

    <h3>3. SCOPE OF SERVICES</h3>
    <p>The Vendor agrees to provide the following services for the above-mentioned event:</p>
    <ul>
        <li>Professional event services as discussed and agreed upon by both parties</li>
        <li>All necessary equipment and materials required for service delivery</li>
        <li>On-site coordination and execution during the event</li>
    </ul>
    <p><em>Additional notes: {$context['booking_notes']}</em></p>

    <h3>4. PAYMENT TERMS</h3>
    <ul>
        <li><strong>Service Fee:</strong> <span style="color: #c00;">[TO BE CONFIRMED by both parties]</span></li>
        <li><strong>Deposit:</strong> A deposit of 30% is required upon signing this agreement</li>
        <li><strong>Balance:</strong> Remaining balance due 48 hours before the event</li>
        <li><strong>Payment Method:</strong> As agreed through platform payment system</li>
    </ul>

    <h3>5. CANCELLATION POLICY</h3>
    <ul>
        <li>Cancellation 30+ days before event: Full refund minus 10% processing fee</li>
        <li>Cancellation 15-29 days before event: 50% refund</li>
        <li>Cancellation less than 15 days before event: No refund</li>
        <li>Vendor cancellation: Full refund and best-effort replacement</li>
    </ul>

    <h3>6. RESPONSIBILITIES</h3>
    <h4>Client Responsibilities:</h4>
    <ul>
        <li>Provide accurate event details and timely communication</li>
        <li>Ensure venue access for the Vendor at agreed times</li>
        <li>Make payments according to the agreed schedule</li>
    </ul>
    <h4>Vendor Responsibilities:</h4>
    <ul>
        <li>Deliver services as described in this agreement</li>
        <li>Maintain professional conduct throughout the event</li>
        <li>Carry appropriate insurance for service delivery</li>
    </ul>

    <h3>7. LIABILITY & INDEMNIFICATION</h3>
    <p>Each party agrees to indemnify the other against any claims arising from their respective negligence or breach of this agreement. Maximum liability is limited to the total service fee.</p>

    <h3>8. FORCE MAJEURE</h3>
    <p>Neither party shall be liable for failure to perform due to circumstances beyond their reasonable control, including natural disasters, government actions, or other force majeure events.</p>

    <h3>9. AGREEMENT ACCEPTANCE</h3>
    <p>By accepting this agreement digitally on the platform, both parties acknowledge that they have read, understood, and agree to all terms stated herein.</p>

    <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #ddd;">
        <div style="display: flex; justify-content: space-between; gap: 2rem;">
            <div style="flex: 1;">
                <p><strong>Client:</strong></p>
                <p>{$context['client_name']}</p>
                <p style="color: #999; font-size: 0.85em;">Digital signature pending...</p>
            </div>
            <div style="flex: 1;">
                <p><strong>Vendor:</strong></p>
                <p>{$context['supplier_name']}</p>
                <p style="color: #999; font-size: 0.85em;">Digital signature pending...</p>
            </div>
        </div>
    </div>
</div>
HTML;
    }

    /**
     * Extract basic terms from booking context.
     */
    private function extractTermsFromChat(array $context): array
    {
        return [
            'event' => $context['event_title'],
            'client' => $context['client_name'],
            'supplier' => $context['supplier_name'],
            'event_date' => $context['event_start'],
            'price' => 'To be confirmed',
            'deliverables' => 'As discussed in conversation',
            'cancellation_policy' => 'Standard 30/15 day policy',
            'payment_schedule' => '30% deposit, 70% balance before event',
            'special_requests' => $context['booking_notes'],
        ];
    }
}
