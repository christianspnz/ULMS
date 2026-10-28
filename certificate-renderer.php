<!-- Shared certificate drawing + PDF download -->
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600&family=Cormorant+Garamond:wght@400;600;700&family=EB+Garamond:wght@400;700&family=Great+Vibes&family=Montserrat:wght@400;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script>
    window.CertRenderer = (function() {

        const BUILTIN = {
            playfair: { css: '"Playfair Display", Georgia, serif', label: "Playfair Display" },
            cinzel: { css: '"Cinzel", Georgia, serif', label: "Cinzel" },
            script: { css: '"Great Vibes", cursive', label: "Great Vibes (handwriting)" },
            garamond: { css: '"EB Garamond", Georgia, serif', label: "EB Garamond" },
            cormorant: { css: '"Cormorant Garamond", Georgia, serif', label: "Cormorant Garamond" },
            montserrat: { css: '"Montserrat", Arial, sans-serif', label: "Montserrat" },
            georgia: { css: 'Georgia, "Times New Roman", serif', label: "Georgia" },
            arial: { css: 'Arial, Helvetica, sans-serif', label: "Arial" }
        };

        const DEFAULT_BODY = 'For completing the **{title}**. Given this **{day}^^{ordinal}^^** day of **{month}**, in the year **{year}**, UAAGI Auto Group commends his/her dedication in continuous learning and growth.';

        // x / y are % of the certificate; size is % of its width. Matches your sample certificate.
        const DEFAULT_LAYOUT = {
            elements: {
                name: { visible: true, x: 50, y: 49.5, size: 8.5, maxWidth: 78, color: "#111111", font: "script", weight: "normal", align: "center" },
                body: { visible: true, x: 50, y: 64.5, size: 2.05, maxWidth: 66, lineHeight: 1.3, color: "#111111", font: "garamond", weight: "normal", align: "center", text: DEFAULT_BODY },
                number: { visible: true, x: 50, y: 97.6, size: 0.9, maxWidth: 50, color: "#475569", font: "montserrat", weight: "normal", align: "center" },
                brandLogo: { visible: true, x: 80.5, y: 16.3, width: 20, logoId: 0 },
                extraLogo: { visible: false, x: 50, y: 16.3, width: 14, logoId: 0 },
                qr: { visible: true, x: 50, y: 92.2, size: 6.5 },
                signatures: {
                    visible: true,
                    slots: [{ signatory_id: 0, x: 26 }, { signatory_id: 0, x: 50.4 }, { signatory_id: 0, x: 74.7 }],
                    sigY: 77.8, sigWidth: 15, nameY: 83.6, titleY: 86.2,
                    nameSize: 2.0, titleSize: 1.6, nameFont: "cormorant", titleFont: "cormorant", nameWeight: "bold", color: "#111111"
                }
            }
        };

        let assets = { templates: {}, defaults: {}, logos: {}, signatories: {}, fonts: [] };
        const customLoaded = {};
        const imageCache = {};

        // ---------- names ----------
        // "HANZ KERBY S. DACUCOS" -> "Hanz Kerby S. Dacucos". Names that already mix capitals are left alone.
        function properName(s) {
            s = String(s || "").replace(/\s+/g, " ").trim();
            if (!s || (s.toUpperCase() !== s && s.toLowerCase() !== s)) return s;
            return s.toLowerCase()
                .replace(/(^|[\s\-'’.(])(\p{L})/gu, (m, p, c) => p + c.toUpperCase())
                .replace(/\b(Ii|Iii|Iv|Vi|Vii|Viii|Ix)\b/g, m => m.toUpperCase())
                .replace(/\bMc(\p{L})/gu, (m, c) => "Mc" + c.toUpperCase());
        }

        // ---------- fonts ----------
        function fontCss(key) {
            if (BUILTIN[key]) return BUILTIN[key].css;
            const m = /^custom:(\d+)$/.exec(key || "");
            return m ? `"CertFont_${m[1]}", Georgia, serif` : BUILTIN.georgia.css;
        }

        const fontString = (key, weight, px) => `${weight === "bold" ? 700 : 400} ${px}px ${fontCss(key)}`;

        function loadCustomFont(f) {
            if (!customLoaded[f.font_id]) {
                customLoaded[f.font_id] = new FontFace(`CertFont_${f.font_id}`, `url(../${f.file_path})`)
                    .load()
                    .then(face => {
                        document.fonts.add(face);
                        return face;
                    })
                    .catch(err => console.warn("Font failed to load:", f.family, err));
            }
            return customLoaded[f.font_id];
        }

        async function setAssets(a) {
            assets = Object.assign({ templates: {}, defaults: {}, logos: {}, signatories: {}, fonts: [] }, a || {});
            await Promise.all((assets.fonts || []).map(loadCustomFont));
        }

        const getAssets = () => assets;

        const fontOptions = () => Object.entries(BUILTIN).map(([k, v]) => [k, v.label])
            .concat((assets.fonts || []).map(f => [`custom:${f.font_id}`, `${f.family} (uploaded)`]));

        async function ensureFonts(L) {

            const E = L.elements;
            const wanted = [
                [E.name.font, E.name.weight], [E.number.font, E.number.weight],
                [E.body.font, "normal"], [E.body.font, "bold"],
                [E.signatures.nameFont, E.signatures.nameWeight], [E.signatures.titleFont, "normal"]
            ];

            await Promise.all(wanted.map(([k, w]) => document.fonts.load(fontString(k, w, 40)).catch(() => {})));

        }

        // ---------- QR code library (local copy first, then two CDNs) ----------
        const QR_SOURCES = [
            "../assets/js/qrcode.js",
            "https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.js",
            "https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"
        ];

        let qrReady = null;

        function loadScript(src) {
            return new Promise((resolve, reject) => {
                const s = document.createElement("script");
                s.src = src;
                s.onload = resolve;
                s.onerror = () => {
                    s.remove();
                    reject(new Error("Could not load " + src));
                };
                document.head.appendChild(s);
            });
        }

        function qrAvailable() {
            if (!qrReady) {
                qrReady = (async () => {
                    if (typeof window.qrcode === "function") return true;
                    for (const src of QR_SOURCES) {
                        try {
                            await loadScript(src);
                            if (typeof window.qrcode === "function") return true;
                        } catch (e) {}
                    }
                    console.error("The QR code library could not be loaded. Check the internet connection, or save qrcode.js to assets/js/qrcode.js.");
                    return false;
                })();
            }
            return qrReady;
        }

        function makeQr(text) {
            for (const typeNumber of [0, 6, 8, 10, 14]) { // 0 = pick the size automatically
                try {
                    const q = window.qrcode(typeNumber, "M");
                    q.addData(text);
                    q.make();
                    return q;
                } catch (e) {}
            }
            return null;
        }

        // ---------- layout / data ----------
        function mergeLayout(layout) {

            const D = DEFAULT_LAYOUT.elements;
            const L = (layout && layout.elements) || {};
            const out = { elements: {} };

            Object.keys(D).forEach(k => {
                out.elements[k] = Object.assign({}, D[k], L[k] || {});
            });

            const slots = (L.signatures && Array.isArray(L.signatures.slots) && L.signatures.slots.length) ? L.signatures.slots : D.signatures.slots;
            out.elements.signatures.slots = [0, 1, 2].map(i => Object.assign({}, D.signatures.slots[i], slots[i] || {}));

            return out;

        }

        function formatDate(iso) {
            if (!iso) return "";
            return new Date(String(iso).slice(0, 10) + "T00:00:00")
                .toLocaleDateString("en-US", { year: "numeric", month: "long", day: "numeric" });
        }

        function ordinal(n) {
            const j = n % 10, k = n % 100;
            if (j === 1 && k !== 11) return "st";
            if (j === 2 && k !== 12) return "nd";
            if (j === 3 && k !== 13) return "rd";
            return "th";
        }

        function placeholders(data) {

            const d = data.date ? new Date(String(data.date).slice(0, 10) + "T00:00:00") : null;
            const monthLong = d ? d.toLocaleDateString("en-US", { month: "long" }) : "";

            return {
                title: data.title || "",
                name: properName(data.name),
                brand: data.brandName || "",
                number: data.number || "",
                day: d ? String(d.getDate()).padStart(2, "0") : "",
                ordinal: d ? ordinal(d.getDate()) : "",
                month: monthLong.toUpperCase(),
                Month: monthLong,
                year: d ? String(d.getFullYear()) : "",
                date: formatDate(data.date),
                valid_until: data.validUntil ? formatDate(data.validUntil) : ""
            };

        }

        // valid / expired / revoked
        function certStatus(c) {
            if (c.revoked_at) return "revoked";
            if (c.valid_until && String(c.valid_until).slice(0, 10) < new Date().toISOString().slice(0, 10)) return "expired";
            return "valid";
        }

        function templateForCert(cert) {
            const t = assets.templates || {};
            if (cert.template_id && t[cert.template_id]) return t[cert.template_id];
            const id = (assets.defaults || {})[cert.brand_id] || (assets.defaults || {})[0];
            return id ? (t[id] || null) : null;
        }

        const dataFromCert = c => ({
            name: c.recipient_name,
            title: c.title,
            date: c.event_date,
            number: c.certificate_no,
            brandId: c.brand_id,
            brandName: c.brand_name || "",
            validUntil: c.valid_until || null
        });

        // ---------- drawing ----------
        function loadImage(src) {
            if (!imageCache[src]) {
                imageCache[src] = new Promise((resolve, reject) => {
                    const img = new Image();
                    img.onload = () => resolve(img);
                    img.onerror = () => {
                        delete imageCache[src];
                        reject(new Error("Image failed to load: " + src));
                    };
                    img.src = src;
                });
            }
            return imageCache[src];
        }

        function drawText(ctx, text, el, W, H) {

            let size = (el.size / 100) * W;
            const maxPx = (el.maxWidth / 100) * W;
            const minSize = size * 0.5;

            ctx.font = fontString(el.font, el.weight, size);

            // A long name shrinks to fit instead of running off the certificate
            while (ctx.measureText(text).width > maxPx && size > minSize) {
                size -= 1;
                ctx.font = fontString(el.font, el.weight, size);
            }

            ctx.fillStyle = el.color;
            ctx.textAlign = el.align;
            ctx.textBaseline = "middle";
            ctx.fillText(text, (el.x / 100) * W, (el.y / 100) * H);

        }

        // **bold**, ^^superscript^^ and {placeholders}; wraps into lines and aligns them
        function drawRich(ctx, template, vals, el, W, H) {

            const runs = [];
            let bold = false, sup = false;

            String(template).split(/(\*\*|\^\^)/).forEach(tok => {
                if (tok === "**") bold = !bold;
                else if (tok === "^^") sup = !sup;
                else if (tok) runs.push({ text: tok, bold, sup });
            });

            // Words are made of pieces, so "Training" + "." never gets split across lines
            const words = [];
            let cur = null;

            runs.forEach(r => {
                const text = r.text.replace(/\{(\w+)\}/g, (m, k) => (k in vals ? vals[k] : m));
                (text.match(/\s+|\S+/g) || []).forEach(part => {
                    if (/^\s+$/.test(part)) cur = null;
                    else {
                        if (!cur) { cur = []; words.push(cur); }
                        cur.push({ text: part, bold: r.bold, sup: r.sup });
                    }
                });
            });

            const size = (el.size / 100) * W;
            const maxPx = (el.maxWidth / 100) * W;
            const pieceFont = p => fontString(el.font, p.bold ? "bold" : "normal", p.sup ? size * 0.6 : size);

            const widthOf = w => w.reduce((sum, p) => {
                ctx.font = pieceFont(p);
                return sum + ctx.measureText(p.text).width;
            }, 0);

            ctx.font = fontString(el.font, "normal", size);
            const space = ctx.measureText(" ").width;

            const measured = words.map(w => ({ pieces: w, w: widthOf(w) }));
            const lines = [];
            let line = [], lineW = 0;

            measured.forEach(w => {
                const add = line.length ? space + w.w : w.w;
                if (line.length && lineW + add > maxPx) {
                    lines.push({ words: line, width: lineW });
                    line = [w];
                    lineW = w.w;
                } else {
                    line.push(w);
                    lineW += add;
                }
            });
            if (line.length) lines.push({ words: line, width: lineW });

            const lh = size * el.lineHeight;
            const anchor = (el.x / 100) * W;
            let y = (el.y / 100) * H - (lines.length * lh) / 2 + lh / 2;

            ctx.fillStyle = el.color;
            ctx.textAlign = "left";
            ctx.textBaseline = "middle";

            lines.forEach(L => {

                let x = el.align === "left" ? anchor : el.align === "right" ? anchor - L.width : anchor - L.width / 2;

                L.words.forEach((w, i) => {
                    if (i) x += space;
                    w.pieces.forEach(p => {
                        ctx.font = pieceFont(p);
                        ctx.fillText(p.text, x, y - (p.sup ? size * 0.28 : 0));
                        x += ctx.measureText(p.text).width;
                    });
                });

                y += lh;

            });

        }

        // The logo a logo element shows: the one it was told to use, else (when `auto`) the brand's main logo
        function logoFor(el, brandId, auto) {

            const all = assets.logos || {};

            if (el.logoId && all[el.logoId]) return all[el.logoId];
            if (!auto) return null;

            const mine = Object.values(all).filter(l => Number(l.brand_id) === Number(brandId));
            return mine.find(l => Number(l.is_primary) === 1) || mine[0] || null;

        }

        async function drawLogo(ctx, el, logo, W, H) {

            if (!logo) return;

            try {
                const img = await loadImage(`../${logo.logo_path}`);
                const w = (el.width / 100) * W;
                const h = (w * img.naturalHeight) / img.naturalWidth;
                ctx.drawImage(img, (el.x / 100) * W - w / 2, (el.y / 100) * H - h / 2, w, h);
            } catch (e) {
                console.warn(e);
            }

        }

        async function drawSignatures(ctx, el, W, H) {

            for (const slot of el.slots) {

                const s = (assets.signatories || {})[slot.signatory_id];
                if (!s) continue;

                const x = (slot.x / 100) * W;

                if (s.signature_path) {
                    try {
                        const img = await loadImage(`../${s.signature_path}`);
                        const w = (el.sigWidth / 100) * W;
                        const h = (w * img.naturalHeight) / img.naturalWidth;
                        ctx.drawImage(img, x - w / 2, (el.sigY / 100) * H - h / 2, w, h);
                    } catch (e) {
                        console.warn(e);
                    }
                }

                ctx.fillStyle = el.color;
                ctx.textAlign = "center";
                ctx.textBaseline = "middle";

                ctx.font = fontString(el.nameFont, el.nameWeight, (el.nameSize / 100) * W);
                ctx.fillText(s.name, x, (el.nameY / 100) * H);

                if (s.position) {
                    ctx.font = fontString(el.titleFont, "normal", (el.titleSize / 100) * W);
                    ctx.fillText(s.position, x, (el.titleY / 100) * H);
                }

            }

        }

        // The QR is drawn square by square (sharp at any size) on a white tile, so it scans over any background
        async function drawQr(ctx, el, number, W, H) {

            if (!(await qrAvailable())) return;

            // Resolves to the site's root, e.g. https://yoursite/verify-certificate.php?no=...
            const url = new URL(`../verify-certificate.php?no=${encodeURIComponent(number)}`, window.location.href).href;
            const qr = makeQr(url);
            if (!qr) return;

            const n = qr.getModuleCount();
            const quiet = 3;
            const size = (el.size / 100) * W;
            const x0 = (el.x / 100) * W - size / 2;
            const y0 = (el.y / 100) * H - size / 2;
            const cell = size / (n + quiet * 2);

            ctx.fillStyle = "#ffffff";
            ctx.fillRect(x0, y0, size, size);
            ctx.fillStyle = "#000000";

            for (let r = 0; r < n; r++) {
                for (let c = 0; c < n; c++) {
                    if (qr.isDark(r, c)) ctx.fillRect(x0 + (c + quiet) * cell, y0 + (r + quiet) * cell, cell + 0.4, cell + 0.4);
                }
            }

        }

        // Used when a brand has no template at all, so learners still get a certificate
        async function drawBuiltIn(ctx, W, H) {

            ctx.fillStyle = "#ffffff";
            ctx.fillRect(0, 0, W, H);

            ctx.fillStyle = "#c8102e";
            ctx.beginPath();
            ctx.moveTo(0, H * 0.5);
            ctx.quadraticCurveTo(W * 0.03, H * 0.9, W * 0.28, H);
            ctx.lineTo(0, H);
            ctx.closePath();
            ctx.fill();

            ctx.fillStyle = "#1d4ed8";
            ctx.beginPath();
            ctx.moveTo(W, H * 0.5);
            ctx.quadraticCurveTo(W * 0.97, H * 0.9, W * 0.72, H);
            ctx.lineTo(W, H);
            ctx.closePath();
            ctx.fill();

            try {
                const logo = await loadImage("../assets/ulh-logo.png");
                const h = H * 0.12;
                ctx.drawImage(logo, W * 0.1, H * 0.1, h * logo.naturalWidth / logo.naturalHeight, h);
            } catch (e) {}

            await document.fonts.load(fontString("garamond", "bold", 40)).catch(() => {});

            const line = (text, y, sizePct, weight) => {
                ctx.font = fontString("garamond", weight, (sizePct / 100) * W);
                ctx.fillStyle = "#111111";
                ctx.textAlign = "center";
                ctx.textBaseline = "middle";
                ctx.fillText(text, W / 2, (y / 100) * H);
            };

            line("CERTIFICATE OF COMPLETION", 31, 5.2, "bold");
            line("This certificate is proudly presented to", 38.5, 2.3, "normal");

            ctx.strokeStyle = "#111111";
            ctx.lineWidth = Math.max(1, W * 0.0006);
            ctx.beginPath();
            ctx.moveTo(W * 0.15, H * 0.565);
            ctx.lineTo(W * 0.85, H * 0.565);
            ctx.stroke();

        }

        /**
         * render(canvas, template, data, { width })
         * template: { background_url | background_path, layout } or null (built-in design)
         * data: { name, title, date, number, brandId, brandName, validUntil }
         */
        async function render(canvas, template, data, opts = {}) {

            const L = mergeLayout(template && template.layout);
            await ensureFonts(L);

            const bgSrc = template && (template.background_url || (template.background_path ? `../${template.background_path}` : null));
            const img = bgSrc ? await loadImage(bgSrc) : null;

            const targetW = opts.width || 2400;
            const W = img ? Math.min(targetW, img.naturalWidth) : targetW;
            const H = Math.round(W * (img ? img.naturalHeight / img.naturalWidth : 1 / 1.4142));

            canvas.width = W;
            canvas.height = H;

            const ctx = canvas.getContext("2d");

            if (img) ctx.drawImage(img, 0, 0, W, H);
            else await drawBuiltIn(ctx, W, H);

            const E = L.elements;
            const vals = placeholders(data);

            if (E.brandLogo.visible) await drawLogo(ctx, E.brandLogo, logoFor(E.brandLogo, data.brandId, true), W, H);
            if (E.extraLogo.visible) await drawLogo(ctx, E.extraLogo, logoFor(E.extraLogo, data.brandId, false), W, H);
            if (E.name.visible && vals.name) drawText(ctx, vals.name, E.name, W, H);
            if (E.body.visible) drawRich(ctx, E.body.text, vals, E.body, W, H);
            if (E.signatures.visible) await drawSignatures(ctx, E.signatures, W, H);
            if (E.number.visible && data.number) drawText(ctx, `Certificate No. ${data.number}`, E.number, W, H);
            if (E.qr.visible && data.number) await drawQr(ctx, E.qr, data.number, W, H);

            return canvas;

        }

        const renderCert = (canvas, cert, opts) => render(canvas, templateForCert(cert), dataFromCert(cert), opts);

        async function buildPdf(template, data) {

            const canvas = document.createElement("canvas");
            await render(canvas, template, data, { width: 3000 });

            const landscape = canvas.width >= canvas.height;
            const pw = landscape ? 297 : 210;
            const ph = (pw * canvas.height) / canvas.width;

            const pdf = new window.jspdf.jsPDF({ orientation: landscape ? "landscape" : "portrait", unit: "mm", format: [pw, ph] });
            pdf.addImage(canvas.toDataURL("image/jpeg", 0.92), "JPEG", 0, 0, pw, ph);

            return pdf;

        }

        const safeName = s => String(s || "certificate.pdf").replace(/[^a-z0-9\-_. ]/gi, "_");

        async function downloadPdf(template, data, filename) {
            (await buildPdf(template, data)).save(safeName(filename));
        }

        const downloadCert = cert => downloadPdf(templateForCert(cert), dataFromCert(cert), `Certificate - ${cert.title}.pdf`);

        // For the ZIP download
        const pdfBufferForCert = async cert => (await buildPdf(templateForCert(cert), dataFromCert(cert))).output("arraybuffer");

        return {
            BUILTIN, DEFAULT_LAYOUT, DEFAULT_BODY, setAssets, getAssets, fontOptions, mergeLayout, formatDate, properName, certStatus,
            qrAvailable, templateForCert, dataFromCert, render, renderCert, downloadPdf, downloadCert, pdfBufferForCert, safeName
        };

    })();
</script>