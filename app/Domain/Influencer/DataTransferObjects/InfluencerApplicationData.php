<?php

namespace App\Domain\Influencer\DataTransferObjects;

final class InfluencerApplicationData
{
    /**
     * @param  array<string, string>  $socialMediaLinks
     */
    public function __construct(
        public readonly string $fullName,
        public readonly string $email,
        public readonly array $socialMediaLinks,
        public readonly ?string $audienceDescription,
        public readonly int $monthlyReach,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            fullName: (string) $data['full_name'],
            email: (string) $data['email'],
            socialMediaLinks: (array) ($data['social_media_links'] ?? []),
            audienceDescription: isset($data['audience_description']) ? (string) $data['audience_description'] : null,
            monthlyReach: (int) ($data['monthly_reach'] ?? 0),
        );
    }
}
