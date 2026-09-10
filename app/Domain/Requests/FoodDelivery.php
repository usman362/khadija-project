<?php

namespace App\Domain\Requests;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

/**
 * Whether a request is about food, and how the client wants it to arrive.
 *
 * Sir Peter is weighing an affiliate deal with a courier. The question that
 * decides it is how many clients actually want somebody other than themselves
 * or the professional to carry the food — and nobody has ever been asked.
 *
 * Asked only where it means something. A photographer does not deliver food,
 * and a request form that asks everybody about catering is a form people learn
 * to skim.
 */
final class FoodDelivery
{
    public const CLIENT_COLLECTS       = 'client_collects';
    public const PROFESSIONAL_DELIVERS = 'professional_delivers';

    /*
     * Two, and only two.
     *
     * A third choice was here for a day -- "we would rather a delivery service
     * brought it" -- to find out whether anyone wanted a courier before
     * approaching one. Sir Peter, 2026-09-10, closed the question himself:
     * "i rather now just have the client pick it up themselves or set it up by
     * the professionals do it, so that we are no longer an option."
     *
     * His own research document reached the same place from the other side --
     * "Do not assume GigResource will earn a commission" -- which removed the
     * reason the idea started. So the option is gone rather than hidden: a
     * courier arriving at a professional's kitchen unannounced is a liability
     * nobody here is carrying, and there is no revenue on the other side of it.
     *
     * Sir Peter's own labels, from that document, so the wording on the screen
     * and the wording in his notes are the same wording.
     */
    public const CHOICES = [
        self::PROFESSIONAL_DELIVERS => 'Professional Delivery. They bring it to the event',
        self::CLIENT_COLLECTS       => 'Client Pickup. We collect it from them',
    ];

    /** The service categories whose services are food. */
    public const FOOD_CATEGORIES = ['Catering & Food Services', 'Bar, Beverage & Mixology Services'];

    /**
     * Do any of these services involve food arriving somewhere?
     *
     * Matched on the category a service sits under rather than on its name.
     * A keyword list would ask about delivery for "Food Truck Booking", which
     * drives itself, and miss anything named without the word food in it.
     *
     * @param  array<int>  $serviceIds
     */
    public static function appliesTo(array $serviceIds): bool
    {
        if ($serviceIds === []) {
            return false;
        }

        return Category::whereIn('id', $serviceIds)
            ->whereIn('parent_id', self::foodCategoryIds())
            ->exists();
    }

    /** @return array<int> */
    public static function foodCategoryIds(): array
    {
        return Cache::remember('food.category_ids', now()->addHours(6), fn () => Category::query()
            ->where('kind', Category::SERVICE_CATEGORY)
            ->whereIn('name', self::FOOD_CATEGORIES)
            ->pluck('id')
            ->all());
    }

    /**
     * The validation rules for the field, on a form where the services arrive
     * in the same request.
     *
     * @param  array<int>  $serviceIds
     * @return array<string, array<int, string>>
     */
    public static function rules(array $serviceIds): array
    {
        return [
            'delivery_mode' => [
                self::appliesTo($serviceIds) ? 'required' : 'nullable',
                'in:' . implode(',', array_keys(self::CHOICES)),
            ],
        ];
    }

    /**
     * What to store: the answer if the question applied, null if it did not.
     *
     * A client can tick catering, answer, then untick it. Without this the
     * request keeps a delivery preference for services that do not involve
     * food — and the count Sir Peter is waiting on is made of those answers.
     *
     * @param  array<int>  $serviceIds
     */
    public static function answerFor(array $serviceIds, ?string $submitted): ?string
    {
        if (! self::appliesTo($serviceIds)) {
            return null;
        }

        return isset(self::CHOICES[$submitted]) ? $submitted : null;
    }

    public static function label(?string $mode): ?string
    {
        return $mode ? (self::CHOICES[$mode] ?? null) : null;
    }
}
