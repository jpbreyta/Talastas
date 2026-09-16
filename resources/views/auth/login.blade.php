<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>TALASTAS | Administrator Portal</title>

    <!--
        Replace this with the official TALASTAS logo once available.
        Example:
        /images/talastas-logo.png
    -->
    <link rel="icon" type="image/png" href="{{ asset('images/talastas.svg') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link
        href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800"
        rel="stylesheet"
    />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Instrument Sans', sans-serif;
        }

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
            box-shadow: 
                0 0 0 4px rgba(122, 31, 43, 0.10),
                0 4px 12px rgba(122, 31, 43, 0.08);
            transform: translateY(-1px);
        }

        .login-input:focus-visible {
            outline: 2px solid #7A1F2B;
            outline-offset: 2px;
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
            box-shadow: 
                0 0 0 4px rgba(239, 68, 68, 0.10),
                0 4px 12px rgba(239, 68, 68, 0.08);
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

                <!-- ====================================================
                     TOP BRAND
                ===================================================== -->
                <header class="flex items-center gap-4">

                    <!-- Logo -->
                    <div
                        class="
                            h-14
                            w-14
                            rounded-2xl
                            bg-white
                            flex
                            items-center
                            justify-center
                            logo-glow
                            overflow-hidden
                        "
                    >
                        <img
                            src="{{ asset('images/logo_ict_mapping.png') }}"
                            alt="TALASTAS Logo"
                            class="h-11 w-11 object-contain"
                        >
                    </div>

                    <div>
                        <h1
                            class="
                                text-[28px]
                                font-extrabold
                                tracking-[0.16em]
                                leading-none
                                talastas-highlight
                                talastas-glow
                            "
                        >
                            TALASTAS
                        </h1>

                        <p
                            class="
                                text-xs
                                text-white/80
                                mt-2
                                tracking-wide
                                font-semibold
                            "
                        >
                            ICT Infrastructure Intelligence
                        </p>
                    </div>
                </header>

                <!-- ====================================================
                     MAIN BRAND MESSAGE
                ===================================================== -->
                <div class="max-w-2xl">

                    <!-- Tagline -->
                    <div class="flex items-center gap-3 mb-6">

                        <span
                            class="
                                block
                                h-0.5
                                w-12
                                bg-[#D6A84B]
                                gold-pulse
                            "
                        ></span>

                        <p
                            class="
                                uppercase
                                tracking-[0.24em]
                                text-xs
                                font-bold
                                text-[#E7C36C]
                            "
                        >
                            Map. Understand. Decide.
                        </p>

                    </div>

                    <!-- Main Heading -->
                    <h2
                        class="
                            text-[42px]
                            xl:text-[56px]
                            leading-[1.05]
                            font-extrabold
                            tracking-[-0.04em]
                            max-w-xl
                            animate-fadeInUp
                        "
                    >
                        ICT Infrastructure
                        <span class="text-[#E7C36C] talastas-glow">
                            Intelligence
                        </span>
                    </h2>

                    <!-- Description -->
                    <p
                        class="
                            mt-6
                            text-base
                            leading-relaxed
                            text-white/75
                            max-w-lg
                        "
                    >
                        GIS-based infrastructure mapping and gap analysis
                        for Lipa City's public institutions.
                    </p>

                    <!-- TALASTAS Meaning -->
                    <div
                        class="
                            mt-12
                            max-w-lg
                            rounded-2xl
                            border
                            border-white/15
                            bg-white/5
                            backdrop-blur-sm
                            px-7
                            py-6
                            hover:bg-white/10
                            hover:border-white/25
                            transition-all
                            duration-300
                            float-animation
                        "
                    >
                        <div class="flex gap-4">

                            <div
                                class="
                                    mt-1
                                    h-10
                                    w-1
                                    rounded-full
                                    bg-[#D6A84B]
                                    shrink-0
                                    gold-pulse
                                "
                            ></div>

                            <div>

                                <p
                                    class="
                                        text-xs
                                        uppercase
                                        tracking-[0.2em]
                                        font-semibold
                                        text-white/60
                                        mb-3
                                    "
                                >
                                    <span class="talastas-highlight font-extrabold text-sm">TALASTAS</span>
                                </p>

                                <p
                                    class="
                                        text-[15px]
                                        leading-relaxed
                                        text-white/95
                                        font-semibold
                                    "
                                >
                                    Technology And Location Analytics
                                    Spatial Tracking & Assessment System
                                </p>

                            </div>

                        </div>
                    </div>

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

                <!-- ====================================================
                     MOBILE BRANDING
                ===================================================== -->
                <div class="lg:hidden mb-10 text-center animate-fadeInUp">

                    <div
                        class="
                            mx-auto
                            h-16
                            w-16
                            rounded-2xl
                            bg-[#7A1F2B]
                            flex
                            items-center
                            justify-center
                            logo-glow
                        "
                    >

                        <img
                            src="{{ asset('images/logo_ict_mapping.png') }}"
                            alt="TALASTAS Logo"
                            class="
                                h-12
                                w-12
                                object-contain
                                bg-white
                                rounded-xl
                                p-1
                            "
                        >

                    </div>

                    <h1
                        class="
                            mt-4
                            text-3xl
                            font-extrabold
                            tracking-[0.14em]
                            text-[#7A1F2B]
                            talastas-highlight
                        "
                        style="
                            background: linear-gradient(135deg, #7A1F2B 0%, #D6A84B 50%, #7A1F2B 100%);
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                            background-clip: text;
                        "
                    >
                        TALASTAS
                    </h1>

                    <p
                        class="
                            mt-1
                            text-xs
                            font-bold
                            text-[#7A1F2B]/70
                        "
                    >
                        Map. Understand. Decide.
                    </p>

                </div>

                <!-- ====================================================
                     LOGIN HEADER
                ===================================================== -->
                <div>

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
                        Administrator Sign In
                    </h2>

                    <p
                        class="
                            mt-3
                            text-sm
                            leading-relaxed
                            text-slate-600
                            font-medium
                        "
                    >
                        Enter your credentials to access the system.
                    </p>

                </div>

                <!-- ====================================================
                     LOGIN FORM
                ===================================================== -->
                <form
                    method="POST"
                    action="{{ url('/login') }}"
                    class="mt-8"
                >

                    @csrf

                    <!-- =================================================
                         EMAIL
                    ================================================== -->
                    <div class="mb-5">

                        <label
                            for="email"
                            class="
                                block
                                text-sm
                                font-semibold
                                text-slate-700
                                mb-2
                            "
                        >
                            Email Address
                        </label>

                        <div class="relative">

                            <!-- Email Icon -->
                            <div
                                class="
                                    pointer-events-none
                                    absolute
                                    inset-y-0
                                    left-0
                                    flex
                                    items-center
                                    pl-4
                                    text-slate-400
                                "
                            >
                                <svg
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8m-18 8V8a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"
                                    />
                                </svg>
                            </div>

                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="Enter your email address"
                                class="
                                    login-input
                                    w-full
                                    pl-12
                                    pr-4
                                    py-3.5
                                    border
                                    {{ $errors->has('email') ? 'border-red-500' : '' }}
                                    rounded-xl
                                    text-sm
                                    text-slate-900
                                    placeholder:text-slate-400
                                    outline-none
                                "
                            >

                        </div>

                        @error('email')
                            <div
                                class="
                                    flex
                                    items-center
                                    gap-1.5
                                    mt-2
                                    text-xs
                                    text-red-600
                                "
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>

                                <span>{{ $message }}</span>
                            </div>
                        @enderror

                    </div>

                    <!-- =================================================
                         PASSWORD
                    ================================================== -->
                    <div class="mb-7">

                        <label
                            for="password"
                            class="
                                block
                                text-sm
                                font-semibold
                                text-slate-700
                                mb-2
                            "
                        >
                            Password
                        </label>

                        <div class="relative">

                            <!-- Lock -->
                            <div
                                class="
                                    pointer-events-none
                                    absolute
                                    inset-y-0
                                    left-0
                                    flex
                                    items-center
                                    pl-4
                                    text-slate-400
                                "
                            >
                                <svg
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M12 11c1.105 0 2 .895 2 2s-.895 2-2 2-2-.895-2-2 .895-2 2-2zm6-2V7a6 6 0 00-12 0v2m-1 0h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2v-8a2 2 0 012-2z"
                                    />
                                </svg>
                            </div>

                            <input
                                id="password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password"
                                class="
                                    login-input
                                    w-full
                                    pl-12
                                    pr-12
                                    py-3.5
                                    border
                                    {{ $errors->has('password') ? 'border-red-500' : '' }}
                                    rounded-xl
                                    text-sm
                                    text-slate-900
                                    placeholder:text-slate-400
                                    outline-none
                                "
                            >

                            <!-- Password Toggle -->
                            <button
                                type="button"
                                id="togglePassword"
                                class="
                                    absolute
                                    inset-y-0
                                    right-0
                                    flex
                                    items-center
                                    pr-4
                                    text-slate-400
                                    hover:text-[#7A1F2B]
                                    transition-colors
                                    duration-200
                                "
                                aria-label="Toggle password visibility"
                            >

                                <!-- Eye -->
                                <svg
                                    id="eyeOpen"
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                    />
                                </svg>

                                <!-- Eye Off -->
                                <svg
                                    id="eyeClosed"
                                    class="w-5 h-5 hidden"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.223-3.592m3.31-2.048A9.956 9.956 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.132 5.411M15 12a3 3 0 01-3 3m-2.121-5.121A3 3 0 0112 9m9 12L3 3"
                                    />
                                </svg>

                            </button>

                        </div>

                        @error('password')
                            <div
                                class="
                                    flex
                                    items-center
                                    gap-1.5
                                    mt-2
                                    text-xs
                                    text-red-600
                                "
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>

                                <span>{{ $message }}</span>
                            </div>
                        @enderror

                    </div>

                    <!-- =================================================
                         LOGIN BUTTON
                    ================================================== -->
                    <button
                        type="submit"
                        class="
                            login-button
                            w-full
                            flex
                            items-center
                            justify-center
                            gap-2.5
                            py-3.5
                            px-5
                            rounded-xl
                            bg-[#7A1F2B]
                            text-white
                            text-sm
                            font-semibold
                            focus:outline-none
                            focus:ring-4
                            focus:ring-[#7A1F2B]/15
                            relative
                            overflow-hidden
                        "
                    >

                        <span class="relative z-10">Sign In</span>

                        <svg
                            class="w-4 h-4 relative z-10"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>

                    </button>

                </form>

                <!-- ====================================================
                     SECURITY NOTICE
                ===================================================== -->
                <div
                    class="
                        mt-7
                        rounded-xl
                        border
                        border-[#7A1F2B]/10
                        bg-[#F7ECEE]/70
                        px-4
                        py-4
                    "
                >

                    <div class="flex items-start gap-3">

                        <div
                            class="
                                flex
                                items-center
                                justify-center
                                w-8
                                h-8
                                rounded-lg
                                bg-white
                                text-[#7A1F2B]
                                shrink-0
                            "
                        >

                            <svg
                                class="w-4.5 h-4.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2h-1V7a5 5 0 00-10 0v4H6a2 2 0 00-2 2v6a2 2 0 002 2zm3-10V7a3 3 0 116 0v4H9z"
                                />
                            </svg>

                        </div>

                        <div>

                            <p
                                class="
                                    text-xs
                                    font-semibold
                                    text-slate-800
                                "
                            >
                                Secure Access
                            </p>

                            <p
                                class="
                                    mt-1.5
                                    text-[11px]
                                    leading-relaxed
                                    text-slate-600
                                "
                            >
                                Access restricted to authorized personnel.
                                Activities are monitored for security.
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </section>

    </main>

    <!-- ================================================================
         PASSWORD VISIBILITY
    ================================================================= -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const passwordInput = document.getElementById('password');
            const togglePassword = document.getElementById('togglePassword');

            const eyeOpen = document.getElementById('eyeOpen');
            const eyeClosed = document.getElementById('eyeClosed');

            if (
                passwordInput &&
                togglePassword &&
                eyeOpen &&
                eyeClosed
            ) {

                togglePassword.addEventListener('click', function () {

                    const isPassword =
                        passwordInput.type === 'password';

                    passwordInput.type =
                        isPassword
                            ? 'text'
                            : 'password';

                    eyeOpen.classList.toggle(
                        'hidden',
                        isPassword
                    );

                    eyeClosed.classList.toggle(
                        'hidden',
                        !isPassword
                    );

                });

            }

        });
    </script>

</body>
</html>
