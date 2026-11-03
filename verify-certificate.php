<?php
require "config/config.php";
require_once __DIR__ . "/php/certificates/name-case.php";

$no = strtoupper(trim($_GET['no'] ?? ''));
$cert = null;

if ($no !== '' && preg_match('/^[A-Z0-9\-]{8,32}$/', $no)) {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT c.recipient_name, c.title, c.event_date, c.valid_until, c.revoked_at, b.brand_name
         FROM certificates c LEFT JOIN brands b ON b.brand_id = c.brand_id
         WHERE c.certificate_no = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "s", $no);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $cert = $result ? $result->fetch_assoc() : null;
    mysqli_stmt_close($stmt);
}

$state = 'notfound';

if ($cert) {
    if ($cert['revoked_at']) $state = 'revoked';
    elseif ($cert['valid_until'] && $cert['valid_until'] < date('Y-m-d')) $state = 'expired';
    else $state = 'valid';
}

$banner = [
    'valid'    => ['fa-circle-check', 'bg-emerald-50 text-emerald-700 border-emerald-200', 'Valid certificate', 'This certificate was issued by UAAGI Auto Group.'],
    'expired'  => ['fa-clock', 'bg-amber-50 text-amber-700 border-amber-200', 'Certificate expired', 'This certificate was genuine but is past its validity date.'],
    'revoked'  => ['fa-ban', 'bg-red-50 text-[#D02027] border-red-200', 'Certificate revoked', 'This certificate has been withdrawn and is no longer valid.'],
    'notfound' => ['fa-circle-question', 'bg-gray-50 text-gray-600 border-gray-200', $no === '' ? 'Verify a certificate' : 'Certificate not found', $no === '' ? 'Enter the certificate number printed on the certificate.' : 'We could not find a certificate with that number. Please check it and try again.'],
][$state];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/output.css">
    <link rel="icon" type="image/png" href="./assets/ulh-logo.png" class="w-24">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <title>Verify Certificate - UAAGI Training Hub</title>
</head>

<body class=" antialiased min-h-screen flex items-center justify-center p-4">
    <main class="w-full max-w-lg bg-white rounded-3xl shadow-lg border border-gray-100 p-6 sm:p-8">

        <div class="flex items-center w-full mb-5">
            <a href="./pages-superadmin/certificate-templates.php" class=" border border-[#234CA1] text-[#234CA1] rounded-full p-1 hover:bg-[#234CA1] hover:text-white transition-colors">
                <div data-lucide="arrow-big-left" class="w-6 h-6"></div>
            </a>
            <div class="flex items-center gap-2 w-full justify-center">
                <img src="./assets/ulh-logo.png" alt="UAAGI" class="h-12">
                <img src="./assets/Logo.png" alt="UAAGI" class="h-8">
            </div>
        </div>

        <div class="rounded-2xl border p-4 flex items-start gap-3 <?= $banner[1] ?>">
            <i class="fa-solid <?= $banner[0] ?> text-2xl mt-0.5"></i>
            <div>
                <h1 class="font-bold text-lg"><?= htmlspecialchars($banner[2]) ?></h1>
                <p class="text-sm opacity-90"><?= htmlspecialchars($banner[3]) ?></p>
            </div>
        </div>

        <?php if ($cert): ?>
            <dl class="mt-6 space-y-4 text-sm">
                <div>
                    <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Awarded to</dt>
                    <dd class="text-lg font-bold text-[#234CA1]"><?= htmlspecialchars(certProperCase($cert['recipient_name'])) ?></dd>
                </div>
                <div>
                    <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Training</dt>
                    <dd class="font-semibold"><?= htmlspecialchars($cert['title']) ?></dd>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Date</dt>
                        <dd><?= date('F j, Y', strtotime($cert['event_date'])) ?></dd>
                    </div>
                    <?php if ($cert['brand_name']): ?>
                        <div>
                            <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Brand</dt>
                            <dd><?= htmlspecialchars($cert['brand_name']) ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if ($cert['valid_until']): ?>
                        <div>
                            <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Valid until</dt>
                            <dd><?= date('F j, Y', strtotime($cert['valid_until'])) ?></dd>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Certificate no.</dt>
                    <dd class="font-mono text-xs text-gray-500"><?= htmlspecialchars($no) ?></dd>
                </div>
            </dl>
        <?php endif; ?>

        <form method="get" class="mt-6 flex flex-col sm:flex-row gap-2">
            <input type="text" name="no" value="<?= htmlspecialchars($no) ?>" placeholder="UAAGI-2026-XXXXXXXX"
                class="flex-1 h-11 px-4 rounded-xl border border-gray-200 font-mono text-sm uppercase" maxlength="32">
            <button type="submit" class="h-11 px-6 rounded-xl bg-[#234CA1] text-white text-sm font-bold">Verify</button>
        </form>

    </main>
    <script>
        lucide.createIcons();
        AOS.init({
            duration: 600,
            once: false
        });
    </script>
</body>

</html>