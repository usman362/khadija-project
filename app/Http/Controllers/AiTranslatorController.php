<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Translator (both). Translate messages, proposals and documents between
 * languages with tone kept intact. Representative data.
 */
class AiTranslatorController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->hasRole('supplier') ? 'layouts.professional' : 'layouts.client';

        return view('ai-tools.translator', [
            'aiLayout' => $aiLayout,
            'stats' => [
                ['Languages', '50+', ''], ['Language Detect', 'Auto', 'good'],
                ['Tone & Meaning', 'Kept', 'good'], ['AI Confidence', '99%', 'good'],
            ],
            'detected' => 'Spanish',
            'original' => "Hola, estamos planeando nuestra boda para el 18 de agosto en Miami. Buscamos un fotógrafo con experiencia en bodas en la playa. ¿Tienen disponibilidad y cuál sería el precio del paquete completo?",
            'languages' => ['English', 'French', 'Arabic', 'More'],
            'translation' => "Hello, we are planning our wedding for August 18 in Miami. We're looking for a photographer with experience in beach weddings. Do you have availability, and what would the price be for the full package?",
        ]);
    }
}
