<?php

namespace App\Services;

use Illuminate\Http\Request;

class AuthenticationDestination
{
    /** Save only a path. Never copy query parameters or a submitted action's body. */
    public function remember(Request $request): void
    {
        if ($request->routeIs('security.*', 'legal.*', 'login', 'logout')) {
            return;
        }
        if ($request->isMethod('GET')) {
            $path = $this->safePath($request, $request->url());
        } else {
            // Only known form pages may be restored from an untrusted Referer header.
            $path = $this->safePath($request, $request->headers->get('referer'));
            $allowed = array_map(fn ($name) => route($name, [], false), [
                'profile.account', 'admin.users.index', 'admin.invitations.index',
            ]);
            if (! in_array($path, $allowed, true)) {
                $path = $request->routeIs('profile.*') ? route('profile.account', [], false) : route('admin.users.index', [], false);
            }
        }
        if ($path && ! str_starts_with($path, '/security/') && ! str_starts_with($path, '/legal/') && $path !== '/login') {
            $request->session()->put('url.intended', $path);
        }
    }

    public function pull(Request $request): string
    {
        return $this->safePath($request, $request->session()->pull('url.intended')) ?? '/';
    }

    public function safePath(Request $request, mixed $url): ?string
    {
        if (! is_string($url) || preg_match('/[\\\\\x00-\x20\x7f]/', $url)) {
            return null;
        }
        $parts = parse_url($url);
        if ($parts === false || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }
        if (isset($parts['host']) || isset($parts['scheme'])) {
            if (($parts['scheme'] ?? '') !== $request->getScheme() || ($parts['host'] ?? '') !== $request->getHost()
                || ($parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80)) !== $request->getPort()) {
                return null;
            }
        }
        $path = $parts['path'] ?? '';
        if (! str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '%')) {
            return null;
        }

        return $path;
    }
}
