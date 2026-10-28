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

        #editorCanvas {
            touch-action: none;
            cursor: crosshair;
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-800 antialiased h-auto">
    <?php include('../sidebar-superadmin.php') ?>
    <main>

        <div class="flex justify-between items-center w-full">
            <span class="page-breadcrumbs">Certificates</span>
            <?php include '../notification-bell.php'; ?>
        </div>

        <section class="relative overflow-hidden rounded-3xl bg-[#234CA1] p-5 md:p-8 text-white shadow-lg mt-5">
            <div class="absolute -right-10 -top-10 w-60 h-60 rounded-full bg-blue-600/30 blur-3xl pointer-events-none"></div>
            <div class="absolute right-1/3 -bottom-10 w-60 h-60 rounded-full bg-cyan-500/20 blur-3xl pointer-events-none"></div>
            <div class="hero-grid absolute inset-0 pointer-events-none"></div>

            <div class="relative z-10 min-w-0 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="space-y-3 min-w-0">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/10 text-xs font-semibold text-cyan-300">
                        <i class="fa-solid fa-certificate text-amber-400"></i> Certificate Module
                    </span>
                    <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight">Certificates</h1>
                    <p class="text-slate-300 text-sm max-w-2xl">Design the certificates, add each brand's logo and the signatories, and choose the fonts. When a learner finishes a training and times out, the certificate is filled in automatically.</p>
                </div>
                <div class="flex flex-wrap justify-end gap-2 shrink-0">
                    <a href="../verify-certificate.php" class="inline-flex items-center gap-2 px-4 h-11 rounded-xl bg-white text-[#234CA1] hover:bg-gray-100 text-sm font-eurostile-bold transition-colors">
                        <i data-lucide="sticky-note-check" class="w-4 h-4"></i> Verify Certificate
                    </a>
                </div>
            </div>
        </section>

        <!-- TABS -->
        <div id="tabs" class="flex flex-wrap gap-2 mt-5">
            <button type="button" data-tab="templates" class="tab-btn px-4 h-10 rounded-full border text-sm font-semibold transition-colors"><i class="fa-solid fa-file-lines mr-2"></i>Templates</button>
            <button type="button" data-tab="logos" class="tab-btn px-4 h-10 rounded-full border text-sm font-semibold transition-colors"><i class="fa-solid fa-image mr-2"></i>Brand Logos</button>
            <button type="button" data-tab="signatories" class="tab-btn px-4 h-10 rounded-full border text-sm font-semibold transition-colors"><i class="fa-solid fa-signature mr-2"></i>Signatories</button>
            <button type="button" data-tab="fonts" class="tab-btn px-4 h-10 rounded-full border text-sm font-semibold transition-colors"><i class="fa-solid fa-font mr-2"></i>Fonts</button>
        </div>

        <!-- TEMPLATES -->
        <section id="tab-templates" class="tab-panel mt-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <p class="text-sm text-gray-500">A schedule uses the template you pick for it. If it picks none, the brand's default template is used.</p>
                <button type="button" id="newTemplateBtn" class="shrink-0 h-11 px-6 rounded-xl bg-[#234CA1] hover:bg-[#193B82] text-white text-sm font-eurostile-bold transition-colors"><i class="fa-solid fa-plus mr-2"></i>New Template</button>
            </div>
            <div id="templateGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5"></div>
        </section>

        <!-- LOGOS -->
        <section id="tab-logos" class="tab-panel hidden mt-5">
            <p class="text-sm text-gray-500 mb-4 max-w-3xl">Add as many logos as you need for each brand (for example an old and a new one). The one marked <b>Main</b> is used on that brand's certificates. A template can also show any logo, including the <b>General logos</b> group, with its "Extra logo" element. A PNG with a transparent background looks best.</p>
            <div id="logoGrid" class="grid grid-cols-1 lg:grid-cols-2 gap-5"></div>
        </section>

        <!-- SIGNATORIES -->
        <section id="tab-signatories" class="tab-panel hidden mt-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <p class="text-sm text-gray-500">Add each person once, then choose them in the template's "Signatures" element. Use a PNG of the signature with a transparent background.</p>
                <button type="button" id="newSignatoryBtn" class="shrink-0 h-11 px-6 rounded-xl bg-[#234CA1] hover:bg-[#193B82] text-white text-sm font-eurostile-bold transition-colors"><i class="fa-solid fa-plus mr-2"></i>Add Signatory</button>
            </div>
            <div id="signatoryGrid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5"></div>
        </section>

        <!-- FONTS -->
        <section id="tab-fonts" class="tab-panel hidden mt-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <p class="text-sm text-gray-500 max-w-3xl">Upload fonts from your computer (.ttf, .otf, .woff, .woff2). They're stored on the server, so every learner's certificate uses them, not only your PC. Only upload fonts you're licensed to use.</p>
                <button type="button" id="newFontBtn" class="shrink-0 h-11 px-6 rounded-xl bg-[#234CA1] hover:bg-[#193B82] text-white text-sm font-eurostile-bold transition-colors"><i class="fa-solid fa-upload mr-2"></i>Upload Font</button>
            </div>
            <div id="fontGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5"></div>
        </section>

        <!-- EDITOR -->
        <div id="editorOverlay" class="hidden fixed inset-0 z-50 bg-black/50 p-3 sm:p-6 overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-xl max-w-6xl mx-auto p-4 sm:p-6">

                <div class="flex items-start justify-between gap-3">
                    <h2 id="e_title" class="text-xl sm:text-2xl font-eurostile-bold text-[#234CA1]">Template</h2>
                    <button type="button" id="editorClose" class="w-9 h-9 rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-600 shrink-0" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_360px] gap-5 mt-4">

                    <div class="min-w-0">
                        <div class="bg-slate-100 rounded-xl p-3">
                            <canvas id="editorCanvas" class="w-full h-auto rounded-md shadow bg-white"></canvas>
                        </div>
                        <p class="text-xs text-gray-400 mt-2"><i class="fa-solid fa-hand-pointer mr-1"></i>Click and drag on the preview to move the nearest element. The brand logo and signatures show once you've added them in their tabs.</p>

                        <div class="rounded-xl bg-slate-50 border border-gray-200 p-3 mt-3 grid grid-cols-1 sm:grid-cols-4 gap-2">
                            <p class="sm:col-span-4 text-xs font-bold text-gray-500 uppercase">Preview text (not saved)</p>
                            <input type="text" id="s_name" class="text-inputs sm:col-span-2" placeholder="Name">
                            <input type="text" id="s_title" class="text-inputs sm:col-span-2" placeholder="Training title">
                            <input type="date" id="s_date" class="text-inputs sm:col-span-2">
                            <select id="s_brand" class="text-inputs sm:col-span-2"></select>
                        </div>
                    </div>

                    <div class="space-y-4 min-w-0">

                        <div class="grid grid-cols-1 gap-3">
                            <div>
                                <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Template name</label>
                                <input type="text" id="t_name" maxlength="120" class="text-inputs">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">For brand</label>
                                    <select id="t_brand" class="text-inputs"></select>
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Valid for (months)</label>
                                    <input type="number" id="t_valid" min="1" max="120" placeholder="No expiry" class="text-inputs">
                                </div>
                            </div>
                            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer"><input type="checkbox" id="t_default"> Use as the default for this brand</label>
                            <div>
                                <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Background image <span class="font-normal normal-case text-gray-400">(JPG/PNG, 1200 px+ wide)</span></label>
                                <div class="input-wrapper">
                                    <input type="file" id="t_file" accept="image/png,image/jpeg" class="text-inputs">
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1">Your design with the heading, curves and the UAAGI logo already on it. The name, body text, brand logo and signatures are placed on top.</p>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs font-bold text-[#234CA1] uppercase mb-2">Element</p>
                            <div id="elementTabs" class="flex flex-wrap gap-2"></div>
                        </div>

                        <div id="controls" class="rounded-xl border border-gray-200 p-3 space-y-3"></div>

                        <label id="t_restyle_wrap" class="hidden flex items-start gap-2 text-sm text-gray-600 cursor-pointer">
                            <input type="checkbox" id="t_restyle" class="mt-1">
                            <span>Also restyle certificates already issued with this template
                                <span class="block text-xs text-gray-400">Leave this off to keep old certificates exactly as they were issued.</span></span>
                        </label>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-end gap-3 mt-5 pt-4 border-t border-gray-100">
                    <button type="button" id="editorCancel" class="h-12 px-8 rounded-xl bg-gray-100 text-gray-600 font-eurostile-bold">Cancel</button>
                    <button type="button" id="editorSave" class="h-12 px-8 rounded-xl bg-[#234CA1] text-white font-eurostile-bold hover:bg-[#193B82] transition-colors">Save Template</button>
                </div>
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

        const el = id => document.getElementById(id);

        function esc(str) {
            const d = document.createElement("div");
            d.textContent = str ?? "";
            return d.innerHTML;
        }

        const escAttr = s => esc(s).replace(/"/g, "&quot;");
        const todayIso = () => new Date().toISOString().slice(0, 10);

        const state = {
            brands: [],
            assets: null,
            tab: "templates"
        };

        const ELEMENT_LABELS = {
            name: "Name",
            body: "Body text",
            brandLogo: "Brand logo",
            signatures: "Signatures",
            qr: "QR code",
            number: "Certificate no.",
            brandLogo: "Brand logo",
            extraLogo: "Extra logo"
        };

        const PLACEHOLDERS = ["{title}", "{name}", "{day}", "{ordinal}", "{month}", "{Month}", "{year}", "{date}", "{brand}", "{valid_until}"];

        const brandName = id => (state.brands.find(b => b.brand_id === Number(id)) || {}).brand_name || "All brands";
        const activeTemplates = () => Object.values(state.assets.templates).filter(t => !t.archived);

        // ==========================================
        // LOAD + TABS
        // ==========================================
        async function loadAll() {

            try {

                const res = await fetch("../php/certificates/get-certificate-setup.php");
                const data = await res.json();

                if (data.status !== "success") {
                    Swal.fire({
                        icon: "error",
                        title: "Couldn't load",
                        text: data.message,
                        confirmButtonColor: "#234CA1"
                    });
                    return;
                }

                state.brands = data.brands;
                state.assets = data.assets;
                await CertRenderer.setAssets(data.assets);

                renderTemplates();
                renderLogos();
                renderSignatories();
                renderFonts();

            } catch (err) {
                console.error(err);
            }

        }

        function showTab(tab) {

            state.tab = tab;

            document.querySelectorAll(".tab-panel").forEach(p => p.classList.toggle("hidden", p.id !== `tab-${tab}`));
            document.querySelectorAll(".tab-btn").forEach(b => {
                const on = b.dataset.tab === tab;
                b.classList.toggle("bg-[#234CA1]", on);
                b.classList.toggle("text-white", on);
                b.classList.toggle("border-[#234CA1]", on);
                b.classList.toggle("bg-white", !on);
                b.classList.toggle("text-gray-600", !on);
                b.classList.toggle("border-gray-200", !on);
            });

        }

        el("tabs").addEventListener("click", e => {
            const b = e.target.closest("[data-tab]");
            if (b) showTab(b.dataset.tab);
        });

        // A brand to draw the preview with: the template's own, else the first one that has a logo
        function previewBrandFor(tpl) {
            if (tpl && tpl.brand_id) return tpl.brand_id;
            const logos = Object.values(state.assets.logos);
            const withLogo = state.brands.find(b => logos.some(l => Number(l.brand_id) === b.brand_id));
            return withLogo ? withLogo.brand_id : 0;
        }

        const sampleFor = (brandId) => ({
            name: "Juan D. Dela Cruz",
            title: "Product Knowledge Training",
            date: todayIso(),
            number: "UAAGI-2026-9F3A12BC",
            brandId: Number(brandId) || 0,
            brandName: brandName(brandId)
        });

        // ==========================================
        // A small form modal used by logos / signatories / fonts
        // ==========================================
        function formModal({
            title,
            fields,
            submitLabel = "Save",
            onSubmit
        }) {

            Swal.fire({
                html: `
                    <div class="flex flex-col gap-y-3 text-left p-5 w-full max-h-[85vh] overflow-y-auto">
                        <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase">${esc(title)}</h2>
                        ${fields.map(f => `
                            <div>
                                <label class="text-sm font-bold text-[#234CA1] block mb-1">${esc(f.label)}${f.hint ? ` <span class="font-normal text-gray-400">${esc(f.hint)}</span>` : ""}</label>
                                <input id="fm_${f.id}" type="${f.type || "text"}" ${f.accept ? `accept="${f.accept}"` : ""} value="${escAttr(f.value ?? "")}" maxlength="120" class="text-inputs">
                            </div>`).join("")}
                        <p id="fm_error" class="hidden text-sm text-[#D02027]"></p>
                        <div class="flex flex-col sm:flex-row gap-3 mt-1">
                            <button type="button" id="fm_cancel" class="flex-1 h-12 bg-gray-100 text-gray-500 rounded-xl font-eurostile-bold">Cancel</button>
                            <button type="button" id="fm_submit" class="flex-1 h-12 bg-[#234CA1] text-white rounded-xl font-eurostile-bold">${esc(submitLabel)}</button>
                        </div>
                    </div>`,
                customClass: {
                    popup: "my-popup popup-blue",
                    htmlContainer: "!p-0 !m-0"
                },
                showConfirmButton: false,
                heightAuto: false,
                width: 460,
                didOpen: () => {

                    el("fm_cancel").onclick = () => Swal.close();

                    el("fm_submit").onclick = async () => {

                        const fd = new FormData();

                        fields.forEach(f => {
                            const input = el(`fm_${f.id}`);
                            if (f.type === "file") {
                                if (input.files[0]) fd.append(f.id, input.files[0]);
                            } else fd.append(f.id, input.value.trim());
                        });

                        const btn = el("fm_submit");
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Saving...';

                        const result = await onSubmit(fd);

                        if (result === true) {
                            Swal.close();
                            loadAll();
                        } else {
                            el("fm_error").textContent = result || "Something went wrong.";
                            el("fm_error").classList.remove("hidden");
                            btn.disabled = false;
                            btn.textContent = submitLabel;
                        }

                    };
                }
            });

        }

        async function postAssets(fd) {

            try {
                const res = await fetch("../php/certificates/manage-certificate-assets.php", {
                    method: "POST",
                    body: fd
                });
                const data = await res.json();
                return data.status === "success" ? true : data.message;
            } catch (err) {
                console.error(err);
                return "Something went wrong. Please try again.";
            }

        }

        async function confirmDanger(title, text, confirmText = "Remove") {
            const r = await Swal.fire({
                icon: "warning",
                title,
                text,
                showCancelButton: true,
                confirmButtonText: confirmText,
                confirmButtonColor: "#D02027"
            });
            return r.isConfirmed;
        }

        // ==========================================
        // TEMPLATES TAB
        // ==========================================
        async function renderTemplates() {

            const list = activeTemplates().sort((a, b) => a.name.localeCompare(b.name));

            if (!list.length) {
                el("templateGrid").innerHTML = `
                    <div class="col-span-full bg-white rounded-2xl border border-gray-200 shadow-md p-10 text-center">
                        <h3 class="font-eurostile-bold text-[#234CA1] text-lg">No templates yet</h3>
                        <p class="text-sm text-gray-500 mt-1">Until you add one, learners get a plain built-in certificate. Click <b>New Template</b> and upload your design.</p>
                    </div>`;
                return;
            }

            el("templateGrid").innerHTML = list.map(t => `
                <div class="bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden flex flex-col min-w-0">
                    <div class="bg-slate-100 p-3"><canvas data-tpl="${t.template_id}" class="w-full h-auto rounded-md shadow-sm bg-white"></canvas></div>
                    <div class="p-4 flex flex-col gap-y-3 flex-1">
                        <div>
                            <h3 class="font-eurostile-bold text-[#234CA1] break-words">${esc(t.name)}</h3>
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-blue-100 text-blue-700">${t.brand_id ? esc(brandName(t.brand_id)) : "All brands"}</span>
                                ${t.is_default ? '<span class="text-[10px] font-bold px-2 py-1 rounded-full bg-emerald-100 text-emerald-700">Default</span>' : ""}
                                ${t.valid_months ? `<span class="text-[10px] font-bold px-2 py-1 rounded-full bg-amber-100 text-amber-700">Valid ${t.valid_months} mo</span>` : ""}
                            </div>
                        </div>
                        <div class="flex gap-2 mt-auto">
                            <button type="button" data-edit="${t.template_id}" class="flex-1 h-10 rounded-lg bg-[#234CA1] hover:bg-[#193B82] text-white text-sm font-eurostile-bold transition-colors">Edit</button>
                            <button type="button" data-dup="${t.template_id}" class="px-3 h-10 rounded-lg border border-gray-200 text-gray-600 hover:border-[#234CA1] hover:text-[#234CA1] text-sm font-semibold" title="Duplicate"><i class="fa-regular fa-copy"></i></button>
                            <button type="button" data-archive="${t.template_id}" class="px-3 h-10 rounded-lg border border-gray-200 text-gray-400 hover:text-[#D02027] text-sm font-semibold" title="Remove"><i class="fa-solid fa-trash-can"></i></button>
                        </div>
                    </div>
                </div>`).join("");

            for (const canvas of document.querySelectorAll("#templateGrid canvas")) {
                const t = state.assets.templates[canvas.dataset.tpl];
                try {
                    await CertRenderer.render(canvas, t, sampleFor(previewBrandFor(t)), {
                        width: 900
                    });
                } catch (err) {
                    console.error(err);
                }
            }

        }

        el("templateGrid").addEventListener("click", async e => {

            const edit = e.target.closest("[data-edit]");
            const dup = e.target.closest("[data-dup]");
            const arch = e.target.closest("[data-archive]");

            if (edit) openEditor(state.assets.templates[edit.dataset.edit], false);
            if (dup) openEditor(state.assets.templates[dup.dataset.dup], true);

            if (arch && await confirmDanger("Remove this template?", "Certificates already issued with it keep their design. Schedules using it go back to the brand's default.")) {

                const fd = new FormData();
                fd.append("action", "archive");
                fd.append("template_id", arch.dataset.archive);

                const res = await fetch("../php/certificates/save-certificate-template.php", {
                    method: "POST",
                    body: fd
                });
                const data = await res.json();

                if (data.status === "success") loadAll();
                else Swal.fire({
                    icon: "error",
                    title: "Couldn't remove",
                    text: data.message,
                    confirmButtonColor: "#234CA1"
                });
            }

        });

        el("newTemplateBtn").addEventListener("click", () => openEditor(null, false));

        // ==========================================
        // LOGOS TAB
        // ==========================================
        function renderLogos() {

            const groups = [{
                id: 0,
                name: "General logos (not tied to a brand)"
            }, ...state.brands.map(b => ({
                id: b.brand_id,
                name: b.brand_name
            }))];

            const all = Object.values(state.assets.logos);

            el("logoGrid").innerHTML = groups.map(g => {

                const mine = all.filter(l => Number(l.brand_id) === g.id)
                    .sort((a, b) => b.is_primary - a.is_primary || a.logo_id - b.logo_id);

                return `
                    <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-4 flex flex-col gap-y-3 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="font-eurostile-bold text-[#234CA1] break-words min-w-0">${esc(g.name)}</h3>
                            <button type="button" data-addlogo="${g.id}" class="shrink-0 px-3 h-9 rounded-lg bg-[#234CA1] hover:bg-[#193B82] text-white text-xs font-eurostile-bold transition-colors"><i class="fa-solid fa-plus mr-1"></i>Add logo</button>
                        </div>
                        ${mine.length ? `
                            <div class="grid grid-cols-2 gap-3">
                                ${mine.map(l => `
                                    <div class="rounded-xl border border-gray-200 p-2 flex flex-col gap-y-2 min-w-0">
                                        <div class="h-20 flex items-center justify-center bg-slate-50 rounded-lg p-2">
                                            <img src="../${escAttr(l.logo_path)}" alt="" class="max-h-full max-w-full object-contain">
                                        </div>
                                        <p class="text-xs font-semibold text-gray-600 truncate" title="${escAttr(l.label)}">${esc(l.label || "Logo")}</p>
                                        <div class="flex items-center gap-1">
                                            ${l.is_primary ?
                                                '<span class="text-[10px] font-bold px-2 py-1 rounded-full bg-emerald-100 text-emerald-700">Main</span>' :
                                                `<button type="button" data-primary="${l.logo_id}" class="text-[10px] font-bold px-2 py-1 rounded-full border border-gray-200 text-gray-500 hover:border-[#234CA1] hover:text-[#234CA1]">Make main</button>`}
                                            <button type="button" data-editlogo="${l.logo_id}" class="ml-auto w-7 h-7 rounded-md text-gray-400 hover:text-[#234CA1]" title="Edit"><i class="fa-solid fa-pen text-xs"></i></button>
                                            <button type="button" data-dellogo="${l.logo_id}" class="w-7 h-7 rounded-md text-gray-400 hover:text-[#D02027]" title="Delete"><i class="fa-solid fa-trash-can text-xs"></i></button>
                                        </div>
                                    </div>`).join("")}
                            </div>` : '<p class="text-xs text-gray-400">No logos yet.</p>'}
                    </div>`;

            }).join("");

        }

        function openLogoForm(logo, brandId = 0) {

            formModal({
                title: logo ? "Edit Logo" : `Add Logo: ${brandId ? brandName(brandId) : "General"}`,
                fields: [{
                        id: "label",
                        label: "Label",
                        hint: "(e.g. Main logo, 2026 logo)",
                        value: logo ? logo.label : ""
                    },
                    {
                        id: "logo",
                        label: "Logo image",
                        type: "file",
                        accept: "image/png,image/jpeg,image/webp",
                        hint: logo ? "(leave empty to keep the current one)" : "(PNG with a transparent background is best)"
                    }
                ],
                submitLabel: logo ? "Save" : "Upload",
                onSubmit: fd => {
                    fd.append("action", "save_logo");
                    fd.append("logo_id", logo ? logo.logo_id : 0);
                    fd.append("brand_id", logo ? logo.brand_id : brandId);
                    return postAssets(fd);
                }
            });

        }

        el("logoGrid").addEventListener("click", async e => {

            const add = e.target.closest("[data-addlogo]");
            const edit = e.target.closest("[data-editlogo]");
            const pri = e.target.closest("[data-primary]");
            const del = e.target.closest("[data-dellogo]");

            if (add) openLogoForm(null, Number(add.dataset.addlogo));
            if (edit) openLogoForm(state.assets.logos[edit.dataset.editlogo]);

            if (pri) {
                const fd = new FormData();
                fd.append("action", "set_primary_logo");
                fd.append("logo_id", pri.dataset.primary);
                if (await postAssets(fd) === true) loadAll();
            }

            if (del && await confirmDanger("Delete this logo?", "Certificates that use it will be drawn without it (or with the brand's main logo, if the element is on Automatic).", "Delete")) {
                const fd = new FormData();
                fd.append("action", "delete_logo");
                fd.append("logo_id", del.dataset.dellogo);
                if (await postAssets(fd) === true) loadAll();
            }

        });

        // ==========================================
        // SIGNATORIES TAB
        // ==========================================
        function renderSignatories() {

            const list = Object.values(state.assets.signatories);

            el("signatoryGrid").innerHTML = list.length ? list.map(s => `
                <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-4 flex flex-col gap-y-3 min-w-0">
                    <div class="h-24 rounded-xl bg-white border border-dashed border-gray-200 flex items-center justify-center p-2">
                        ${s.signature_path ? `<img src="../${escAttr(s.signature_path)}" alt="" class="max-h-full max-w-full object-contain">` : '<span class="text-xs text-gray-400">No signature image</span>'}
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-eurostile-bold text-[#234CA1] break-words">${esc(s.name)}</h3>
                        <p class="text-sm text-gray-500 break-words">${esc(s.position) || "—"}</p>
                    </div>
                    <div class="flex gap-2 mt-auto">
                        <button type="button" data-sig="${s.signatory_id}" class="flex-1 h-10 rounded-lg bg-[#234CA1] hover:bg-[#193B82] text-white text-sm font-eurostile-bold transition-colors">Edit</button>
                        <button type="button" data-delsig="${s.signatory_id}" class="px-3 h-10 rounded-lg border border-gray-200 text-gray-400 hover:text-[#D02027]" title="Delete"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                </div>`).join("") :
                `<p class="col-span-full text-center text-gray-400 py-10">No signatories yet.</p>`;

        }

        function openSignatoryForm(s = null) {

            formModal({
                title: s ? "Edit Signatory" : "Add Signatory",
                fields: [{
                        id: "name",
                        label: "Full name",
                        value: s ? s.name : ""
                    },
                    {
                        id: "position",
                        label: "Position",
                        value: s ? s.position : ""
                    },
                    {
                        id: "signature",
                        label: "Signature image",
                        type: "file",
                        accept: "image/png,image/jpeg,image/webp",
                        hint: s ? "(leave empty to keep the current one)" : "(PNG, transparent background)"
                    }
                ],
                onSubmit: fd => {
                    fd.append("action", "save_signatory");
                    fd.append("signatory_id", s ? s.signatory_id : 0);
                    return postAssets(fd);
                }
            });

        }

        el("newSignatoryBtn").addEventListener("click", () => openSignatoryForm());

        el("signatoryGrid").addEventListener("click", async e => {

            const edit = e.target.closest("[data-sig]");
            const del = e.target.closest("[data-delsig]");

            if (edit) openSignatoryForm(state.assets.signatories[edit.dataset.sig]);

            if (del && await confirmDanger("Delete this signatory?", "Templates that use them will show an empty signature slot.", "Delete")) {
                const fd = new FormData();
                fd.append("action", "delete_signatory");
                fd.append("signatory_id", del.dataset.delsig);
                if (await postAssets(fd) === true) loadAll();
            }

        });

        // ==========================================
        // FONTS TAB
        // ==========================================
        function renderFonts() {

            const fonts = state.assets.fonts;

            el("fontGrid").innerHTML = fonts.length ? fonts.map(f => `
                <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-4 flex flex-col gap-y-3 min-w-0">
                    <p class="text-3xl break-words" style="font-family: 'CertFont_${f.font_id}', Georgia, serif">Hanz Kerby Dacucos</p>
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="font-eurostile-bold text-[#234CA1] break-words min-w-0">${esc(f.family)}</h3>
                        <button type="button" data-delfont="${f.font_id}" class="px-3 h-9 rounded-lg border border-gray-200 text-gray-400 hover:text-[#D02027] shrink-0" title="Delete"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                </div>`).join("") :
                `<p class="col-span-full text-center text-gray-400 py-10">No uploaded fonts yet. The built-in fonts (Great Vibes, EB Garamond, Cormorant Garamond and more) are always available.</p>`;

        }

        el("newFontBtn").addEventListener("click", () => {
            formModal({
                title: "Upload Font",
                fields: [{
                        id: "family",
                        label: "Font name",
                        hint: "(how it appears in the dropdowns)"
                    },
                    {
                        id: "font",
                        label: "Font file",
                        type: "file",
                        accept: ".ttf,.otf,.woff,.woff2"
                    }
                ],
                submitLabel: "Upload",
                onSubmit: fd => {
                    fd.append("action", "save_font");
                    return postAssets(fd);
                }
            });
        });

        el("fontGrid").addEventListener("click", async e => {

            const del = e.target.closest("[data-delfont]");

            if (del && await confirmDanger("Delete this font?", "Templates that use it fall back to Georgia.", "Delete")) {
                const fd = new FormData();
                fd.append("action", "delete_font");
                fd.append("font_id", del.dataset.delfont);
                if (await postAssets(fd) === true) loadAll();
            }

        });

        // ==========================================
        // EDITOR
        // ==========================================
        const editor = {
            id: 0,
            copyFrom: 0,
            layout: null,
            bgUrl: null,
            bgFile: null,
            selected: "name"
        };

        function openEditor(template, duplicate) {

            editor.id = template && !duplicate ? template.template_id : 0;
            editor.copyFrom = template && duplicate ? template.template_id : 0;
            editor.layout = CertRenderer.mergeLayout(template ? template.layout : null);
            editor.bgFile = null;
            editor.bgUrl = template && template.background_path ? `../${template.background_path}` : null;
            editor.selected = "name";

            el("e_title").textContent = editor.id ? "Edit Template" : (duplicate ? "Duplicate Template" : "New Template");
            el("t_name").value = template ? (duplicate ? `${template.name} (copy)` : template.name) : "";
            el("t_valid").value = template && template.valid_months ? template.valid_months : "";
            el("t_default").checked = !!(template && !duplicate && template.is_default);
            el("t_file").value = "";
            el("t_restyle").checked = false;
            el("t_restyle_wrap").classList.toggle("hidden", !editor.id);

            el("t_brand").innerHTML = `<option value="0">All brands</option>` +
                state.brands.map(b => `<option value="${b.brand_id}">${esc(b.brand_name)}</option>`).join("");
            el("t_brand").value = template ? template.brand_id : 0;

            el("s_brand").innerHTML = `<option value="0">(no brand logo)</option>` +
                state.brands.map(b => `<option value="${b.brand_id}">Preview as ${esc(b.brand_name)}</option>`).join("");
            el("s_brand").value = previewBrandFor(template);

            el("s_name").value = "Juan D. Dela Cruz";
            el("s_title").value = "Product Knowledge Training";
            el("s_date").value = todayIso();

            renderElementTabs();
            renderControls();

            el("editorOverlay").classList.remove("hidden");
            document.body.classList.add("overflow-hidden");

            redraw();

        }

        function closeEditor() {
            el("editorOverlay").classList.add("hidden");
            document.body.classList.remove("overflow-hidden");
        }

        function renderElementTabs() {

            el("elementTabs").innerHTML = Object.entries(ELEMENT_LABELS).map(([key, label]) => `
                <button type="button" data-el="${key}"
                    class="px-3 py-1.5 rounded-full border text-xs font-semibold transition-colors ${key === editor.selected ? "bg-[#234CA1] border-[#234CA1] text-white" : "bg-white border-gray-200 text-gray-600 hover:border-[#234CA1]"}">
                    ${label}
                </button>`).join("");

        }

        el("elementTabs").addEventListener("click", e => {
            const b = e.target.closest("[data-el]");
            if (!b) return;
            editor.selected = b.dataset.el;
            renderElementTabs();
            renderControls();
        });

        function renderControls() {

            const key = editor.selected;
            const E = editor.layout.elements[key];

            const num = (k, label, min, max, step) => `
                <div><label class="text-xs font-bold text-gray-500 block mb-1">${label}</label>
                <input type="number" data-k="${k}" min="${min}" max="${max}" step="${step}" value="${E[k]}" class="text-inputs"></div>`;

            const sel = (k, label, options) => `
                <div><label class="text-xs font-bold text-gray-500 block mb-1">${label}</label>
                <select data-k="${k}" class="text-inputs">${options.map(([v, l]) => `<option value="${escAttr(v)}" ${String(E[k]) === String(v) ? "selected" : ""}>${esc(l)}</option>`).join("")}</select></div>`;

            const color = (k, label) => `
                <div><label class="text-xs font-bold text-gray-500 block mb-1">${label}</label>
                <input type="color" data-k="${k}" value="${E[k]}" class="h-10 w-full p-1 rounded-lg border border-gray-200 bg-white cursor-pointer"></div>`;

            const grid = (...items) => `<div class="grid grid-cols-2 gap-3">${items.join("")}</div>`;

            const visible = `<label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer"><input type="checkbox" data-k="visible" ${E.visible ? "checked" : ""}> Show this element</label>`;

            const fonts = CertRenderer.fontOptions();
            const weights = [
                ["normal", "Normal"],
                ["bold", "Bold"]
            ];
            const aligns = [
                ["left", "Left"],
                ["center", "Center"],
                ["right", "Right"]
            ];

            let html = visible;

            if (key === "name" || key === "number") {
                html += sel("font", "Font", fonts) +
                    grid(sel("weight", "Weight", weights), sel("align", "Align", aligns),
                        num("size", "Size (% of width)", 0.5, 15, 0.1), num("maxWidth", "Max width (%)", 10, 100, 1),
                        num("x", "X position (%)", 0, 100, 0.5), num("y", "Y position (%)", 0, 100, 0.5)) +
                    color("color", "Colour");
            }

            if (key === "body") {
                html += `
                    <div>
                        <label class="text-xs font-bold text-gray-500 block mb-1">Sentence</label>
                        <textarea id="c_text" data-k="text" rows="5" maxlength="600" class="text-inputs p-3 w-full text-sm">${esc(E.text)}</textarea>
                        <p class="text-[11px] text-gray-400 mt-1">Use <b>**bold**</b> and <b>^^small raised text^^</b>. Click a placeholder to insert it:</p>
                        <div class="flex flex-wrap gap-1.5 mt-2">${PLACEHOLDERS.map(p => `<button type="button" data-insert="${p}" class="px-2 py-1 rounded-md bg-slate-100 hover:bg-slate-200 text-[11px] font-mono text-slate-600">${p}</button>`).join("")}</div>
                    </div>` +
                    sel("font", "Font", fonts) +
                    grid(sel("align", "Align", aligns), num("lineHeight", "Line spacing", 1, 3, 0.05),
                        num("size", "Size (% of width)", 0.5, 15, 0.05), num("maxWidth", "Line width (%)", 10, 100, 1),
                        num("x", "X position (%)", 0, 100, 0.5), num("y", "Y position (%)", 0, 100, 0.5)) +
                    color("color", "Colour");
            }

            if (key === "brandLogo" || key === "extraLogo") {
                const logoOptions = [
                    [0, key === "brandLogo" ? "Automatic (the brand's main logo)" : "(none)"],
                    ...Object.values(state.assets.logos).map(l => [l.logo_id, `${l.brand_id ? brandName(l.brand_id) : "General"}: ${l.label || "Logo"}`])
                ];

                html += sel("logoId", "Logo", logoOptions) +
                    grid(num("x", "X position (%)", 0, 100, 0.5), num("y", "Y position (%)", 0, 100, 0.5), num("width", "Width (% of certificate)", 3, 60, 0.5)) +
                    `<p class="text-xs text-gray-400">${key === "brandLogo" ? "Shows the logo of the certificate's brand. Pick a specific one to always use it." : "A second logo, such as a partner or an older brand logo. Turn it on above and choose which one."}</p>`;
            }

            if (key === "qr") {
                html += grid(num("x", "X position (%)", 0, 100, 0.5), num("y", "Y position (%)", 0, 100, 0.5), num("size", "Size (% of width)", 3, 20, 0.1)) +
                    `<p id="qrWarn" class="hidden text-xs rounded-lg bg-amber-50 text-amber-700 border border-amber-200 p-2">The QR library couldn't be loaded, so the QR code can't be drawn. Check your internet connection, or save qrcode.js to assets/js/qrcode.js.</p>
                     <p class="text-xs text-gray-400">Scanning it opens the page that confirms the certificate is genuine. Keep it at least about 6% so printed copies scan well.</p>`;
            }

            if (key === "signatures") {

                const signatories = Object.values(state.assets.signatories);

                html += E.slots.map((slot, i) => `
                    <div class="grid grid-cols-[1fr_90px] gap-2 items-end">
                        <div><label class="text-xs font-bold text-gray-500 block mb-1">Signatory ${i + 1}</label>
                        <select data-slot="${i}" data-sk="signatory_id" class="text-inputs">
                            <option value="0">(empty)</option>
                            ${signatories.map(s => `<option value="${s.signatory_id}" ${Number(slot.signatory_id) === s.signatory_id ? "selected" : ""}>${esc(s.name)}</option>`).join("")}
                        </select></div>
                        <div><label class="text-xs font-bold text-gray-500 block mb-1">X (%)</label>
                        <input type="number" data-slot="${i}" data-sk="x" min="0" max="100" step="0.5" value="${slot.x}" class="text-inputs"></div>
                    </div>`).join("") +
                    grid(num("sigY", "Signature Y (%)", 0, 100, 0.5), num("sigWidth", "Signature width (%)", 4, 40, 0.5),
                        num("nameY", "Name Y (%)", 0, 100, 0.5), num("titleY", "Position Y (%)", 0, 100, 0.5),
                        num("nameSize", "Name size", 0.5, 8, 0.1), num("titleSize", "Position size", 0.5, 8, 0.1),
                        sel("nameFont", "Name font", fonts), sel("titleFont", "Position font", fonts)) +
                    sel("nameWeight", "Name weight", weights) + color("color", "Colour");
            }

            el("controls").innerHTML = html;

            if (key === "qr") {
                CertRenderer.qrAvailable().then(ok => {
                    if (!ok && el("qrWarn")) el("qrWarn").classList.remove("hidden");
                });
            }

        }

        function onControlChange(e) {

            const t = e.target;
            const E = editor.layout.elements[editor.selected];

            if (t.dataset.slot !== undefined) {
                const slot = E.slots[Number(t.dataset.slot)];
                if (t.dataset.sk === "signatory_id") slot.signatory_id = Number(t.value);
                else {
                    const n = Number(t.value);
                    if (!Number.isFinite(n)) return;
                    slot.x = n;
                }
                redraw();
                return;
            }

            const k = t.dataset.k;
            if (!k) return;

            if (t.type === "checkbox") E[k] = t.checked;
            else if (t.type === "number") {
                const n = Number(t.value);
                if (!Number.isFinite(n)) return;
                E[k] = n;
            } else E[k] = k === "logoId" ? Number(t.value) : t.value;

            redraw();

        }

        el("controls").addEventListener("input", onControlChange);
        el("controls").addEventListener("change", onControlChange);

        el("controls").addEventListener("click", e => {

            const chip = e.target.closest("[data-insert]");
            if (!chip) return;

            const ta = el("c_text");
            const s = ta.selectionStart;
            const en = ta.selectionEnd;

            ta.value = ta.value.slice(0, s) + chip.dataset.insert + ta.value.slice(en);
            editor.layout.elements.body.text = ta.value;
            ta.focus();
            ta.selectionStart = ta.selectionEnd = s + chip.dataset.insert.length;

            redraw();

        });

        ["s_name", "s_title", "s_date", "s_brand"].forEach(id => el(id).addEventListener("input", redraw));

        el("t_file").addEventListener("change", () => {

            const file = el("t_file").files[0];
            if (!file) return;

            if (!["image/png", "image/jpeg"].includes(file.type) || file.size > 6 * 1024 * 1024) {
                Swal.fire({
                    icon: "error",
                    title: "Please choose a JPG or PNG under 6 MB",
                    confirmButtonColor: "#234CA1"
                });
                el("t_file").value = "";
                return;
            }

            editor.bgFile = file;
            editor.bgUrl = URL.createObjectURL(file);
            redraw();

        });

        // Draw off-screen first, so a slow earlier draw can never overwrite a newer one
        let drawToken = 0;

        async function redraw() {

            const token = ++drawToken;

            const tpl = {
                background_url: editor.bgUrl,
                layout: editor.layout
            };

            const data = {
                name: el("s_name").value,
                title: el("s_title").value,
                date: el("s_date").value,
                number: "UAAGI-2026-9F3A12BC",
                brandId: Number(el("s_brand").value) || 0,
                brandName: brandName(el("s_brand").value)
            };

            try {

                const tmp = document.createElement("canvas");
                await CertRenderer.render(tmp, tpl, data, {
                    width: 1400
                });

                if (token !== drawToken) return;

                const cv = el("editorCanvas");
                cv.width = tmp.width;
                cv.height = tmp.height;
                cv.getContext("2d").drawImage(tmp, 0, 0);

            } catch (err) {
                console.error(err);
            }

        }

        // ---------- drag on the preview ----------
        const cv = el("editorCanvas");
        let dragging = false;
        let last = null;

        const pointerPct = e => {
            const r = cv.getBoundingClientRect();
            return {
                x: ((e.clientX - r.left) / r.width) * 100,
                y: ((e.clientY - r.top) / r.height) * 100
            };
        };

        const clamp = v => Math.max(0, Math.min(100, Math.round(v * 2) / 2));

        // Where each element "sits", used to grab the closest one
        function anchorOf(key, E) {
            return key === "signatures" ? {
                x: E.slots.reduce((s, sl) => s + sl.x, 0) / 3,
                y: E.nameY
            } : {
                x: E.x,
                y: E.y
            };
        }

        cv.addEventListener("pointerdown", e => {

            dragging = true;
            cv.setPointerCapture(e.pointerId);

            const p = pointerPct(e);
            let best = null;
            let bestDist = Infinity;

            Object.entries(editor.layout.elements).forEach(([key, E]) => {
                if (!E.visible) return;
                const a = anchorOf(key, E);
                const d = Math.hypot(p.x - a.x, (p.y - a.y) * 1.8);
                if (d < bestDist) {
                    bestDist = d;
                    best = key;
                }
            });

            if (best && best !== editor.selected) {
                editor.selected = best;
                renderElementTabs();
                renderControls();
            }

            last = p;

        });

        cv.addEventListener("pointermove", e => {

            if (!dragging || !last) return;

            const p = pointerPct(e);
            const dx = p.x - last.x;
            const dy = p.y - last.y;
            last = p;

            const E = editor.layout.elements[editor.selected];

            if (editor.selected === "signatures") {
                E.slots.forEach(s => s.x = clamp(s.x + dx));
                E.sigY = clamp(E.sigY + dy);
                E.nameY = clamp(E.nameY + dy);
                E.titleY = clamp(E.titleY + dy);
            } else {
                E.x = clamp(E.x + dx);
                E.y = clamp(E.y + dy);
            }

            redraw();

        });

        const endDrag = () => {
            if (dragging) renderControls(); // refresh the X / Y boxes once the drag is over
            dragging = false;
            last = null;
        };

        cv.addEventListener("pointerup", endDrag);
        cv.addEventListener("pointercancel", endDrag);

        // ---------- save ----------
        el("editorSave").addEventListener("click", async () => {

            if (!editor.bgUrl) {
                Swal.fire({
                    icon: "info",
                    title: "Upload a background",
                    text: "Choose your certificate design image first.",
                    confirmButtonColor: "#234CA1"
                });
                return;
            }

            const btn = el("editorSave");
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Saving...';

            try {

                const fd = new FormData();
                fd.append("action", "save");
                fd.append("template_id", editor.id);
                fd.append("copy_from", editor.copyFrom);
                fd.append("name", el("t_name").value.trim());
                fd.append("brand_id", el("t_brand").value);
                fd.append("valid_months", el("t_valid").value);
                if (el("t_default").checked) fd.append("is_default", "1");
                if (el("t_restyle").checked) fd.append("restyle_issued", "1");
                fd.append("layout", JSON.stringify(editor.layout));
                if (editor.bgFile) fd.append("background", editor.bgFile);

                const res = await fetch("../php/certificates/save-certificate-template.php", {
                    method: "POST",
                    body: fd
                });
                const data = await res.json();

                if (data.status !== "success") {
                    Swal.fire({
                        icon: "error",
                        title: "Couldn't save",
                        text: data.message,
                        confirmButtonColor: "#234CA1"
                    });
                    return;
                }

                closeEditor();
                loadAll();

                Swal.fire({
                    toast: true,
                    position: "top-end",
                    icon: "success",
                    title: "Template saved",
                    showConfirmButton: false,
                    timer: 2200
                });

            } catch (err) {
                console.error(err);
                Swal.fire({
                    icon: "error",
                    title: "Something went wrong",
                    text: "Please try again.",
                    confirmButtonColor: "#234CA1"
                });
            } finally {
                btn.disabled = false;
                btn.textContent = "Save Template";
            }

        });

        el("editorClose").addEventListener("click", closeEditor);
        el("editorCancel").addEventListener("click", closeEditor);

        showTab("templates");
        loadAll();
    </script>
</body>

</html>