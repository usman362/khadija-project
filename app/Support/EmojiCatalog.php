<?php

namespace App\Support;

/**
 * The emoji offered in the message composer.
 *
 * The picker used to hold ten characters, all of them event-planning shorthand
 * — a thumbs-up, a party popper, a bouquet. People write to each other in
 * these threads; ten is a toolbar, not a picker.
 *
 * Names are here because the search filters on them: "party" has to find 🎉,
 * and searching the character itself would find nothing. They are also the
 * button's title and accessible name, so a screen reader says "party popper"
 * rather than reading the glyph.
 *
 * Not the full Unicode set on purpose. This is a marketplace where clients and
 * professionals arrange paid work; the list is what people reach for in that
 * conversation, without a 1,800-emoji scroll to get through.
 */
final class EmojiCatalog
{
    /** @return array<string, array{label:string, icon:string, emoji:array<string,string>}> */
    public static function groups(): array
    {
        return [
            'smileys' => [
                'label' => 'Smileys & people',
                'icon'  => '😊',
                'emoji' => [
                    '😀' => 'grinning happy', '😃' => 'smile happy', '😄' => 'laugh happy',
                    '😁' => 'beam grin', '😆' => 'laughing', '😅' => 'sweat laugh relief',
                    '🤣' => 'rofl laughing', '😂' => 'tears of joy laughing', '🙂' => 'slight smile',
                    '😊' => 'blush smile happy', '😇' => 'innocent angel', '🥰' => 'love hearts',
                    '😍' => 'heart eyes love', '😘' => 'kiss', '😋' => 'yum tasty food',
                    '😎' => 'cool sunglasses', '🤩' => 'star struck excited', '🥳' => 'party celebrate',
                    '🙃' => 'upside down', '😉' => 'wink', '🤗' => 'hug',
                    '🤔' => 'thinking hmm', '🤨' => 'raised eyebrow doubt', '😐' => 'neutral',
                    '😑' => 'expressionless', '😶' => 'no mouth speechless', '🙄' => 'eye roll',
                    '😏' => 'smirk', '😣' => 'persevere', '😥' => 'sad relieved',
                    '😮' => 'surprised open mouth', '🤐' => 'zipper mouth quiet', '😯' => 'hushed',
                    '😪' => 'sleepy tired', '😴' => 'sleeping', '😌' => 'relieved calm',
                    '😔' => 'pensive sad', '😕' => 'confused', '🙁' => 'slight frown sad',
                    '😖' => 'confounded', '😞' => 'disappointed sad', '😟' => 'worried',
                    '😢' => 'crying sad', '😭' => 'sobbing crying', '😤' => 'triumph frustrated',
                    '😠' => 'angry', '😡' => 'rage very angry', '🤯' => 'mind blown',
                    '😳' => 'flushed embarrassed', '🥺' => 'pleading please', '😬' => 'grimace awkward',
                    '🤞' => 'fingers crossed hope', '🙏' => 'thanks please pray', '👋' => 'wave hello hi bye',
                    '🤝' => 'handshake deal agreed', '👏' => 'clap applause well done',
                    '💪' => 'strong muscle', '🫡' => 'salute on it',
                ],
            ],
            'gestures' => [
                'label' => 'Yes, no & reactions',
                'icon'  => '👍',
                'emoji' => [
                    '👍' => 'thumbs up yes good agree ok', '👎' => 'thumbs down no disagree',
                    '👌' => 'ok perfect', '✌️' => 'peace victory', '🤙' => 'call me',
                    '👉' => 'point right this', '👈' => 'point left', '👆' => 'point up above',
                    '👇' => 'point down below', '✋' => 'hand stop wait', '🤚' => 'raised back of hand',
                    '✅' => 'check done yes confirmed complete tick', '☑️' => 'checkbox done',
                    '❌' => 'cross no cancel wrong', '⭕' => 'circle correct',
                    '❓' => 'question ask', '❗' => 'exclamation important',
                    '⚠️' => 'warning caution careful', '🚫' => 'no forbidden not allowed',
                    '💯' => 'hundred perfect exactly', '🔥' => 'fire great hot amazing',
                    '⭐' => 'star favourite rating', '🌟' => 'glowing star special',
                    '❤️' => 'heart love red', '🧡' => 'orange heart', '💛' => 'yellow heart',
                    '💚' => 'green heart', '💙' => 'blue heart', '💜' => 'purple heart',
                    '🤍' => 'white heart', '💔' => 'broken heart', '👀' => 'eyes looking watching',
                    '🎯' => 'target bullseye on point', '🙌' => 'raised hands celebrate praise',
                ],
            ],
            'events' => [
                'label' => 'Events',
                'icon'  => '🎉',
                'emoji' => [
                    '🎉' => 'party popper celebrate congratulations', '🎊' => 'confetti celebrate party',
                    '🎈' => 'balloon party decor', '🎂' => 'birthday cake', '🍰' => 'cake slice dessert',
                    '🧁' => 'cupcake dessert', '🥂' => 'cheers toast champagne celebrate',
                    '🍾' => 'champagne bottle celebrate', '🍷' => 'wine bar drinks',
                    '🍸' => 'cocktail bar drinks', '🍺' => 'beer drinks', '☕' => 'coffee break',
                    '🍽️' => 'dining plate catering meal', '🍴' => 'cutlery catering food',
                    '🥗' => 'salad food catering', '🍕' => 'pizza food', '🌮' => 'taco food truck',
                    '💐' => 'bouquet flowers floral', '🌹' => 'rose flowers floral',
                    '🌸' => 'blossom flowers decor', '💒' => 'wedding venue chapel',
                    '💍' => 'ring engagement wedding', '👰' => 'bride wedding', '🤵' => 'groom formal',
                    '🎵' => 'music note dj', '🎶' => 'music notes dj band', '🎤' => 'microphone mic singer speaker',
                    '🎧' => 'headphones dj audio', '🎸' => 'guitar band live music',
                    '🎹' => 'piano keyboard music', '🥁' => 'drums band', '🎺' => 'trumpet brass band',
                    '📸' => 'camera photo photographer', '📷' => 'camera photography',
                    '🎥' => 'video camera videographer film', '🎬' => 'clapper film production',
                    '🎨' => 'art decor design', '🎭' => 'theatre performance entertainment',
                    '✨' => 'sparkles magic special', '🎁' => 'gift favour present',
                    '🏆' => 'trophy award prize', '🎪' => 'tent marquee circus',
                    '🕺' => 'dancing man dance floor', '💃' => 'dancing woman dance floor',
                    '🪩' => 'disco ball party dance', '🎆' => 'fireworks celebrate',
                ],
            ],
            'logistics' => [
                'label' => 'Time, money & places',
                'icon'  => '📅',
                'emoji' => [
                    '📅' => 'calendar date booking', '📆' => 'calendar schedule',
                    '🗓️' => 'calendar planner dates', '⏰' => 'alarm clock time reminder',
                    '⏱️' => 'stopwatch timing', '⌛' => 'hourglass waiting deadline',
                    '🕐' => 'clock time hour', '📍' => 'pin location venue address',
                    '🗺️' => 'map directions', '🚗' => 'car travel transport',
                    '🚐' => 'van transport load in', '✈️' => 'plane travel flight',
                    '🏨' => 'hotel accommodation venue', '🏛️' => 'hall venue building',
                    '🏠' => 'house home venue', '🌇' => 'sunset evening event',
                    '☀️' => 'sun weather outdoor', '🌧️' => 'rain weather wet',
                    '❄️' => 'snow cold winter', '💵' => 'money cash dollars payment budget',
                    '💰' => 'money bag budget payment', '💳' => 'card payment pay',
                    '🧾' => 'receipt invoice bill', '📊' => 'chart report numbers',
                    '📈' => 'chart up growth', '📝' => 'memo note write brief',
                    '📄' => 'document page contract', '📋' => 'clipboard checklist',
                    '📎' => 'paperclip attachment file', '📁' => 'folder files',
                    '✉️' => 'email message envelope', '📞' => 'phone call ring',
                    '📱' => 'mobile phone', '💬' => 'speech bubble message chat',
                    '🔗' => 'link url', '🔒' => 'lock secure private', '🔑' => 'key access',
                    '💡' => 'idea lightbulb suggestion', '🛠️' => 'tools setup equipment',
                    '🔌' => 'plug power electrical', '💡' => 'light lighting',
                ],
            ],
        ];
    }

    /** Every emoji in one flat list — used by the tests and any consumer that
     *  does not care about grouping. */
    public static function all(): array
    {
        return array_merge(...array_values(array_map(
            fn (array $g) => $g['emoji'],
            self::groups(),
        )));
    }
}
