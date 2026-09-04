<?php
/**
 * "Iznajmi svoj alat i zaradi" Landing Page
 *
 * Gathers emails from people interested in listing their own tools for rent.
 */

$errors = [];
$success = false;
$submittedEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Nevažeći zahtev. Osvežite stranicu i pokušajte ponovo.';
    } else {
        $email = trim(post('email'));
        $submittedEmail = $email;
        $hcaptchaResponse = post('h-captcha-response');

        // Basic email validation
        if (empty($email)) {
            $errors[] = 'Email adresa je obavezna.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Unesite validnu email adresu.';
        } elseif (mb_strlen($email) > 255) {
            $errors[] = 'Email adresa je predugačka.';
        }

        // hCaptcha verification
        if (empty($errors)) {
            $remoteIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
            if (!verifyHcaptcha($hcaptchaResponse, $remoteIp)) {
                $errors[] = 'hCaptcha verifikacija nije uspela. Pokušajte ponovo.';
            }
        }

        // Save to database
        if (empty($errors)) {
            try {
                db()->insert(
                    "INSERT INTO early_adopters (email, ip_address, user_agent) VALUES (?, ?, ?)",
                    [
                        mb_strtolower($email, 'UTF-8'),
                        $_SERVER['REMOTE_ADDR'] ?? null,
                        $_SERVER['HTTP_USER_AGENT'] ?? null,
                    ]
                );
                $success = true;
                $submittedEmail = '';
            } catch (PDOException $e) {
                if (str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                    $errors[] = 'Ova email adresa je već prijavljena. Hvala na interesovanju!';
                } else {
                    error_log('Early adopter save error: ' . $e->getMessage());
                    $errors[] = 'Došlo je do greške prilikom čuvanja. Pokušajte ponovo kasnije.';
                }
            }
        }
    }
}

$pageTitle = 'Iznajmi svoj alat i zaradi - ' . SITE_NAME;
$pageDescription = 'Prijavite se da iznajmljujete svoj alat i zarađujete. Minimalna provizija platformi, a vi određujete cenu i dostupnost.';
$bodyClass = 'rent-your-tool-page';
$showSidebar = false;
$canonicalUrl = '/iznajmi-svoj-alat';

$hcaptchaConfigured = !empty(HCAPTCHA_SITE_KEY);

ob_start();
?>

<section class="ryt-hero">
    <div class="ryt-hero-content">
        <div class="ryt-hero-text">
            <span class="ryt-badge">Uskoro dostupno</span>
            <h1 class="ryt-headline">Iznajmi svoj alat i zaradi</h1>
            <p class="ryt-subheadline">
                Imate alat koji stoji i skuplja prašinu? Ostavite nam email i budite među
                prvima koji će moći da iznajmljuju svoju opremu putem Rent a Tool platforme.
            </p>
        </div>
        <div class="ryt-hero-form">
            <?php if ($success): ?>
                <div class="ryt-form-card ryt-form-success">
                    <div class="ryt-success-icon"><i class="fas fa-check-circle"></i></div>
                    <h2>Hvala na prijavi!</h2>
                    <p>
                        Zabeležili smo vašu email adresu. Obavestićemo vas čim funkcija
                        „Iznajmi svoj alat i zaradi" postane dostupna.
                    </p>
                    <a href="<?= url('') ?>" class="btn btn-primary btn-block">Nazad na početnu</a>
                </div>
            <?php else: ?>
                <div class="ryt-form-card">
                    <h2>Prijavi se za rani pristup</h2>
                    <p class="ryt-form-lead">
                        Prikupljamo zainteresovane korisnike. Ako se prijavi dovoljan broj ljudi,
                        kreiramo funkcionalnost.
                    </p>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <?php foreach ($errors as $error): ?>
                                <p><?= e($error) ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= url('iznajmi-svoj-alat') ?>" class="ryt-form">
                        <?= csrfField() ?>

                        <div class="form-group">
                            <label for="email" class="form-label required">Email adresa</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                placeholder="vas@email.com"
                                value="<?= e($submittedEmail) ?>"
                                required
                                maxlength="255"
                                autocomplete="email"
                            >
                        </div>

                        <?php if ($hcaptchaConfigured): ?>
                            <div class="form-group">
                                <div class="h-captcha" data-sitekey="<?= e(HCAPTCHA_SITE_KEY) ?>"></div>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary btn-block btn-large" <?= $hcaptchaConfigured ? '' : 'disabled' ?>>
                            Prijavi me
                        </button>

                        <?php if (!$hcaptchaConfigured): ?>
                            <p class="ryt-config-notice">
                                hCaptcha zaštita trenutno nije aktivirana. Kontaktirajte administratora.
                            </p>
                        <?php endif; ?>
                    </form>

                    <p class="ryt-privacy-note">
                        Email koristimo isključivo za obaveštenje o dostupnosti ove funkcije.
                        Ne šaljemo spam i ne delimo vašu adresu.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="ryt-section ryt-how-it-works">
    <div class="ryt-section-inner">
        <h2>Kako bi to radilo?</h2>
        <p class="ryt-section-intro">
            Ideja je jednostavna: vi postavljate svoj alat na platformu, a mi vam pomažemo da
            pronađete klijente. Za uslugu naplaćujemo samo malu proviziju.
        </p>

        <div class="ryt-steps">
            <div class="ryt-step">
                <h3>1. Postaviš alat</h3>
                <p>Fotografišeš alat, napišeš kratak opis i postaviš dnevnu cenu iznajmljivanja.</p>
            </div>

            <div class="ryt-step">
                <h3>2. Primaš rezervacije</h3>
                <p>Klijenti rezervišu online. Ti potvrđuješ ili odbijaš svaki zahtev.</p>
            </div>

            <div class="ryt-step">
                <h3>3. Zarađuješ</h3>
                <p>Iznajmiš alat, klijent ga koristi, a ti plaćaš malu proviziju i zarađuješ.</p>
            </div>
        </div>
    </div>
