<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingreso | Mantenimientos TI</title>
    <style>
        :root {
            --auth-bg: #eef3f9;
            --auth-panel: #ffffff;
            --auth-ink: #0f172a;
            --auth-muted: #64748b;
            --auth-soft: #f8fafc;
            --auth-line: #dbe5f2;
            --auth-blue: #2563eb;
            --auth-blue-dark: #102a56;
            --auth-blue-soft: rgba(37, 99, 235, .1);
            --auth-danger: #dc2626;
            --auth-shadow: 0 28px 80px rgba(15, 23, 42, .14);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--auth-ink);
            background:
                radial-gradient(circle at 18% 18%, rgba(37, 99, 235, .14), transparent 28%),
                radial-gradient(circle at 82% 12%, rgba(14, 165, 233, .12), transparent 26%),
                linear-gradient(135deg, #f8fafc 0%, #edf3fa 52%, #e6edf7 100%);
        }

        button,
        input {
            font: inherit;
        }

        .auth-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(420px, 1.05fr) minmax(420px, .95fr);
            isolation: isolate;
        }

        .auth-visual {
            position: relative;
            display: flex;
            align-items: stretch;
            min-height: 100vh;
            overflow: hidden;
            color: #ffffff;
            background:
                linear-gradient(135deg, rgba(6, 18, 38, .92), rgba(12, 47, 91, .78)),
                url("/images/login-background.png") center / cover no-repeat,
                linear-gradient(135deg, #07111f, #12376b);
        }

        .auth-visual::before,
        .auth-visual::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
        }

        .auth-visual::before {
            width: 420px;
            height: 420px;
            top: -120px;
            right: -120px;
            background: rgba(34, 211, 238, .22);
            filter: blur(18px);
        }

        .auth-visual::after {
            width: 520px;
            height: 520px;
            left: -180px;
            bottom: -180px;
            background: rgba(37, 99, 235, .32);
            filter: blur(26px);
        }

        .auth-visual-overlay {
            position: relative;
            z-index: 1;
            width: 100%;
            padding: clamp(34px, 5vw, 64px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, .08), rgba(255, 255, 255, 0) 38%),
                radial-gradient(circle at 75% 55%, rgba(255, 255, 255, .16), transparent 32%);
        }

        .auth-brand,
        .auth-card-brand {
            display: inline-flex;
            align-items: center;
            gap: 14px;
        }

        .auth-brand-mark {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #2563eb, #0ea5e9);
            color: #ffffff;
            font-weight: 800;
            letter-spacing: .02em;
            box-shadow: 0 16px 34px rgba(37, 99, 235, .35);
        }

        .auth-brand-copy strong,
        .auth-card-brand strong {
            display: block;
            font-size: 1.05rem;
            line-height: 1.1;
            font-weight: 750;
        }

        .auth-brand-copy span,
        .auth-card-brand span {
            display: block;
            margin-top: 4px;
            font-size: .84rem;
            color: rgba(255, 255, 255, .76);
        }

        .auth-visual-content {
            max-width: 560px;
        }

        .auth-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 18px;
            padding: 8px 12px;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 999px;
            background: rgba(255, 255, 255, .1);
            color: rgba(255, 255, 255, .86);
            font-size: .78rem;
            font-weight: 650;
        }

        .auth-visual-title {
            margin: 0;
            max-width: 620px;
            font-size: clamp(2.35rem, 5vw, 4.8rem);
            line-height: .98;
            letter-spacing: -.055em;
            font-weight: 760;
        }

        .auth-visual-text {
            margin: 22px 0 0;
            max-width: 480px;
            color: rgba(255, 255, 255, .78);
            font-size: 1rem;
            line-height: 1.75;
        }

        .auth-visual-footer {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            max-width: 560px;
        }

        .auth-metric {
            min-height: 94px;
            padding: 16px;
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 22px;
            background: rgba(255, 255, 255, .09);
            backdrop-filter: blur(12px);
        }

        .auth-metric strong {
            display: block;
            font-size: 1.25rem;
            font-weight: 760;
        }

        .auth-metric span {
            display: block;
            margin-top: 6px;
            color: rgba(255, 255, 255, .7);
            font-size: .82rem;
            line-height: 1.45;
        }

        .auth-form-pane {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: clamp(24px, 5vw, 64px);
        }

        .auth-card {
            width: min(420px, 100%);
            padding: 34px;
            border: 1px solid rgba(219, 229, 242, .92);
            border-radius: 30px;
            background: rgba(255, 255, 255, .94);
            box-shadow: var(--auth-shadow);
        }

        .auth-card-brand {
            margin-bottom: 30px;
        }

        .auth-card-brand .auth-brand-mark {
            width: 44px;
            height: 44px;
            border-radius: 14px;
        }

        .auth-card-brand span {
            color: var(--auth-muted);
        }

        .auth-card h1 {
            margin: 0;
            font-size: clamp(1.75rem, 3vw, 2.2rem);
            line-height: 1.12;
            letter-spacing: -.035em;
            color: #0b1730;
            font-weight: 760;
        }

        .auth-card-subtitle {
            margin: 10px 0 28px;
            color: var(--auth-muted);
            font-size: .96rem;
            line-height: 1.6;
        }

        .auth-error {
            margin: 0 0 20px;
            padding: 13px 14px;
            border-radius: 16px;
            border: 1px solid rgba(220, 38, 38, .24);
            background: rgba(254, 226, 226, .78);
            color: #991b1b;
            font-size: .9rem;
            line-height: 1.45;
        }

        .auth-field {
            margin-top: 18px;
        }

        .auth-field label,
        .auth-options label {
            display: block;
            color: #27364f;
            font-size: .9rem;
            font-weight: 650;
        }

        .auth-input-wrap {
            position: relative;
            margin-top: 8px;
        }

        .auth-input {
            width: 100%;
            min-height: 48px;
            padding: 13px 15px;
            border: 1px solid var(--auth-line);
            border-radius: 15px;
            outline: none;
            color: #0f172a;
            background: #fbfdff;
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .auth-input::placeholder {
            color: #94a3b8;
        }

        .auth-input:focus {
            border-color: rgba(37, 99, 235, .72);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
        }

        .auth-password-input {
            padding-right: 52px;
        }

        .auth-password-toggle {
            position: absolute;
            right: 8px;
            top: 50%;
            width: 38px;
            height: 38px;
            transform: translateY(-50%);
            display: grid;
            place-items: center;
            border: 0;
            border-radius: 12px;
            background: transparent;
            color: #64748b;
            cursor: pointer;
            transition: background .18s ease, color .18s ease;
        }

        .auth-password-toggle:hover,
        .auth-password-toggle:focus-visible {
            background: var(--auth-blue-soft);
            color: var(--auth-blue);
            outline: none;
        }

        .auth-password-toggle svg {
            width: 20px;
            height: 20px;
        }

        .auth-options {
            margin-top: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .auth-remember {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: #334155;
            cursor: pointer;
            user-select: none;
        }

        .auth-remember input {
            width: 17px;
            height: 17px;
            accent-color: var(--auth-blue);
        }

        .auth-link {
            border: 0;
            padding: 0;
            background: transparent;
            color: var(--auth-blue);
            font-size: .9rem;
            font-weight: 650;
            text-decoration: none;
            cursor: pointer;
        }

        .auth-link:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }

        .auth-submit {
            width: 100%;
            min-height: 50px;
            margin-top: 24px;
            border: 0;
            border-radius: 16px;
            color: #ffffff;
            background: linear-gradient(135deg, #2563eb, #173c8f);
            font-weight: 750;
            cursor: pointer;
            box-shadow: 0 18px 34px rgba(37, 99, 235, .25);
            transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
        }

        .auth-submit:hover,
        .auth-submit:focus-visible {
            transform: translateY(-1px);
            filter: saturate(1.08);
            box-shadow: 0 22px 42px rgba(37, 99, 235, .3);
            outline: none;
        }

        .auth-support {
            margin: 22px 0 0;
            padding-top: 20px;
            border-top: 1px solid var(--auth-line);
            color: var(--auth-muted);
            text-align: center;
            font-size: .88rem;
            line-height: 1.5;
        }

        @media (max-width: 980px) {
            .auth-shell {
                grid-template-columns: 1fr;
            }

            .auth-visual {
                display: none;
            }

            .auth-form-pane {
                min-height: 100vh;
                padding: 22px;
            }

            .auth-card {
                padding: 28px;
                border-radius: 26px;
            }
        }

        @media (max-width: 460px) {
            .auth-card {
                padding: 24px;
                border-radius: 22px;
            }

            .auth-options {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <main class="auth-shell">
        <section class="auth-visual" aria-label="Presentación de Mantenimientos TI">
            <div class="auth-visual-overlay">
                <div class="auth-brand">
                    <span class="auth-brand-mark" aria-hidden="true">TI</span>
                    <span class="auth-brand-copy">
                        <strong>Mantenimientos TI</strong>
                        <span>Gestión inteligente de mantenimiento tecnológico</span>
                    </span>
                </div>

                <div class="auth-visual-content">
                    <p class="auth-eyebrow">Plataforma administrativa segura</p>
                    <h2 class="auth-visual-title">Control operativo para equipos, usuarios y mantenimientos.</h2>
                    <p class="auth-visual-text">
                        Administra programaciones, ejecuciones y reportes desde un entorno confiable para el equipo de tecnología.
                    </p>
                </div>

                <div class="auth-visual-footer" aria-label="Beneficios del sistema">
                    <div class="auth-metric">
                        <strong>TI</strong>
                        <span>Gestión centralizada del mantenimiento.</span>
                    </div>
                    <div class="auth-metric">
                        <strong>24/7</strong>
                        <span>Acceso protegido al panel operativo.</span>
                    </div>
                    <div class="auth-metric">
                        <strong>SaaS</strong>
                        <span>Interfaz moderna para equipos administrativos.</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="auth-form-pane" aria-label="Formulario de inicio de sesión">
            <div class="auth-card">
                <div class="auth-card-brand">
                    <span class="auth-brand-mark" aria-hidden="true">TI</span>
                    <span class="auth-brand-copy">
                        <strong>Mantenimientos TI</strong>
                        <span>Acceso administrativo</span>
                    </span>
                </div>

                <h1>Bienvenido nuevamente</h1>
                <p class="auth-card-subtitle">Inicia sesión para administrar tus mantenimientos</p>

                @if (session('error_login'))
                    <div class="auth-error">{{ session('error_login') }}</div>
                @endif

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <div class="auth-field">
                        <label for="correo">Usuario o correo electrónico</label>
                        <div class="auth-input-wrap">
                            <input
                                id="correo"
                                class="auth-input"
                                type="email"
                                name="correo"
                                value="{{ old('correo') }}"
                                placeholder="nombre@empresa.com"
                                required
                                autofocus
                                autocomplete="email"
                            >
                        </div>
                    </div>

                    <div class="auth-field">
                        <label for="password">Password</label>
                        <div class="auth-input-wrap">
                            <input
                                id="password"
                                class="auth-input auth-password-input"
                                type="password"
                                name="password"
                                placeholder="Ingresa tu contraseña"
                                required
                                autocomplete="current-password"
                            >
                            <button
                                type="button"
                                class="auth-password-toggle"
                                aria-label="Mostrar contraseña"
                                aria-pressed="false"
                                data-password-toggle
                            >
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
                                    <path d="M2.8 12s3.2-5.5 9.2-5.5 9.2 5.5 9.2 5.5-3.2 5.5-9.2 5.5S2.8 12 2.8 12Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 14.4a2.4 2.4 0 1 0 0-4.8 2.4 2.4 0 0 0 0 4.8Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="auth-options">
                        <label class="auth-remember" for="remember">
                            <input id="remember" type="checkbox" name="remember" value="1">
                            <span>Recordarme</span>
                        </label>

                        <button class="auth-link" type="button">¿Olvidaste tu contraseña?</button>
                    </div>

                    <button class="auth-submit" type="submit">Iniciar sesión</button>
                </form>

                <p class="auth-support">Acceso protegido para personal autorizado de Mantenimientos TI.</p>
            </div>
        </section>
    </main>

    <script>
        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById('password');
                if (!input) {
                    return;
                }

                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                button.setAttribute('aria-label', isHidden ? 'Ocultar contraseña' : 'Mostrar contraseña');
            });
        });
    </script>
</body>
</html>
