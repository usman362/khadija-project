<?php

namespace App\Domain\Messaging;

/**
 * Sir Peter, 2026-09-21: "if the user considers the text messages as urgent
 * then there needs to be" a way to say so. Five levels, in his order, set on a
 * message by either person in the conversation.
 *
 * Routine is the absence of a level rather than a badge of its own on every
 * row: a list where every conversation is labelled says nothing. It is stored
 * when chosen, so a client can take a level back off a message.
 */
final class MessagePriority
{
    public const ROUTINE   = 'routine';
    public const IMPORTANT = 'important';
    public const PRIORITY  = 'priority';
    public const URGENT    = 'urgent';
    public const CRITICAL  = 'critical';

    public const LEVELS = [
        self::ROUTINE   => 'Routine',
        self::IMPORTANT => 'Important',
        self::PRIORITY  => 'Priority',
        self::URGENT    => 'Urgent',
        self::CRITICAL  => 'Critical',
    ];

    /** The colour each level is drawn in, the same in every list and window. */
    public const COLOURS = [
        self::ROUTINE   => '#16a34a',
        self::IMPORTANT => '#2563eb',
        self::PRIORITY  => '#d97706',
        self::URGENT    => '#ea580c',
        self::CRITICAL  => '#dc2626',
    ];

    public static function label(?string $level): ?string
    {
        return $level ? (self::LEVELS[$level] ?? null) : null;
    }

    public static function isValid(?string $level): bool
    {
        return $level !== null && array_key_exists($level, self::LEVELS);
    }
}