</section>

<section class="ryt-section ryt-benefits">
    <div class="ryt-section-inner">
        <h2>Zašto iznajmljivati svoj alat?</h2>

        <div class="ryt-benefits-grid">
            <div class="ryt-benefit">
                <h3>Dodatna zarada</h3>
                <p>Alat koji stoji ne donosi ništa. Iznajmljivanjem možeš znatno uvećati prihod.</p>
            </div>

            <div class="ryt-benefit">
                <h3>Ti određuješ uslove</h3>
                <p>Cenu, depozit i dostupnost definišeš sam. Nema pritiska i obaveza.</p>
            </div>

            <div class="ryt-benefit">
                <h3>Pristup klijentima</h3>
                <p>Koristiš već postojeću bazu korisnika Rent a Tool-a i naš marketing.</p>
            </div>
        </div>
    </div>
</section>

<section class="ryt-section ryt-faq">
    <div class="ryt-section-inner">
        <h2>Često postavljana pitanja</h2>

        <div class="ryt-faq-list">
            <div class="ryt-faq-item">
                <h3>Kolika bi bila provizija platformi?</h3>
                <p>
                    Cilj nam je da provizija bude minimalna — dovoljna da pokrije troškove
                    održavanja platforme, plaćanja i podrške. Tačan procenat ćemo objaviti
                    kada funkcija bude spremna.
                </p>
            </div>

            <div class="ryt-faq-item">
                <h3>Koji alat mogu da iznajmljujem?</h3>
                <p>
                    Bilo koji alat u ispravnom stanju — od ručnih alata i bušilica do
                    građevinske opreme. Zadržavamo pravo pregleda i odobrenja pre objavljivanja.
                </p>
            </div>

            <div class="ryt-faq-item">
                <h3>Šta ako se alat ošteti?</h3>
                <p>
                    Planiramo mehanizam depozita i osiguranja. Detalji o zaštiti vlasnika
                    i klijenta biće objavljeni zajedno sa funkcionalnošću.
                </p>
            </div>

            <div class="ryt-faq-item">
                <h3>Kada će ovo biti dostupno?</h3>
                <p>
                    Trenutno smo u fazi prikupljanja zainteresovanih. Ako se prijavi dovoljan
                    broj ljudi, kreiramo funkcionalnost u najkraćem roku. Prijavljeni će biti
                    obavešteni prvi.
                </p>
            </div>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();

$extraJsHead = '';
if ($hcaptchaConfigured && !$success) {
    $extraJsHead = '<script src="https://js.hcaptcha.com/1/api.js" async defer></script>';
}

