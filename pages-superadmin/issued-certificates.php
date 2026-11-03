<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(4);

$brandsResult = mysqli_query($conn, "SELECT brand_id, brand_name FROM brands ORDER BY brand_name ASC");
$allBrands = $brandsResult ? $brandsResult->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png" class="w-24">
    <title>UTH - Issued Certificates</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
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

        <div class="main-header">
            <span class="page-breadcrumbs">Issued Certificates</span>
            <?php include '../notification-bell.php'; ?>
        </div>

        <div class="main-container">
            <section class="relative overflow-hidden rounded-3xl bg-[#234CA1] p-5 md:p-8 text-white shadow-lg ">
                <div class="absolute -right-10 -top-10 w-60 h-60 rounded-full bg-blue-600/30 blur-3xl pointer-events-none"></div>
                <div class="hero-grid absolute inset-0 pointer-events-none"></div>
    
                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                    <div class="space-y-2 min-w-0">
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/10 text-xs font-semibold text-cyan-300">
                            <i class="fa-solid fa-award text-amber-400"></i> Certificate Register
                        </span>
                        <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight">Issued Certificates</h1>
                        <p class="text-slate-300 text-sm max-w-xl">Every certificate given to learners. Search, filter, download and export.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <button type="button" id="csvBtn" class="inline-flex items-center gap-2 px-4 h-11 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-sm font-eurostile-bold transition-colors"><i class="fa-solid fa-file-csv"></i> CSV</button>
                        <button type="button" id="zipBtn" class="inline-flex items-center gap-2 px-4 h-11 rounded-xl bg-white text-[#234CA1] hover:bg-gray-100 text-sm font-eurostile-bold transition-colors"><i class="fa-solid fa-file-zipper"></i> Download ZIP</button>
                    </div>
                </div>
            </section>
    
            <!-- FILTERS -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5 ">
                <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
                    <div class="col-span-2">
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Search</label>
                        <input type="search" id="f_q" placeholder="Name, training or certificate no." class="text-inputs">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Brand</label>
                        <select id="f_brand" class="text-inputs">
                            <option value="">All brands</option>
                            <?php foreach ($allBrands as $b): ?>
                                <option value="<?= $b['brand_id'] ?>"><?= htmlspecialchars($b['brand_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Type</label>
                        <select id="f_type" class="text-inputs">
                            <option value="">Trainings &amp; courses</option>
                            <option value="Training">Trainings</option>
                            <option value="Course">Courses</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Status</label>
                        <select id="f_status" class="text-inputs">
                            <option value="">Any</option>
                            <option value="valid">Valid</option>
                            <option value="expired">Expired</option>
                            <option value="revoked">Revoked</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">From</label>
                            <input type="date" id="f_from" class="text-inputs">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">To</label>
                            <input type="date" id="f_to" class="text-inputs">
                        </div>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row justify-end gap-3 mt-4">
                    <button type="button" id="resetBtn" class="bg-gray-100 text-gray-600 rounded-lg px-8 py-2.5 text-sm font-eurostile-bold">Reset</button>
                    <button type="button" id="applyBtn" class="bg-[#234CA1] text-white rounded-lg px-8 py-2.5 text-sm font-eurostile-bold">Apply Filters</button>
                </div>
            </div>
    
            <!-- TABLE -->
            <div class="min-w-0 bg-white rounded-2xl shadow-md border border-gray-200 p-6 ">
                <p id="meta" class="text-xs text-gray-400 mb-3"></p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-400">
                                <th class="py-3 px-3">Recipient</th>
                                <th class="py-3 px-3">Training / Course</th>
                                <th class="py-3 px-3">Brand</th>
                                <th class="py-3 px-3 text-center">Date</th>
                                <th class="py-3 px-3 text-center">Status</th>
                                <th class="py-3 px-3">Certificate no.</th>
                                <th class="py-3 px-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="certBody">
                            <tr>
                                <td colspan="7" class="text-center text-gray-400 py-10">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="pager" class="flex flex-col sm:flex-row justify-between items-center gap-3 mt-4"></div>
            </div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        lucide.createIcons();
        AOS.init({
            duration: 600,
            once: false
        });

        const PAGE_SIZE = 20;
        const ZIP_LIMIT = 50; // PDFs are built in the browser, so a ZIP is capped to keep it from running out of memory

        const el = id => document.getElementById(id);

        function esc(str) {
            const d = document.createElement("div");
            d.textContent = str ?? "";
            return d.innerHTML;
        }

        const fmtDate = s => s ? new Date(String(s).slice(0, 10) + "T00:00:00")
            .toLocaleDateString(undefined, { month: "short", day: "numeric", year: "numeric" }) : "—";

        const STATUS = {
            valid: ["Valid", "bg-emerald-100 text-emerald-700"],
            expired: ["Expired", "bg-amber-100 text-amber-700"],
            revoked: ["Revoked", "bg-red-100 text-[#D02027]"]
        };

        let rows = [];
        let truncated = false;
        let page = 1;

        function getParams() {

            const p = new URLSearchParams();
            const set = (k, id) => {
                if (el(id).value) p.append(k, el(id).value.trim());
            };

            set("q", "f_q");
            set("brand_id", "f_brand");
            set("type", "f_type");
            set("status", "f_status");
            set("date_from", "f_from");
            set("date_to", "f_to");

            return p;

        }

        async function load() {

            el("certBody").innerHTML = `<tr><td colspan="7" class="text-center text-gray-400 py-10"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading...</td></tr>`;

            try {

                const res = await fetch(`../php/certificates/get-issued-certificates.php?${getParams().toString()}`);
                const data = await res.json();

                if (data.status !== "success") {
                    el("certBody").innerHTML = `<tr><td colspan="7" class="text-center text-red-500 py-10">${esc(data.message)}</td></tr>`;
                    return;
                }

                rows = data.certificates;
                truncated = data.truncated;
                page = 1;
                await CertRenderer.setAssets(data.assets);
                render();

            } catch (err) {
                console.error(err);
                el("certBody").innerHTML = `<tr><td colspan="7" class="text-center text-red-500 py-10">Failed to load.</td></tr>`;
            }

        }

        function render() {

            const pages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
            page = Math.min(Math.max(page, 1), pages);

            const start = (page - 1) * PAGE_SIZE;
            const slice = rows.slice(start, start + PAGE_SIZE);

            el("meta").textContent = `${rows.length} certificate${rows.length === 1 ? "" : "s"}${truncated ? " (showing the latest 1,000, so narrow the filters to see older ones)" : ""}`;

            el("certBody").innerHTML = slice.length ? slice.map((c, i) => {

                const st = CertRenderer.certStatus(c);
                const chip = STATUS[st];

                return `
                    <tr class="border-b border-gray-100 ${st === "revoked" ? "bg-red-50/40" : ""}">
                        <td class="py-3 px-3"><span class="font-medium">${esc(CertRenderer.properName(c.recipient_name))}</span><br><span class="text-[11px] text-gray-400">${esc(c.dealership_name)}</span></td>
                        <td class="py-3 px-3">${esc(c.title)}<br><span class="text-[11px] text-gray-400">${c.source_type === "Course" ? "Course" : "Training"}</span></td>
                        <td class="py-3 px-3 text-xs text-gray-500">${esc(c.brand_name || "—")}</td>
                        <td class="py-3 px-3 text-center text-xs text-gray-500">${fmtDate(c.event_date)}${c.valid_until ? `<br><span class="text-gray-400">until ${fmtDate(c.valid_until)}</span>` : ""}</td>
                        <td class="py-3 px-3 text-center"><span class="text-xs font-bold px-2 py-1 rounded-full ${chip[1]}">${chip[0]}</span></td>
                        <td class="py-3 px-3 font-mono text-[11px] text-gray-400">${esc(c.certificate_no)}</td>
                        <td class="py-3 px-3 text-center whitespace-nowrap">
                            <button type="button" data-pdf="${start + i}" ${st === "revoked" ? "disabled" : ""} class="w-9 h-9 rounded-lg border border-gray-200 text-gray-500 hover:border-[#234CA1] hover:text-[#234CA1] disabled:opacity-40 disabled:cursor-not-allowed" title="Download PDF"><i class="fa-solid fa-file-pdf"></i></button>
                            <a href="learner-profile.php?user_id=${c.user_id}" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 text-gray-500 hover:border-[#234CA1] hover:text-[#234CA1]" title="Open profile"><i class="fa-solid fa-user"></i></a>
                        </td>
                    </tr>`;

            }).join("") : `<tr><td colspan="7" class="text-center text-gray-400 py-10">No certificates match these filters.</td></tr>`;

            el("pager").innerHTML = `
                <p class="text-xs text-gray-400">Showing ${rows.length ? start + 1 : 0}–${Math.min(start + PAGE_SIZE, rows.length)} of ${rows.length}</p>
                <div class="flex items-center gap-2">
                    <button type="button" id="prevBtn" ${page <= 1 ? "disabled" : ""} class="px-3 h-9 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"><i class="fa-solid fa-chevron-left"></i></button>
                    <span class="font-mono text-xs text-gray-500">${page} / ${pages}</span>
                    <button type="button" id="nextBtn" ${page >= pages ? "disabled" : ""} class="px-3 h-9 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"><i class="fa-solid fa-chevron-right"></i></button>
                </div>`;

            el("prevBtn").onclick = () => {
                page--;
                render();
            };
            el("nextBtn").onclick = () => {
                page++;
                render();
            };

        }

        el("certBody").addEventListener("click", async e => {

            const btn = e.target.closest("[data-pdf]");
            if (!btn) return;

            const label = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            try {
                await CertRenderer.downloadCert(rows[Number(btn.dataset.pdf)]);
            } catch (err) {
                console.error(err);
                Swal.fire({
                    icon: "error",
                    title: "Couldn't create the PDF",
                    confirmButtonColor: "#234CA1"
                });
            } finally {
                btn.disabled = false;
                btn.innerHTML = label;
            }

        });

        // ---------- CSV ----------
        el("csvBtn").addEventListener("click", () => {

            if (!rows.length) {
                Swal.fire({
                    icon: "info",
                    title: "Nothing to export",
                    confirmButtonColor: "#234CA1"
                });
                return;
            }

            // Quote every cell, and stop spreadsheets from running "=..." as a formula
            const cell = v => {
                let s = String(v ?? "");
                if (/^[=+\-@\t\r]/.test(s)) s = "'" + s;
                return `"${s.replace(/"/g, '""')}"`;
            };

            const lines = [
                ["Certificate No", "Name", "Training / Course", "Type", "Brand", "Dealership", "Date", "Valid Until", "Status", "Issued At"].map(cell).join(",")
            ];

            rows.forEach(c => lines.push([
                c.certificate_no,
                CertRenderer.properName(c.recipient_name),
                c.title,
                c.source_type,
                c.brand_name || "",
                c.dealership_name,
                c.event_date,
                c.valid_until || "",
                STATUS[CertRenderer.certStatus(c)][0],
                c.issued_at
            ].map(cell).join(",")));

            const blob = new Blob(["\ufeff" + lines.join("\n")], {
                type: "text/csv;charset=utf-8;"
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = `issued-certificates-${new Date().toISOString().slice(0, 10)}.csv`;
            a.click();
            URL.revokeObjectURL(url);

        });

        // ---------- ZIP of PDFs (filtered list, revoked ones skipped) ----------
        el("zipBtn").addEventListener("click", async () => {

            const list = rows.filter(c => !c.revoked_at).slice(0, ZIP_LIMIT);

            if (!list.length) {
                Swal.fire({
                    icon: "info",
                    title: "Nothing to download",
                    text: "There are no active certificates for these filters.",
                    confirmButtonColor: "#234CA1"
                });
                return;
            }

            Swal.fire({
                title: "Preparing certificates",
                html: `<p id="zipProgress" class="text-gray-500">0 / ${list.length}</p><p class="text-xs text-gray-400 mt-2">Please keep this tab open.</p>`,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            try {

                const zip = new JSZip();

                for (let i = 0; i < list.length; i++) {
                    const c = list[i];
                    const name = CertRenderer.safeName(`${c.certificate_no} - ${CertRenderer.properName(c.recipient_name)} - ${c.title}.pdf`);
                    zip.file(name, await CertRenderer.pdfBufferForCert(c));
                    el("zipProgress").textContent = `${i + 1} / ${list.length}`;
                }

                const blob = await zip.generateAsync({
                    type: "blob"
                });
                const url = URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = `certificates-${new Date().toISOString().slice(0, 10)}.zip`;
                a.click();
                URL.revokeObjectURL(url);

                Swal.close();

                if (rows.filter(c => !c.revoked_at).length > ZIP_LIMIT) {
                    Swal.fire({
                        icon: "info",
                        title: `First ${ZIP_LIMIT} downloaded`,
                        text: "Narrow the filters (for example by date or brand) to download the rest in more ZIP files.",
                        confirmButtonColor: "#234CA1"
                    });
                }

            } catch (err) {
                console.error(err);
                Swal.fire({
                    icon: "error",
                    title: "Couldn't build the ZIP",
                    text: "Try a smaller selection.",
                    confirmButtonColor: "#234CA1"
                });
            }

        });

        el("applyBtn").addEventListener("click", load);
        el("f_q").addEventListener("keydown", e => {
            if (e.key === "Enter") load();
        });

        el("resetBtn").addEventListener("click", () => {
            ["f_q", "f_brand", "f_type", "f_status", "f_from", "f_to"].forEach(id => el(id).value = "");
            load();
        });

        load();
    </script>
</body>

</html>