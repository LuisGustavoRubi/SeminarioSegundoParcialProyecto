<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?> - Sistema de Citas Medicas</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --login-primary: #01d28e;
            --login-active-primary: #dc3545;
            --login-primary-dark: #00b67b;
            --login-text: #000;
            --login-muted: #999;
            --login-bg: #f4fbf8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Lato", Arial, sans-serif;
            color: var(--login-text);
            background:
                radial-gradient(circle at top left, rgba(1, 210, 142, 0.18), transparent 35%),
                radial-gradient(circle at bottom right, rgba(13, 110, 253, 0.12), transparent 30%),
                linear-gradient(135deg, #f9fffd 0%, var(--login-bg) 100%);
        }

        a {
            color: var(--login-primary);
            text-decoration: none;
        }

        a:hover {
            color: var(--login-primary-dark);
        }

        .ftco-section {
            min-height: 100vh;
            padding: 3rem 1rem;
            display: flex;
            align-items: center;
        }

        .heading-section {
            font-size: 28px;
            color: #000;
        }

        .login-shell {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
        }

        .wrap {
            width: 100%;
            overflow: hidden;
            background: #fff;
            border-radius: 6px;
            box-shadow: 0 10px 34px -15px rgba(0, 0, 0, 0.24);
        }

        .login-hero {
            min-height: 220px;
            background-image:
                linear-gradient(rgba(0, 0, 0, 0.18), rgba(0, 0, 0, 0.28)),
                url('../recursos/imagenes/logo-medicina.jpg');
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }

        .login-wrap {
            position: relative;
            padding: 2rem;
        }

        .login-wrap h3 {
            font-weight: 300;
        }

        .form-group {
            position: relative;
            z-index: 0;
            margin-bottom: 1.25rem;
        }

        .form-control {
            height: 48px;
            background: #fff;
            color: #000;
            font-size: 16px;
            border-radius: 5px;
            box-shadow: none;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .form-control::placeholder {
            color: transparent;
        }

        .form-control:focus,
        .form-control:active {
            outline: none;
            box-shadow: none;
            border-color: var(--login-primary);
        }

        .field-icon {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            color: rgba(0, 0, 0, 0.3);
            cursor: pointer;
            line-height: 1;
        }

        .form-control-placeholder {
            position: absolute;
            top: 2px;
            left: 0;
            padding: 7px 0 0 15px;
            transition: all 400ms;
            opacity: 0.6;
            pointer-events: none;
        }

        .form-control:focus+.form-control-placeholder,
        .form-control:not(:placeholder-shown)+.form-control-placeholder {
            transform: translate3d(0, -120%, 0);
            padding: 7px 0 0 0;
            opacity: 1;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
            color: var(--login-primary);
            font-weight: 700;
        }

        .btn-submit {
            background: var(--login-primary);
            border: 1px solid var(--login-primary);
            color: #fff;
            font-size: 15px;
            padding: 10px 20px;
            box-shadow: none;
            transition: all 0.3s ease;
        }

        .btn-submit:hover,
        .btn-submit:focus {
            background: transparent;
            color: var(--login-primary);
            border-color: var(--login-primary);
        }

        .btn-cancel {
            background: var(--login-active-primary);
            border: 1px solid var(--login-active-primary);
            color: #fff;
            font-size: 15px;
            padding: 10px 20px;
            box-shadow: none;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover,
        .btn-cancel:focus {
            background: transparent;
            color: var(--login-active-primary);
            border-color: var(--login-active-primary);
        }

        .login-brand {
            color: rgba(255, 255, 255, 0.95);
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        .login-brand small {
            display: block;
            font-size: 0.88rem;
            font-weight: 400;
            opacity: 0.9;
        }

        .hero-copy {
            color: rgba(255, 255, 255, 0.92);
            max-width: 22rem;
            font-size: 0.95rem;
        }

        @media (min-width: 992px) {
            .login-hero {
                min-height: 100%;
            }

            .login-wrap {
                padding: 3rem;
            }
        }

        @media (max-width: 767.98px) {
            .ftco-section {
                padding: 1.5rem 0.75rem;
            }

            .login-wrap {
                padding: 1.5rem;
            }
        }
    </style>
</head>