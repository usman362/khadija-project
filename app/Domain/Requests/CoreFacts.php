<?php

namespace App\Domain\Requests;

/**
 * The facts every request must carry, whichever way it was sent.
 *
 * Sir Peter, 2026-09-09: "if all of these workflows ultimately lead to an
 * agreement, I would make the core agreement-driving information consistent
 * across all of them."
 *
 * They did not. A bidding request could not be published without a name, a
 * description and a date; an emergency request asked for none of the three and
 * invented its own title from the services picked; a direct request asked for
 * a name but took no date and had nowhere to write what was actually wanted.
 * All three end in the same agreement, so all three now ask the same questions.
 *
 * The rules live here rather than in the three controllers, because three
 * copies is how they came apart in the first place.
 */
final class CoreFacts
{
    /** The shortest description that tells a professional anything. */
    public const MIN_DESCRIPTION = 20;

    /** @return array<int, string> */
    public static function nameRule(): array
    {
        return ['required', 'string', 'max:200'];
    }

    /** @return array<int, string> */
    public static function descriptionRule(): array
    {
        return ['required', 'string', 'min:' . self::MIN_DESCRIPTION, 'max:4000'];
    }

    /** @return array<int, string> */
    public static function dateRule(): array
    {
        return ['required', 'date'];
    }

    /**
     * All three at once, for a form that asks them on one page.
     *
     * The bidding wizard spreads them over three steps, so it takes the rules
     * one at a time instead -- same rules either way.
     *
     * @param  string  $name  the form's field name for the event's name
     * @param  string  $desc  the form's field name for the description
     * @param  string  $date  the form's field name for the date it happens
     * @return array<string, array<int, string>>
     */
    public static function rules(string $name, string $desc, string $date): array
    {
        return [
            $name => self::nameRule(),
            $desc => self::descriptionRule(),
            $date => self::dateRule(),
        ];
    }

    /**
     * One wording for each, so the same mistake is not explained three ways.
     *
     * @return array<string, string>
     */
    public static function messages(string $name, string $desc, string $date): array
    {
        return [
            $name . '.required' => 'Give your event a name.',
            $desc . '.required' => 'Describe what you need — this is what the professional reads before answering.',
            $desc . '.min'      => 'A little more detail helps professionals answer accurately.',
            $date . '.required' => 'Set the date your event runs.',
        ];
    }
}
