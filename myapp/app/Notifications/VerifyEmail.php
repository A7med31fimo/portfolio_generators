<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Overrides Laravel's default VerifyEmail notification.
 *
 * The default notification builds a signed link to the API's own
 * 'verification.verify' route (a GET endpoint meant to be clicked from
 * an email, not called by JS). Since the actual user-facing page lives
 * on the Next.js frontend, this notification instead points to a
 * frontend route — e.g. https://nextdev.io/en/verify-email/{id}/{hash}?expires=...&signature=...
 * which the Next.js page then forwards to this API's GET
 * /api/v1/auth/email/verify/{id}/{hash} endpoint (with the same query string)
 * on the user's behalf, authenticated with their Sanctum token.
 *
 * Registered in AppServiceProvider::boot() via:
 *   VerifyEmail::toMailUsing(...)
 * or by binding this class — see config/api.php FRONTEND_URL usage below.
 *
 * Note on queueing: this notification sends synchronously by default
 * (simplest setup for local dev — no queue worker required). For
 * production, consider implementing ShouldQueue on this class so
 * registration doesn't wait on the mail server's response time:
 *
 *   class VerifyEmail extends BaseVerifyEmail implements ShouldQueue
 *   {
 *       use Queueable;
 *       ...
 *   }
 *
 * If you do this, run `php artisan queue:work` (or supervisor in prod)
 * so queued notifications actually get processed.
 */
class VerifyEmail extends BaseVerifyEmail
{
    /**
     * Build the mail message, but replace the API's internal signed URL
     * with one pointing at the Next.js frontend, preserving the
     * signature and expiry query params so the link is still valid
     * when the frontend relays it back to the API.
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        // Swap the API origin for the frontend origin, keep path + query intact.
        $frontendUrl = $this->toFrontendUrl($verificationUrl);

        return (new MailMessage)
            ->subject('Verify Your Email Address')
            ->line('Please click the button below to verify your email address.')
            ->action('Verify Email Address', $frontendUrl)
            ->line('This verification link will expire in 60 minutes.')
            ->line('If you did not create an account, no further action is required.');
    }

    /**
     * Rewrite an API-origin signed URL into a frontend-origin URL,
     * preserving path segments (id, hash) and the full query string
     * (expires, signature).
     */
    private function toFrontendUrl(string $apiSignedUrl): string
    {
        $parts = parse_url($apiSignedUrl);

        // Expecting path like: /api/v1/auth/email/verify/{id}/{hash}
        $path = $parts['path'] ?? '';
        $segments = explode('/', trim($path, '/'));
        $id   = $segments[count($segments) - 2] ?? '';
        $hash = $segments[count($segments) - 1] ?? '';

        $query = $parts['query'] ?? '';
        $frontendBase = rtrim(config('app.frontend_url'), '/');

        return "{$frontendBase}/en/verify-email/{$id}/{$hash}?{$query}";
    }
}
