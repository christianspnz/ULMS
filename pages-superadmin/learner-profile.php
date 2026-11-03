<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(4);
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png" class="w-24">
    <title>UTH - Learner Profile</title>
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

<body class=" antialiased h-auto">
    <?php include('../sidebar-superadmin.php') ?>
    <main>

        <div class="flex justify-between items-center w-full">
            <span class="page-breadcrumbs">
                <a href="reports.php">Reports</a>
                <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor" />
                </svg>
                <span id="crumbName">Profile</span>
            </span>
            <?php include '../notification-bell.php'; ?>
        </div>

        <div id="profileRoot" class="flex flex-col gap-5 mt-5">
            <div class="text-center text-gray-400 py-16"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading profile...</div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        lucide.createIcons();
        AOS.init({
            duration: 600,
            once: false
        });

        const userId = new URLSearchParams(window.location.search).get("user_id");

        function esc(str) {
            const d = document.createElement("div");
            d.textContent = str ?? "";
            return d.innerHTML;
        }

        const escAttr = s => esc(s).replace(/"/g, "&quot;");

        const fmtDate = s => s ? new Date(String(s).slice(0, 10) + "T00:00:00")
            .toLocaleDateString(undefined, {
                month: "short",
                day: "numeric",
                year: "numeric"
            }) : "—";

        const STATUS_COLORS = {
            "Present": "bg-green-100 text-green-700",
            "Late Time Out": "bg-yellow-100 text-yellow-700",
            "Left Early": "bg-orange-100 text-orange-700",
            "No Time Out": "bg-purple-100 text-purple-700"
        };

        let certs = [];

        async function load() {

            const root = document.getElementById("profileRoot");

            if (!userId) {
                root.innerHTML = `<p class="text-center text-red-500 py-16">No learner specified.</p>`;
                return;
            }

            try {

                const res = await fetch(`../php/learners/get-learner-profile.php?user_id=${encodeURIComponent(userId)}`);
                const data = await res.json();

                if (data.status !== "success") {
                    root.innerHTML = `<p class="text-center text-red-500 py-16">${esc(data.message)}</p>`;
                    return;
                }

                certs = data.certificates;
                await CertRenderer.setAssets(data.assets);

                render(data);
                drawCertificates();

            } catch (err) {
                console.error(err);
                root.innerHTML = `<p class="text-center text-red-500 py-16">Failed to load this profile.</p>`;
            }

        }

        function render(d) {

            const p = d.profile;
            const fullName = [p.first_name, p.middle_name, p.last_name].filter(Boolean).join(" ");
            const initials = `${(p.first_name || "")[0] || ""}${(p.last_name || "")[0] || ""}`.toUpperCase();

            document.getElementById("crumbName").textContent = fullName;

            const avatar = p.profile_picture ?
                `<img src="../${escAttr(p.profile_picture)}" alt="" class="w-24 h-24 rounded-full object-cover ring-4 ring-white/20 shrink-0">` :
                `<div class="w-24 h-24 rounded-full bg-white/15 ring-4 ring-white/20 flex items-center justify-center text-3xl font-bold shrink-0">${esc(initials)}</div>`;

            const chip = (icon, text) => text ? `<span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/10 text-xs font-semibold"><i class="fa-solid ${icon}"></i>${esc(text)}</span>` : "";

            const hero = `
                <section class="relative overflow-hidden rounded-3xl bg-[#234CA1] p-5 md:p-8 text-white shadow-lg">
                    <div class="absolute -right-10 -top-10 w-60 h-60 rounded-full bg-blue-600/30 blur-3xl pointer-events-none"></div>
                    <div class="absolute right-1/3 -bottom-10 w-60 h-60 rounded-full bg-cyan-500/20 blur-3xl pointer-events-none"></div>
                    <div class="hero-grid absolute inset-0 pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col sm:flex-row sm:items-center gap-5">
                        ${avatar}
                        <div class="min-w-0 space-y-2">
                            <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight break-words">${esc(fullName)}</h1>
                            <p class="text-slate-200">${esc(p.position_name || "Sales Executive")}</p>
                            <div class="flex flex-wrap gap-2">
                                ${chip("fa-car", p.brands.join(", "))}
                                ${chip("fa-building", p.dealership_name)}
                                ${chip("fa-circle", p.status)}
                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold" style="background:${escAttr(d.tier.color)}26; color:${escAttr(d.tier.color)}">
                                    <i class="fa-solid ${escAttr(d.tier.icon)}"></i>${esc(d.tier.name)} · ${Number(d.points).toLocaleString()} pts
                                </span>
                            </div>
                        </div>
                        <div class="sm:ml-auto shrink-0">
                            <a href="print-learner-record.php?user_id=${encodeURIComponent(userId)}" target="_blank"
                               class="inline-flex items-center gap-2 px-4 h-11 rounded-xl bg-white text-[#234CA1] hover:bg-gray-100 text-sm font-eurostile-bold transition-colors">
                                <i class="fa-solid fa-print"></i> Print Record
                            </a>
                        </div>
                    </div>
                </section>`;

            const stat = (icon, tint, value, label) => `
                <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-4 flex items-center gap-3 min-w-0">
                    <span class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 ${tint}"><i class="fa-solid ${icon}"></i></span>
                    <div class="min-w-0">
                        <p class="text-2xl font-eurostile-black text-[#234CA1] leading-none">${value}</p>
                        <p class="text-xs text-gray-500 mt-1 truncate">${label}</p>
                    </div>
                </div>`;

            const stats = `
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                    ${stat("fa-graduation-cap", "bg-blue-50 text-[#234CA1]", d.courses.length, "Courses completed")}
                    ${stat("fa-calendar-check", "bg-cyan-50 text-cyan-600", d.trainings.length, "Trainings attended")}
                    ${stat("fa-certificate", "bg-amber-50 text-amber-500", d.certificates.length, "Certificates")}
                    ${stat("fa-star", "bg-emerald-50 text-emerald-600", Number(d.points).toLocaleString(), "Total points")}
                </div>`;

            const detail = (label, value) => `
                <div class="min-w-0">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">${label}</p>
                    <p class="text-sm text-slate-700 mt-0.5 break-words">${value ? esc(value) : "—"}</p>
                </div>`;

            const details = `
                <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-eurostile-bold text-[#234CA1] mb-4">Profile Details</h3>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
                        ${detail("Email", p.email)}
                        ${detail("Contact", p.contact_number)}
                        ${detail("Date hired", p.date_hired ? fmtDate(p.date_hired) : "")}
                        ${detail("Birthday", p.date_of_birth ? fmtDate(p.date_of_birth) : "")}
                    </div>
                </div>`;

            const score = v => v === null ? "—" : `${Number(v)}%`;

            const courses = `
                <div class="min-w-0 bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-eurostile-bold text-[#234CA1] mb-4">Completed Courses <span class="font-mono text-xs text-gray-400">(${d.courses.length})</span></h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead><tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-400">
                                <th class="py-2 px-2">Course</th><th class="py-2 px-2 text-center">Completed</th><th class="py-2 px-2 text-center">Pre-Test</th><th class="py-2 px-2 text-center">Post-Test</th>
                            </tr></thead>
                            <tbody>
                                ${d.courses.length ? d.courses.map(c => `
                                    <tr class="border-b border-gray-100">
                                        <td class="py-2.5 px-2 font-medium">${esc(c.course_title)}</td>
                                        <td class="py-2.5 px-2 text-center text-xs text-gray-500">${fmtDate(c.completed_at)}</td>
                                        <td class="py-2.5 px-2 text-center">${score(c.pre_score)}</td>
                                        <td class="py-2.5 px-2 text-center font-eurostile-bold text-[#234CA1]">${score(c.post_score)}</td>
                                    </tr>`).join("") : `<tr><td colspan="4" class="text-center text-gray-400 py-8">No completed courses yet.</td></tr>`}
                            </tbody>
                        </table>
                    </div>
                </div>`;

            const trainings = `
                <div class="min-w-0 bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-eurostile-bold text-[#234CA1] mb-4">Trainings Attended <span class="font-mono text-xs text-gray-400">(${d.trainings.length})</span></h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead><tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-400">
                                <th class="py-2 px-2">Training</th><th class="py-2 px-2 text-center">Date</th><th class="py-2 px-2 text-center">Status</th><th class="py-2 px-2 text-center">Certificate</th>
                            </tr></thead>
                            <tbody>
                                ${d.trainings.length ? d.trainings.map(t => `
                                    <tr class="border-b border-gray-100">
                                        <td class="py-2.5 px-2"><span class="font-medium">${esc(t.title)}</span><br><span class="text-[11px] text-gray-400">${esc(t.schedule_type)}</span></td>
                                        <td class="py-2.5 px-2 text-center text-xs text-gray-500">${fmtDate(t.event_date)}</td>
                                        <td class="py-2.5 px-2 text-center"><span class="text-xs font-bold px-2 py-1 rounded-full ${STATUS_COLORS[t.attendance_status] || "bg-gray-100 text-gray-500"}">${esc(t.attendance_status)}</span></td>
                                        <td class="py-2.5 px-2 text-center">${t.certificate_id ? '<i class="fa-solid fa-certificate text-amber-500" title="Certificate issued"></i>' : '<span class="text-gray-300">—</span>'}</td>
                                    </tr>`).join("") : `<tr><td colspan="4" class="text-center text-gray-400 py-8">No trainings attended yet.</td></tr>`}
                            </tbody>
                        </table>
                    </div>
                </div>`;

            const certSection = `
                <div class="min-w-0 bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-eurostile-bold text-[#234CA1] mb-4">Certifications <span class="font-mono text-xs text-gray-400">(${d.certificates.length})</span></h3>
                    ${d.certificates.length ? `
                        <div id="certGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            ${d.certificates.map((c, i) => {
                                const st = CertRenderer.certStatus(c);
                                const chip = CERT_STATUS[st];
                                return `
                                    <div class="rounded-xl border ${st === "revoked" ? "border-red-200" : "border-gray-200"} overflow-hidden flex flex-col min-w-0">
                                        <div class="bg-slate-100 p-2 ${st === "revoked" ? "opacity-50" : ""}"><canvas data-i="${i}" class="w-full h-auto rounded shadow-sm bg-white"></canvas></div>
                                        <div class="p-3 flex flex-col gap-y-1">
                                            <div class="flex items-start justify-between gap-2">
                                                <p class="font-semibold text-sm text-[#234CA1] break-words">${esc(c.title)}</p>
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0 ${chip[1]}">${chip[0]}</span>
                                            </div>
                                            <p class="text-xs text-gray-500">${c.source_type === "Course" ? "Course" : "Training"} · ${fmtDate(c.event_date)}${c.brand_name ? ` · ${esc(c.brand_name)}` : ""}</p>
                                            ${c.valid_until ? `<p class="text-xs text-gray-400">Valid until ${fmtDate(c.valid_until)}</p>` : ""}
                                            <p class="text-xs text-gray-500">Name on certificate: <b>${esc(CertRenderer.properName(c.recipient_name))}</b></p>
                                            <p class="font-mono text-[10px] text-gray-400 break-all">${esc(c.certificate_no)}</p>
                                            <div class="flex gap-2 mt-2">
                                                <button type="button" data-download="${i}" ${st === "revoked" ? "disabled" : ""} class="flex-1 h-10 rounded-lg border border-[#234CA1] text-[#234CA1] hover:bg-[#234CA1] hover:text-white text-xs font-eurostile-bold transition-colors disabled:opacity-40 disabled:cursor-not-allowed"><i class="fa-solid fa-file-pdf mr-1"></i> PDF</button>
                                                <button type="button" data-rename="${i}" class="w-10 h-10 rounded-lg border border-gray-200 text-gray-500 hover:border-[#234CA1] hover:text-[#234CA1]" title="Correct the name"><i class="fa-solid fa-pen"></i></button>
                                                ${st === "revoked" ?
                                                    `<button type="button" data-restore="${i}" class="px-3 h-10 rounded-lg border border-emerald-200 text-emerald-700 hover:bg-emerald-50 text-xs font-bold">Restore</button>` :
                                                    `<button type="button" data-revoke="${i}" class="w-10 h-10 rounded-lg border border-gray-200 text-gray-400 hover:text-[#D02027] hover:border-red-200" title="Revoke"><i class="fa-solid fa-ban"></i></button>`}
                                            </div>
                                        </div>
                                    </div>`;
                            }).join("")}
                        </div>` : `<p class="text-center text-gray-400 py-8">No certificates yet.</p>`}
                </div>`;

            document.getElementById("profileRoot").innerHTML = hero + stats + details +
                `<div class="grid grid-cols-1 xl:grid-cols-2 gap-5">${courses}${trainings}</div>` + certSection;

        }

        const dataFor = c => ({
            name: c.recipient_name,
            title: c.title,
            date: c.event_date,
            number: c.certificate_no
        });

        async function drawCertificates() {

            for (const canvas of document.querySelectorAll("#certGrid canvas")) {
                const c = certs[Number(canvas.dataset.i)];
                try {
                    await CertRenderer.renderCert(canvas, c, {
                        width: 800
                    });
                } catch (err) {
                    console.error(err);
                }
            }

        }

        document.getElementById("profileRoot").addEventListener("click", async e => {

            const dl = e.target.closest("[data-download]");
            const rename = e.target.closest("[data-rename]");
            const revoke = e.target.closest("[data-revoke]");
            const restore = e.target.closest("[data-restore]");

            if (dl) {

                const c = certs[Number(dl.dataset.download)];
                const label = dl.innerHTML;

                dl.disabled = true;
                dl.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Preparing...';

                try {
                    await CertRenderer.downloadCert(c);
                } catch (err) {
                    console.error(err);
                    Swal.fire({
                        icon: "error",
                        title: "Couldn't create the PDF",
                        confirmButtonColor: "#234CA1"
                    });
                } finally {
                    dl.disabled = false;
                    dl.innerHTML = label;
                }

                return;
            }

            if (rename) {

                const c = certs[Number(rename.dataset.rename)];

                const r = await Swal.fire({
                    title: "Name on the certificate",
                    input: "text",
                    inputValue: CertRenderer.properName(c.recipient_name),
                    showCancelButton: true,
                    confirmButtonText: "Save",
                    confirmButtonColor: "#234CA1",
                    inputValidator: v => (!v.trim() ? "Please enter a name" : null)
                });

                if (r.isConfirmed && await updateCertificate({
                        action: "rename",
                        certificate_id: c.certificate_id,
                        name: r.value.trim()
                    })) load();
            }

            if (revoke) {

                const c = certs[Number(revoke.dataset.revoke)];

                const r = await Swal.fire({
                    icon: "warning",
                    title: "Revoke this certificate?",
                    text: "The learner can no longer download it and the QR code will say it was revoked. You can restore it later.",
                    input: "text",
                    inputPlaceholder: "Reason (optional)",
                    showCancelButton: true,
                    confirmButtonText: "Revoke",
                    confirmButtonColor: "#D02027"
                });

                if (r.isConfirmed && await updateCertificate({
                        action: "revoke",
                        certificate_id: c.certificate_id,
                        reason: r.value || ""
                    })) load();
            }

            if (restore) {

                const c = certs[Number(restore.dataset.restore)];

                if (await updateCertificate({
                        action: "restore",
                        certificate_id: c.certificate_id
                    })) load();
            }

        });

        const CERT_STATUS = {
            valid: ["Valid", "bg-emerald-100 text-emerald-700"],
            expired: ["Expired", "bg-amber-100 text-amber-700"],
            revoked: ["Revoked", "bg-red-100 text-[#D02027]"]
        };

        async function updateCertificate(fields) {

            const fd = new FormData();
            Object.entries(fields).forEach(([k, v]) => fd.append(k, v));

            try {
                const res = await fetch("../php/certificates/update-certificate.php", {
                    method: "POST",
                    body: fd
                });
                const data = await res.json();

                if (data.status !== "success") {
                    Swal.fire({
                        icon: "error",
                        title: "Couldn't update",
                        text: data.message,
                        confirmButtonColor: "#234CA1"
                    });
                    return false;
                }

                return true;

            } catch (err) {
                console.error(err);
                return false;
            }

        }

        load();
    </script>
</body>

</html>