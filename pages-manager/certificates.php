<?php
require "../php/auth-logout/auth.php";
requireRole(2);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png" class="w-24">
    <title>UTH - Certificates</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <?php include '../certificate-renderer.php'; ?>
    <style>
        .hero-grid {
            background-image:
                linear-gradient(rgba(255, 255, 255, .07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .07) 1px, transparent 1px);
            background-size: 32px 32px;
            -webkit-mask-image: radial-gradient(ellipse at top right, #000 0%, transparent 70%);
            mask-image: radial-gradient(ellipse at top right, #000 0%, transparent 70%);
        }
    </style>
</head>

<body class=" antialiased">
    <?php include '../sidebar-manager.php'; ?>
    <main>

        <div class="main-header">
            <span class="page-breadcrumbs">Certificates</span>
            <?php include '../notification-bell.php'; ?>
        </div>

        <div class="main-container">
            <section class="relative overflow-hidden rounded-3xl bg-[#234CA1] p-5 md:p-8 text-white shadow-lg">
                <div class="absolute -right-10 -top-10 w-60 h-60 rounded-full bg-blue-600/30 blur-3xl pointer-events-none"></div>
                <div class="absolute right-1/3 -bottom-10 w-60 h-60 rounded-full bg-cyan-500/20 blur-3xl pointer-events-none"></div>
                <div class="hero-grid absolute inset-0 pointer-events-none"></div>
    
                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-5">
                    <div class="space-y-2 min-w-0">
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/10 text-xs font-semibold text-cyan-300">
                            <i class="fa-solid fa-certificate text-amber-400"></i> My Certificates
                        </span>
                        <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight">Your Certificates</h1>
                        <p class="text-slate-300 text-sm max-w-xl">Earned by attending a training and timing out when it ends. Download any of them as a PDF.</p>
                    </div>
    
                    <div class="shrink-0 rounded-2xl bg-white/10 border border-white/10 px-6 py-4 text-center">
                        <p id="certCount" class="text-4xl font-black">—</p>
                        <p class="text-[11px] uppercase tracking-wider text-slate-300">Earned</p>
                    </div>
                </div>
            </section>
    
            <div id="certGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mt-5">
                <div class="col-span-full text-center text-gray-400 py-10"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading...</div>
            </div>
        </div>

        <?php include '../feedback-button.php'; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        lucide.createIcons();
        AOS.init({
            duration: 600,
            once: false
        });

        const el = id => document.getElementById(id);

        function esc(str) {
            const d = document.createElement("div");
            d.textContent = str ?? "";
            return d.innerHTML;
        }

        let certs = [];

        async function load() {

            try {

                const res = await fetch("../php/certificates/get-my-certificates.php");
                const data = await res.json();

                if (data.status !== "success") {
                    el("certGrid").innerHTML = `<p class="col-span-full text-center text-red-500 py-10">${esc(data.message)}</p>`;
                    return;
                }

                certs = data.certificates;
                await CertRenderer.setAssets(data.assets);

                const STATUS = {
                    valid: ["Valid", "bg-emerald-100 text-emerald-700"],
                    expired: ["Expired", "bg-amber-100 text-amber-700"],
                    revoked: ["Revoked", "bg-red-100 text-[#D02027]"]
                };

                el("certCount").textContent = certs.length;

                if (!certs.length) {
                    el("certGrid").innerHTML = `
                        <div class="col-span-full bg-white rounded-2xl border border-gray-200 shadow-md p-10 text-center">
                            <span class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 inline-flex items-center justify-center text-2xl mb-3"><i class="fa-solid fa-certificate"></i></span>
                            <h3 class="font-eurostile-bold text-[#234CA1] text-lg">No certificates yet</h3>
                            <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">Attend a live training and time out when it ends. Your certificate will appear here automatically.</p>
                            <a href="calendar.php" class="inline-flex items-center gap-2 mt-5 px-6 h-11 rounded-xl bg-[#234CA1] text-white text-sm font-eurostile-bold">View Calendar <i class="fa-solid fa-arrow-right"></i></a>
                        </div>`;
                    return;
                }

                el("certGrid").innerHTML = certs.map((c, i) => {

                    const st = CertRenderer.certStatus(c);
                    const chip = STATUS[st];

                    return `
                        <div class="bg-white rounded-2xl shadow-md border ${st === "revoked" ? "border-red-200" : "border-gray-200"} overflow-hidden flex flex-col min-w-0">
                            <div class="bg-slate-100 p-3 ${st === "revoked" ? "opacity-50" : ""}">
                                <canvas data-i="${i}" class="w-full h-auto rounded-md shadow-sm bg-white"></canvas>
                            </div>
                            <div class="p-4 flex flex-col gap-y-2 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <h3 class="font-eurostile-bold text-[#234CA1] break-words min-w-0">${esc(c.title)}</h3>
                                    <span class="text-[10px] font-bold px-2 py-1 rounded-full shrink-0 ${chip[1]}">${chip[0]}</span>
                                </div>
                                <p class="text-xs text-gray-500"><i class="fa-regular fa-calendar mr-1"></i>${CertRenderer.formatDate(c.event_date)}${c.brand_name ? ` · ${esc(c.brand_name)}` : ""}</p>
                                ${c.valid_until ? `<p class="text-xs ${st === "expired" ? "text-amber-600 font-semibold" : "text-gray-400"}">${st === "expired" ? "Expired" : "Valid until"} ${CertRenderer.formatDate(c.valid_until)}</p>` : ""}
                                ${st === "revoked" ? `<p class="text-xs text-[#D02027]">This certificate was withdrawn, so it can't be downloaded.</p>` : ""}
                                <p class="font-mono text-[11px] text-gray-400 break-all">${esc(c.certificate_no)}</p>
                                <button type="button" data-download="${i}" ${st === "revoked" ? "disabled" : ""} class="mt-2 h-11 rounded-xl bg-[#234CA1] hover:bg-[#193B82] text-white text-sm font-eurostile-bold transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                                    <i class="fa-solid fa-file-pdf mr-2"></i> Download PDF
                                </button>
                            </div>
                        </div>`;

                }).join("");
                // Previews, one at a time to keep memory low
                for (const canvas of document.querySelectorAll("#certGrid canvas")) {
                    const c = certs[Number(canvas.dataset.i)];
                    try {
                        await CertRenderer.renderCert(canvas, c, {
                            width: 900
                        });
                    } catch (err) {
                        console.error(err);
                    }
                }

            } catch (err) {
                console.error(err);
                el("certGrid").innerHTML = `<p class="col-span-full text-center text-red-500 py-10">Failed to load your certificates.</p>`;
            }

        }

        el("certGrid").addEventListener("click", async e => {

            const btn = e.target.closest("[data-download]");
            if (!btn) return;

            const c = certs[Number(btn.dataset.download)];
            const label = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Preparing...';

            try {
                await CertRenderer.downloadCert(c);
            } catch (err) {
                console.error(err);
                Swal.fire({
                    icon: "error",
                    title: "Couldn't create the PDF",
                    text: "Please try again.",
                    confirmButtonColor: "#234CA1"
                });
            } finally {
                btn.disabled = false;
                btn.innerHTML = label;
            }

        });

        load();
    </script>
</body>

</html>