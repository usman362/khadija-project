<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * AI Translator (both). Without an external API this cannot translate arbitrary
 * free text, so it ships a curated, honest EVENT PHRASEBOOK covering common
 * booking phrases in Spanish, French, German, Italian and Portuguese.
 * Deterministic lookup with fuzzy matching; no external API.
 */
class AiTranslatorController extends Controller
{
    private const LANGUAGES = ['spanish', 'french', 'german', 'italian', 'portuguese'];

    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->hasRole('supplier') ? 'layouts.professional' : 'layouts.client';

        return view('ai-tools.translator', [
            'aiLayout'  => $aiLayout,
            'stats' => [
                ['Phrasebook', (string) count(self::phrasebook()), 'good'], ['Languages', '5', ''],
                ['Match', 'Fuzzy', 'good'], ['Built-in', 'No API', 'good'],
            ],
        ]);
    }

    public function compute(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'text'            => ['required', 'string', 'max:500'],
                'target_language' => ['required', 'in:' . implode(',', self::LANGUAGES)],
            ]);

            $result = $this->translate($data['text'], $data['target_language']);

            return response()->json(['success' => true, 'result' => $result]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Please check the form and try again.',
            ], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function translate(string $text, string $lang): array
    {
        $book = self::phrasebook();
        $needle = $this->normalize($text);

        $langLabel = ucfirst($lang);
        $matched = false;
        $translation = null;

        // Exact normalized match first.
        foreach ($book as $entry) {
            if ($this->normalize($entry['en']) === $needle) {
                $matched = true;
                $translation = $entry[$lang];
                break;
            }
        }

        // Fuzzy: high similarity to a known phrase.
        if (!$matched) {
            $best = null;
            $bestScore = 0.0;
            foreach ($book as $entry) {
                similar_text($needle, $this->normalize($entry['en']), $pct);
                if ($pct > $bestScore) {
                    $bestScore = $pct;
                    $best = $entry;
                }
            }
            if ($best && $bestScore >= 82.0) {
                $matched = true;
                $translation = $best[$lang];
            }
        }

        if (!$matched) {
            $translation = "This phrase isn't in the built-in event phrasebook. Live translation of any text activates once an AI key is configured.";
        }

        // Always surface up to 5 useful phrasebook suggestions.
        $suggestions = $this->suggest($book, $needle, $lang, 5);

        $summary = $matched
            ? "Found a match in the built-in event phrasebook and translated it to {$langLabel}."
            : "That exact phrase isn't in the built-in phrasebook yet. Here are the closest common event phrases available in {$langLabel}.";

        return [
            'source'                => $text,
            'target_language'       => $langLabel,
            'translation'           => $translation,
            'matched'               => $matched,
            'phrasebook_suggestions'=> $suggestions,
            'summary'               => $summary,
        ];
    }

    /**
     * @param array<int,array<string,string>> $book
     * @return array<int,array{en:string,translated:string}>
     */
    private function suggest(array $book, string $needle, string $lang, int $limit): array
    {
        $scored = [];
        foreach ($book as $entry) {
            similar_text($needle, $this->normalize($entry['en']), $pct);
            $scored[] = ['pct' => $pct, 'en' => $entry['en'], 'translated' => $entry[$lang]];
        }
        usort($scored, fn ($a, $b) => $b['pct'] <=> $a['pct']);

        return array_map(
            fn ($s) => ['en' => $s['en'], 'translated' => $s['translated']],
            array_slice($scored, 0, $limit)
        );
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = preg_replace('/[¿¡?!.,;:"\'()]+/u', '', $s) ?? $s;
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;
        return trim($s);
    }

    /**
     * Curated event phrasebook. English → 5 languages.
     * @return array<int,array{en:string,spanish:string,french:string,german:string,italian:string,portuguese:string}>
     */
    private static function phrasebook(): array
    {
        return [
            ['en' => 'Hello', 'spanish' => 'Hola', 'french' => 'Bonjour', 'german' => 'Hallo', 'italian' => 'Ciao', 'portuguese' => 'Olá'],
            ['en' => 'Thank you', 'spanish' => 'Gracias', 'french' => 'Merci', 'german' => 'Danke', 'italian' => 'Grazie', 'portuguese' => 'Obrigado'],
            ['en' => 'Thank you for booking', 'spanish' => 'Gracias por reservar', 'french' => 'Merci pour votre réservation', 'german' => 'Danke für Ihre Buchung', 'italian' => 'Grazie per la prenotazione', 'portuguese' => 'Obrigado pela reserva'],
            ['en' => 'You are welcome', 'spanish' => 'De nada', 'french' => 'De rien', 'german' => 'Gern geschehen', 'italian' => 'Prego', 'portuguese' => 'De nada'],
            ['en' => 'Please', 'spanish' => 'Por favor', 'french' => "S'il vous plaît", 'german' => 'Bitte', 'italian' => 'Per favore', 'portuguese' => 'Por favor'],
            ['en' => 'Yes', 'spanish' => 'Sí', 'french' => 'Oui', 'german' => 'Ja', 'italian' => 'Sì', 'portuguese' => 'Sim'],
            ['en' => 'No', 'spanish' => 'No', 'french' => 'Non', 'german' => 'Nein', 'italian' => 'No', 'portuguese' => 'Não'],
            ['en' => 'What time does the event start?', 'spanish' => '¿A qué hora empieza el evento?', 'french' => "À quelle heure commence l'événement ?", 'german' => 'Wann beginnt die Veranstaltung?', 'italian' => "A che ora inizia l'evento?", 'portuguese' => 'A que horas começa o evento?'],
            ['en' => 'What time does the event end?', 'spanish' => '¿A qué hora termina el evento?', 'french' => "À quelle heure se termine l'événement ?", 'german' => 'Wann endet die Veranstaltung?', 'italian' => "A che ora finisce l'evento?", 'portuguese' => 'A que horas termina o evento?'],
            ['en' => 'Please arrive 30 minutes early', 'spanish' => 'Por favor, llegue 30 minutos antes', 'french' => "Merci d'arriver 30 minutes en avance", 'german' => 'Bitte kommen Sie 30 Minuten früher', 'italian' => 'Si prega di arrivare 30 minuti prima', 'portuguese' => 'Por favor, chegue 30 minutos mais cedo'],
            ['en' => 'Can you send a quote?', 'spanish' => '¿Puede enviar un presupuesto?', 'french' => 'Pouvez-vous envoyer un devis ?', 'german' => 'Können Sie ein Angebot senden?', 'italian' => 'Può inviare un preventivo?', 'portuguese' => 'Pode enviar um orçamento?'],
            ['en' => 'What is the price?', 'spanish' => '¿Cuál es el precio?', 'french' => 'Quel est le prix ?', 'german' => 'Was kostet es?', 'italian' => 'Qual è il prezzo?', 'portuguese' => 'Qual é o preço?'],
            ['en' => 'Are you available on this date?', 'spanish' => '¿Está disponible en esta fecha?', 'french' => 'Êtes-vous disponible à cette date ?', 'german' => 'Sind Sie an diesem Datum verfügbar?', 'italian' => 'È disponibile in questa data?', 'portuguese' => 'Está disponível nesta data?'],
            ['en' => 'The deposit is required to confirm', 'spanish' => 'Se requiere un depósito para confirmar', 'french' => 'Un acompte est requis pour confirmer', 'german' => 'Eine Anzahlung ist zur Bestätigung erforderlich', 'italian' => 'È richiesto un acconto per confermare', 'portuguese' => 'É necessário um sinal para confirmar'],
            ['en' => 'Congratulations on your wedding', 'spanish' => 'Felicidades por su boda', 'french' => 'Félicitations pour votre mariage', 'german' => 'Herzlichen Glückwunsch zu Ihrer Hochzeit', 'italian' => 'Congratulazioni per il vostro matrimonio', 'portuguese' => 'Parabéns pelo seu casamento'],
            ['en' => 'Happy birthday', 'spanish' => 'Feliz cumpleaños', 'french' => 'Joyeux anniversaire', 'german' => 'Alles Gute zum Geburtstag', 'italian' => 'Buon compleanno', 'portuguese' => 'Feliz aniversário'],
            ['en' => 'How many guests will attend?', 'spanish' => '¿Cuántos invitados asistirán?', 'french' => "Combien d'invités seront présents ?", 'german' => 'Wie viele Gäste werden teilnehmen?', 'italian' => 'Quanti ospiti parteciperanno?', 'portuguese' => 'Quantos convidados vão participar?'],
            ['en' => 'Where is the venue?', 'spanish' => '¿Dónde es el lugar del evento?', 'french' => 'Où se trouve le lieu ?', 'german' => 'Wo ist der Veranstaltungsort?', 'italian' => "Dov'è la sede?", 'portuguese' => 'Onde é o local?'],
            ['en' => 'We look forward to working with you', 'spanish' => 'Esperamos trabajar con usted', 'french' => 'Nous avons hâte de travailler avec vous', 'german' => 'Wir freuen uns auf die Zusammenarbeit', 'italian' => 'Non vediamo l\'ora di lavorare con voi', 'portuguese' => 'Estamos ansiosos para trabalhar com você'],
            ['en' => 'Please confirm your booking', 'spanish' => 'Por favor, confirme su reserva', 'french' => 'Veuillez confirmer votre réservation', 'german' => 'Bitte bestätigen Sie Ihre Buchung', 'italian' => 'Si prega di confermare la prenotazione', 'portuguese' => 'Por favor, confirme a sua reserva'],
            ['en' => 'Your booking is confirmed', 'spanish' => 'Su reserva está confirmada', 'french' => 'Votre réservation est confirmée', 'german' => 'Ihre Buchung ist bestätigt', 'italian' => 'La sua prenotazione è confermata', 'portuguese' => 'A sua reserva está confirmada'],
            ['en' => 'See you soon', 'spanish' => 'Hasta pronto', 'french' => 'À bientôt', 'german' => 'Bis bald', 'italian' => 'A presto', 'portuguese' => 'Até breve'],
            ['en' => 'Good morning', 'spanish' => 'Buenos días', 'french' => 'Bonjour', 'german' => 'Guten Morgen', 'italian' => 'Buongiorno', 'portuguese' => 'Bom dia'],
            ['en' => 'Good evening', 'spanish' => 'Buenas noches', 'french' => 'Bonsoir', 'german' => 'Guten Abend', 'italian' => 'Buonasera', 'portuguese' => 'Boa noite'],
            ['en' => 'Can we reschedule?', 'spanish' => '¿Podemos cambiar la fecha?', 'french' => 'Pouvons-nous reporter ?', 'german' => 'Können wir verschieben?', 'italian' => 'Possiamo riprogrammare?', 'portuguese' => 'Podemos remarcar?'],
            ['en' => 'Do you have any questions?', 'spanish' => '¿Tiene alguna pregunta?', 'french' => 'Avez-vous des questions ?', 'german' => 'Haben Sie Fragen?', 'italian' => 'Ha domande?', 'portuguese' => 'Tem alguma pergunta?'],
            ['en' => 'The balance is due before the event', 'spanish' => 'El saldo se paga antes del evento', 'french' => "Le solde est dû avant l'événement", 'german' => 'Der Restbetrag ist vor der Veranstaltung fällig', 'italian' => "Il saldo è dovuto prima dell'evento", 'portuguese' => 'O saldo deve ser pago antes do evento'],
            ['en' => 'Thank you for your patience', 'spanish' => 'Gracias por su paciencia', 'french' => 'Merci de votre patience', 'german' => 'Danke für Ihre Geduld', 'italian' => 'Grazie per la pazienza', 'portuguese' => 'Obrigado pela paciência'],
            ['en' => 'We hope you enjoyed the event', 'spanish' => 'Esperamos que haya disfrutado el evento', 'french' => "Nous espérons que vous avez apprécié l'événement", 'german' => 'Wir hoffen, die Veranstaltung hat Ihnen gefallen', 'italian' => "Speriamo che l'evento vi sia piaciuto", 'portuguese' => 'Esperamos que tenha gostado do evento'],
        ];
    }
}
