<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>TALASTAS | Sign In</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo_ict_mapping.png') }}">

    @include('partials.fonts')

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /*
        |--------------------------------------------------------------------------
        | TALASTAS Brand Colors
        |--------------------------------------------------------------------------
        |
        | Primary Maroon : #7A1F2B
        | Dark Maroon    : #58151E
        | Deep Burgundy  : #3F0F16
        | Gold Accent    : #D6A84B
        | Soft Maroon    : #F7ECEE
        |
        */

        .talastas-pattern {
            background-image:
                radial-gradient(
                    circle at 20% 20%,
                    rgba(255, 255, 255, 0.05) 0,
                    rgba(255, 255, 255, 0.05) 1px,
                    transparent 1px
                );
            background-size: 28px 28px;
            animation: patternShift 60s ease-in-out infinite;
        }

        @keyframes patternShift {
            0%, 100% {
                background-position: 0 0;
            }
            50% {
                background-position: 28px 28px;
            }
        }

        .brand-gradient {
            background:
                linear-gradient(
                    145deg,
                    #3F0F16 0%,
                    #58151E 40%,
                    #7A1F2B 100%
                );
        }

        /* TALASTAS Text Gradient & Glow */
        .talastas-highlight {
            background: linear-gradient(
                135deg,
                #FFFFFF 0%,
                #FFD700 50%,
                #FFFFFF 100%
            );
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 30px rgba(214, 168, 75, 0.5);
            animation: shimmer 3s ease-in-out infinite;
            position: relative;
        }

        .talastas-glow {
            text-shadow: 
                0 0 20px rgba(214, 168, 75, 0.6),
                0 0 40px rgba(214, 168, 75, 0.4),
                0 0 60px rgba(214, 168, 75, 0.2);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
        }

        @keyframes pulse {
            0%, 100% {
                text-shadow: 
                    0 0 20px rgba(214, 168, 75, 0.6),
                    0 0 40px rgba(214, 168, 75, 0.4),
                    0 0 60px rgba(214, 168, 75, 0.2);
            }
            50% {
                text-shadow: 
                    0 0 30px rgba(214, 168, 75, 0.8),
                    0 0 50px rgba(214, 168, 75, 0.6),
                    0 0 80px rgba(214, 168, 75, 0.4);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.8s ease-out;
        }

        .login-input {
            transition:
                border-color 0.3s ease,
                box-shadow 0.3s ease,
                background-color 0.3s ease,
                transform 0.2s ease;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
        }

        .login-input:hover {
            border-color: #94a3b8;
        }

        .login-input:focus {
            border-color: #7A1F2B;
            background-color: #ffffff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(122, 31, 43, 0.08);
        }

        @media (forced-colors: active) {
            .login-input:focus-visible {
                outline: 2px solid Highlight;
                outline-offset: 2px;
            }
        }

        .login-button {
            position: relative;
            overflow: hidden;
            transition:
                background-color 0.3s ease,
                transform 0.2s ease,
                box-shadow 0.3s ease;
        }

        .login-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        .login-button:hover::before {
            width: 300px;
            height: 300px;
        }

        .login-button:hover {
            background-color: #58151E;
            box-shadow: 
                0 10px 30px rgba(88, 21, 30, 0.30),
                0 0 20px rgba(214, 168, 75, 0.20);
            transform: translateY(-2px);
        }

        .login-button:active {
            transform: translateY(0) scale(0.98);
            box-shadow: 
                0 5px 15px rgba(88, 21, 30, 0.20);
        }

        .login-button:focus-visible {
            outline: 3px solid #D6A84B;
            outline-offset: 3px;
        }

        /* Logo Glow Effect */
        .logo-glow {
            box-shadow: 
                0 0 20px rgba(214, 168, 75, 0.4),
                0 0 40px rgba(214, 168, 75, 0.2),
                0 4px 20px rgba(122, 31, 43, 0.2);
            animation: logoGlow 3s ease-in-out infinite;
        }

        @keyframes logoGlow {
            0%, 100% {
                box-shadow: 
                    0 0 20px rgba(214, 168, 75, 0.4),
                    0 0 40px rgba(214, 168, 75, 0.2),
                    0 4px 20px rgba(122, 31, 43, 0.2);
            }
            50% {
                box-shadow: 
                    0 0 30px rgba(214, 168, 75, 0.6),
                    0 0 60px rgba(214, 168, 75, 0.3),
                    0 6px 30px rgba(122, 31, 43, 0.3);
            }
        }

        /* Decorative elements animation */
        .float-animation {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        /* Gold accent pulse */
        .gold-pulse {
            animation: goldPulse 2s ease-in-out infinite;
        }

        @keyframes goldPulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.8;
                transform: scale(1.05);
            }
        }

        /* Password toggle button focus */
        #togglePassword:focus-visible {
            outline: 2px solid #7A1F2B;
            outline-offset: 2px;
            border-radius: 6px;
        }

        /* General focus-visible for all interactive elements */
        button:focus-visible,
        a:focus-visible {
            outline: 2px solid #7A1F2B;
            outline-offset: 2px;
        }

        /* Input error state */
        .login-input.border-red-500 {
            border-color: #ef4444 !important;
        }

        .login-input.border-red-500:focus {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.08);
        }
        @media (prefers-reduced-motion: reduce) {
            .talastas-pattern,
            .talastas-highlight,
            .talastas-glow,
            .logo-glow {
                animation: none;
            }

            .login-input,
            .login-button,
            .login-button::before {
                transition: none;
            }
        }
    </style>
