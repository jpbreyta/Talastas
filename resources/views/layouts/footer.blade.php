<footer class="bg-red-900 border-t border-red-800 flex-shrink-0">
    <div class="w-full px-4 py-1 sm:px-6 lg:px-8">
        <div class="flex w-full flex-col gap-1 md:flex-row md:justify-between md:items-center">
            <div class="text-xs leading-relaxed text-red-200">
                © {{ date('Y') }} Lipa City Local Government Unit. All rights reserved.
            </div>
            <div class="text-red-100 md:ml-auto [&_nav]:text-xs [&_a]:min-h-8">
                @include('partials.legal-links')
            </div>
        </div>
    </div>
</footer>
