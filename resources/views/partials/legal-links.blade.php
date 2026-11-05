@if ($menu ?? false)
    <a href="{{ route('legal.terms') }}" class="profile-menu-item"><i class="fa-regular fa-file-lines" aria-hidden="true"></i><span>Terms and Conditions</span></a>
    <a href="{{ route('legal.privacy') }}" class="profile-menu-item"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><span>Privacy Notice</span></a>
@else
    <nav aria-label="Terms and privacy" class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
        <a href="{{ route('legal.terms') }}" class="inline-flex min-h-11 items-center underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2">Terms and Conditions</a>
        <a href="{{ route('legal.privacy') }}" class="inline-flex min-h-11 items-center underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2">Privacy Notice</a>
    </nav>
@endif