</head>

<body class="antialiased bg-[#F8F6F6]">

    <main class="min-h-screen flex">

        <!-- ============================================================
             LEFT SIDE - TALASTAS BRANDING
        ============================================================= -->
        <section
            class="
                hidden
                lg:flex
                lg:w-[52%]
                xl:w-[55%]
                min-h-screen
                relative
                overflow-hidden
                brand-gradient
                talastas-pattern
                text-white
            "
        >

            <!-- Decorative Map / Network SVG -->
            <div
                class="
                    absolute
                    inset-0
                    pointer-events-none
                    overflow-hidden
                "
            >
                <svg
                    class="
                        absolute
                        w-212.5
                        h-212.5
                        -right-67.5
                        top-1/2
                        -translate-y-1/2
                        opacity-[0.12]
                    "
                    viewBox="0 0 800 800"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <!-- Connection lines -->
                    <path
                        d="M90 180L230 105L360 185L510 110L660 210"
                        stroke="white"
                        stroke-width="2"
                    />

                    <path
                        d="M230 105L255 330L425 285L510 110"
                        stroke="white"
                        stroke-width="2"
                    />

                    <path
                        d="M90 180L165 410L255 330"
                        stroke="white"
                        stroke-width="2"
                    />

                    <path
                        d="M255 330L355 510L425 285"
                        stroke="white"
                        stroke-width="2"
                    />

                    <path
                        d="M425 285L570 410L660 210"
                        stroke="white"
                        stroke-width="2"
                    />

                    <path
                        d="M165 410L270 635L355 510"
                        stroke="white"
                        stroke-width="2"
                    />

                    <path
                        d="M355 510L505 650L570 410"
                        stroke="white"
                        stroke-width="2"
                    />

                    <!-- Nodes -->
                    <circle cx="90" cy="180" r="10" fill="white"/>
                    <circle cx="230" cy="105" r="10" fill="white"/>
                    <circle cx="360" cy="185" r="10" fill="white"/>
                    <circle cx="510" cy="110" r="10" fill="white"/>
                    <circle cx="660" cy="210" r="10" fill="white"/>

                    <circle cx="165" cy="410" r="10" fill="white"/>
                    <circle cx="255" cy="330" r="10" fill="white"/>
                    <circle cx="425" cy="285" r="10" fill="white"/>
                    <circle cx="570" cy="410" r="10" fill="white"/>

                    <circle cx="270" cy="635" r="10" fill="white"/>
                    <circle cx="355" cy="510" r="10" fill="white"/>
                    <circle cx="505" cy="650" r="10" fill="white"/>
                </svg>
            </div>

            <!-- Decorative Circles -->
            <div
                class="
                    absolute
                    -top-32
                    -left-32
                    w-80
                    h-80
                    rounded-full
                    border
                    border-white/10
                "
            ></div>

            <div
                class="
                    absolute
                    -top-16
                    -left-16
                    w-48
                    h-48
                    rounded-full
                    border
                    border-white/10
                "
            ></div>

            <!-- Content -->
            <div
                class="
                    relative
                    z-10
                    flex
                    flex-col
                    justify-between
                    w-full
                    px-14
                    py-12
                    xl:px-20
                    xl:py-16
                "
            >

                <!-- Concise brand message in the original visual area. -->
                <div class="flex max-w-xl flex-1 flex-col justify-center py-10">
                    <span class="mb-6 block h-0.5 w-12 bg-[#D6A84B]" aria-hidden="true"></span>
                    <h2 class="text-[36px] xl:text-[48px] leading-[1.1] font-extrabold tracking-[-0.04em]">
                        Alam.<br>
                        Kabisado.<br>
                        <span class="text-[#E7C36C]">Tukoy.</span>
                    </h2>
                    <p class="mt-5 text-sm leading-relaxed text-white/70">
                        Impormasyong malinaw.<br>
                        Desisyong mas tiyak.
                    </p>
                </div>

                <!-- ====================================================
                     LEFT FOOTER
                ===================================================== -->
                <footer>

                    <div
                        class="
                            border-t
                            border-white/10
                            pt-5
                        "
                    >

                        <p
                            class="
                                text-xs
                                text-white/60
                                font-medium
                            "
                        >
                            Lipa City, Batangas
                        </p>

                    </div>

                </footer>

            </div>

        </section>

        <!-- ============================================================
             RIGHT SIDE - LOGIN
        ============================================================= -->
        <section
            class="
                w-full
                lg:w-[48%]
                xl:w-[45%]
                min-h-screen
                flex
                items-center
                justify-center
                px-5
                sm:px-8
                md:px-12
                py-10
                bg-white
            "
        >

            <div class="w-full max-w-107.5">

                <img src="{{ asset('images/LandScapeBG.png') }}"
                    alt="TALASTAS - GIS-Based ICT Mapping System" width="866" height="288"
                    class="mb-5 block h-auto w-full max-w-[336px] sm:max-w-[380px]">

                <!-- ====================================================
                     LOGIN HEADER
                ===================================================== -->
                <div>

                    <span class="mb-4 block h-0.5 w-12 bg-[#D6A84B]" aria-hidden="true"></span>

                    <h2
                        class="
                            text-3xl
                            sm:text-[36px]
                            leading-tight
                            font-bold
                            tracking-tight
                            text-slate-900
                        "
                    >
                        Welcome Back
                    </h2>

                    <p
                        class="
                            mt-1.5
                            text-sm
                            leading-relaxed
                            text-slate-600
                            font-medium
                        "
                    >
                        Sign in to your TALASTAS account
                    </p>

                </div>

                <!-- ====================================================
                     LOGIN FORM
                ===================================================== -->
            <form class="mt-6" method="POST" action="{{ route('login.submit') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="mb-2 block text-slate-700">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                        required autofocus autocomplete="username" aria-describedby="emailError"
                        aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                        class="login-input h-12 w-full rounded-xl border bg-white px-3 text-slate-900 outline-none {{ $errors->has('email') ? 'border-red-500' : 'border-slate-300' }}">
                    <p id="emailError" class="mt-1.5 min-h-5 text-xs leading-5 text-red-700" aria-live="polite">@error('email'){{ $message }}@enderror</p>
                </div>

                <div>
                    <label for="password" class="mb-2 block text-slate-700">Password</label>
                    <div class="relative">
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                            aria-describedby="passwordError" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                            class="login-input h-12 w-full rounded-xl border bg-white py-2 pl-3 pr-12 text-slate-900 outline-none {{ $errors->has('password') ? 'border-red-500' : 'border-slate-300' }}">
                        <button id="togglePassword" type="button" aria-label="Show password" aria-controls="password" aria-pressed="false"
                            class="absolute inset-y-0 right-0 flex h-12 w-12 items-center justify-center rounded-xl text-slate-500 hover:text-red-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                            <svg id="eyeOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg id="eyeClosed" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.223-3.592m3.31-2.048A9.956 9.956 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.132 5.411M15 12a3 3 0 01-3 3m-2.121-5.121A3 3 0 0112 9m9 12L3 3" />
                            </svg>
                        </button>
                    </div>
                    <p id="passwordError" class="mt-1.5 min-h-5 text-xs leading-5 text-red-700" aria-live="polite">@error('password'){{ $message }}@enderror</p>
                </div>

                <label for="remember" class="mt-2 inline-flex min-h-11 cursor-pointer items-center gap-2 text-slate-600">
                    <input id="remember" type="checkbox" name="remember" value="1" @checked(old('remember'))
                        class="h-4 w-4 accent-red-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                    Remember me
                </label>

                <button type="submit" class="login-button mt-4 flex min-h-12 w-full items-center justify-center rounded-xl bg-[#7A1F2B] px-4 py-3 text-white hover:bg-[#58151E] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                    Sign In
                </button>
            </form>
            <p class="mt-4 text-center text-sm"><a class="text-red-900 underline" href="{{ route('security.recovery') }}">Forgot password?</a></p>
            @if (session('status'))
                <p role="status" class="mt-4 text-center text-sm text-red-900">{{ session('status') }}</p>
            @endif
            <div class="flex justify-center text-center text-slate-600 [&>nav]:justify-center">@include('partials.legal-links')</div>

            </div>

        </section>

    </main>

    <!-- ================================================================
         PASSWORD VISIBILITY
    ================================================================= -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const passwordInput = document.getElementById('password');
            const togglePassword = document.getElementById('togglePassword');
            const eyeOpen = document.getElementById('eyeOpen');
            const eyeClosed = document.getElementById('eyeClosed');

            togglePassword.addEventListener('click', () => {
                const showPassword = passwordInput.type === 'password';
                passwordInput.type = showPassword ? 'text' : 'password';
                togglePassword.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
                togglePassword.setAttribute('aria-pressed', String(showPassword));
                eyeOpen.classList.toggle('hidden', showPassword);
                eyeClosed.classList.toggle('hidden', !showPassword);
            });
        });
    </script>

</body>
</html>
