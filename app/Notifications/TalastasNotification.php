<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TalastasNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $category,
        public readonly string $title,
        public readonly string $message,
        public readonly ?string $actionUrl = null,
        public readonly array $metadata = [],
    ) {
    }

    public function via(object $notifiable): array
    {
        // In-app/database notifications only. This avoids sending LGU data to
        // another external provider unless email/SMS is explicitly approved.
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => Str::limit(strip_tags($this->category), 80, ''),
            'title' => Str::limit(strip_tags($this->title), 160, ''),
            'message' => Str::limit(strip_tags($this->message), 1000, ''),
            'action_url' => $this->safeLocalPath($this->actionUrl),
            'metadata' => $this->safeMetadata($this->metadata),
        ];
    }

    private function safeLocalPath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        // Only same-application relative paths are allowed in notification
        // actions. This prevents open redirects and javascript/data URLs.
        if (! Str::startsWith($path, '/') || Str::startsWith($path, '//')) {
            return null;
        }

        return Str::limit($path, 500, '');
    }

    private function safeMetadata(array $metadata): array
    {
        // Keep notification payloads deliberately small and non-sensitive.
        // Do not store passwords, API keys, complete audit payloads, device
        // specifications, personal contact information, or access tokens here.
        return collect($metadata)
            ->only([
                'institution_id',
                'audit_id',
                'status',
                'analysis_id',
            ])
            ->mapWithKeys(function (mixed $value, string $key): array {
                if (is_bool($value) || is_int($value) || is_float($value)) {
                    return [$key => $value];
                }

                return [$key => Str::limit(strip_tags((string) $value), 120, '')];
            })
            ->all();
    }
}