$extraCss = '<style>
.ryt-hero {
    background: linear-gradient(135deg, var(--color-black) 0%, #1a1a1a 50%, #2d2d2d 100%);
    color: var(--color-white);
    padding: var(--spacing-xxl) var(--spacing-md);
}
.rent-your-tool-page .main-container {
    max-width: none;
    padding: 0;
    width: 100%;
}
.ryt-hero-content {
    max-width: var(--container-max);
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-xxl);
    align-items: center;
}
.ryt-hero-text {
    min-height: 280px;
}
.ryt-badge {
    display: inline-block;
    background: var(--color-accent);
    color: var(--color-white);
    padding: var(--spacing-xs) var(--spacing-md);
    border-radius: 50px;
    font-size: var(--font-size-small);
    font-weight: 600;
    margin-bottom: var(--spacing-lg);
}
.ryt-headline {
    font-size: clamp(28px, 5vw, 48px);
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: var(--spacing-lg);
    color: var(--color-white);
}
.ryt-subheadline {
    font-size: var(--font-size-large);
    color: var(--color-gray-300);
    margin-bottom: var(--spacing-xl);
    line-height: 1.6;
}
.ryt-hero-form {
    display: flex;
    justify-content: center;
}
.ryt-form-card {
    background: var(--color-white);
    color: var(--color-black);
    border-radius: var(--border-radius);
    padding: var(--spacing-xl);
    width: 100%;
    max-width: 420px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.25);
}
.ryt-form-card h2 {
    font-size: var(--font-size-h2);
    margin-bottom: var(--spacing-sm);
}
.ryt-form-lead {
    color: var(--color-gray-500);
    font-size: var(--font-size-small);
    margin-bottom: var(--spacing-lg);
}
.ryt-form .form-group {
    margin-bottom: var(--spacing-md);
}
.ryt-form .form-label {
    display: block;
    margin-bottom: var(--spacing-xs);
    font-weight: 600;
}
.ryt-form .form-label.required::after {
    content: " *";
    color: var(--color-error);
}
.ryt-form .form-control {
    width: 100%;
    padding: var(--spacing-sm) var(--spacing-md);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: var(--font-size-base);
}
.ryt-form .btn-block {
    width: 100%;
}
.ryt-privacy-note {
    font-size: var(--font-size-small);
    color: var(--color-gray-400);
    text-align: center;
    margin-top: var(--spacing-md);
    margin-bottom: 0;
}
.ryt-config-notice {
    font-size: var(--font-size-small);
    color: var(--color-warning);
    text-align: center;
    margin-top: var(--spacing-sm);
    margin-bottom: 0;
}
.ryt-form-success {
    text-align: center;
    padding: var(--spacing-xxl) var(--spacing-xl);
}
.ryt-success-icon {
    font-size: 64px;
    color: var(--color-success);
    margin-bottom: var(--spacing-md);
}
.ryt-section {
    padding: var(--spacing-xxl) var(--spacing-md);
}
.ryt-section-inner {
    max-width: var(--container-max);
    margin: 0 auto;
}
.ryt-section h2 {
    text-align: center;
    margin-bottom: var(--spacing-md);
}
.ryt-section-intro {
    text-align: center;
    max-width: 700px;
    margin: 0 auto var(--spacing-xl);
    color: var(--color-gray-500);
    font-size: var(--font-size-large);
}
.ryt-how-it-works {
    background: var(--color-gray-100);
}
.ryt-steps {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--spacing-lg);
}
.ryt-step {
    background: var(--color-white);
    border-radius: var(--border-radius);
    padding: var(--spacing-xl);
    text-align: center;
    border: 1px solid var(--border-color);
}
.ryt-step h3 {
    font-size: var(--font-size-large);
    margin-bottom: var(--spacing-sm);
}
.ryt-step p {
    color: var(--color-gray-500);
    margin-bottom: 0;
}
.ryt-benefits-grid {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-lg);
    max-width: 600px;
    margin: 0 auto;
}
.ryt-benefit {
    position: relative;
    background: var(--color-white);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: var(--spacing-lg);
    text-align: left;
}
.ryt-benefit h3 {
    font-size: var(--font-size-large);
    margin-bottom: var(--spacing-xs);
}
.ryt-benefit p {
    color: var(--color-gray-500);
    margin-bottom: 0;
}
.ryt-faq {
    background: var(--color-gray-100);
}
.ryt-faq-list {
    max-width: 800px;
    margin: 0 auto;
}
.ryt-faq-item {
    background: var(--color-white);
    border-radius: var(--border-radius);
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-md);
}
.ryt-faq-item h3 {
    font-size: var(--font-size-large);
    margin-bottom: var(--spacing-sm);
}
.ryt-faq-item p {
    color: var(--color-gray-500);
    margin-bottom: 0;
}
@media (max-width: 768px) {
    .ryt-hero-content {
        grid-template-columns: 1fr;
        text-align: center;
        gap: var(--spacing-xl);
    }
    .ryt-hero-text {
        min-height: auto;
    }
    .ryt-steps {
        grid-template-columns: 1fr;
    }
}
</style>';

include TEMPLATES_PATH . '/layout.php';
