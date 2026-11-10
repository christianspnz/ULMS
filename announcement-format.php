<!-- Shared announcement helpers: safe text formatting, priority colours and button links -->
<script>
    window.AnnouncementUI = window.AnnouncementUI || (function() {
        const esc = s => {
            const d = document.createElement("div");
            d.textContent = s ?? "";
            return d.innerHTML.replace(/"/g, "&quot;").replace(/'/g, "&#39;");
        };

        // Plain text with **bold**, clickable links and line breaks. Everything is escaped FIRST, so no HTML can get through.
        function toHtml(text) {
            return esc(text)
                .replace(/\*\*([^*\n]+?)\*\*/g, "<strong>$1</strong>")
                .replace(/(https?:\/\/[^\s<]*[^\s<.,;:!?)\]])/g, '<a href="$1" target="_blank" rel="noopener noreferrer" style="color:#234CA1;text-decoration:underline;word-break:break-all">$1</a>')
                .replace(/\r?\n/g, "<br>");
        }

        // For short excerpts: no bold markers
        const plain = text => String(text ?? "").replace(/\*\*/g, "");

        const PRIORITY = {
            Normal: {
                color: "#234CA1",
                bg: "#eff6ff",
                icon: "fa-bullhorn"
            },
            Important: {
                color: "#d97706",
                bg: "#fffbeb",
                icon: "fa-circle-exclamation"
            },
            Urgent: {
                color: "#D02027",
                bg: "#fef2f2",
                icon: "fa-triangle-exclamation"
            }
        };

        const CATEGORY_ICON = {
            General: "fa-bullhorn",
            Training: "fa-graduation-cap",
            Policy: "fa-scale-balanced",
            Event: "fa-calendar-days",
            Reminder: "fa-bell"
        };

        const CTA_DEFAULT_LABEL = {
            Course: "Open course",
            Schedule: "View schedule",
            Library: "Open in Library",
            Link: "Learn more"
        };

        // Where the button takes a learner (paths are relative to the learner pages)
        function ctaHref(a) {
            switch (a.cta_type) {
                case "Course":
                    return `courses.php?highlight_course=${Number(a.cta_ref)}`;
                case "Schedule":
                    return `calendar.php?open_schedule=${Number(a.cta_ref)}`;
                case "Library":
                    return `library-viewer.php?id=${Number(a.cta_ref)}`;
                case "Link":
                    return /^https?:\/\//i.test(a.cta_url || "") ? a.cta_url : null;
                default:
                    return null;
            }
        }

        const ctaLabel = a => (a.cta_label && a.cta_label.trim()) || CTA_DEFAULT_LABEL[a.cta_type] || "Open";

        // "Just now", "5 min ago", "Yesterday", "Nov 3"
        function timeAgo(s) {
            const d = new Date(String(s).replace(" ", "T"));
            const diff = Math.floor((Date.now() - d.getTime()) / 1000);

            if (isNaN(diff) || diff < 60) return "Just now";
            if (diff < 3600) return `${Math.floor(diff / 60)} min ago`;
            if (diff < 86400) {
                const h = Math.floor(diff / 3600);
                return `${h} hour${h === 1 ? "" : "s"} ago`;
            }
            if (diff < 172800) return "Yesterday";
            if (diff < 604800) return `${Math.floor(diff / 86400)} days ago`;

            return d.toLocaleDateString(undefined, {
                month: "short",
                day: "numeric",
                year: d.getFullYear() === new Date().getFullYear() ? undefined : "numeric"
            });
        }

        return {
            esc,
            timeAgo,
            toHtml,
            plain,
            PRIORITY,
            CATEGORY_ICON,
            CTA_DEFAULT_LABEL,
            ctaHref,
            ctaLabel
        };

    })();
</script>