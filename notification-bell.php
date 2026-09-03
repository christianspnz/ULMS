<div class="flex items-center gap-x-2" style="order: 2;">
    <div class="relative" id="notificationBellWrapper">

        <button type="button" id="notificationBellBtn" class="relative w-10 h-10 rounded-full hover:bg-gray-100 flex items-center justify-center transition">
            <i class="fa-solid fa-bell text-[#234CA1] text-2xl"></i>
            <span id="notificationBadge" class="hidden absolute top-1 right-1 bg-[#D02027] text-white text-[10px] rounded-full min-w-4 h-4 px-1 flex items-center justify-center">0</span>
        </button>

        <div id="notificationDropdown" class="hidden absolute right-0 mt-2 py-2 w-80 bg-white rounded-2xl shadow-xl border border-gray-200 z-50 overflow-hidden">

            <div class="flex flex-col px-4 py-2">
                <h3 class="font-eurostile-bold text-[#234CA1]">Notifications</h3>
                <div class="flex justify-between items-center py-1 border-b border-gray-100">
                    <div class="flex gap-x-3 justify-start items-center">
                        <button type="button" id="allBtn" class="notif-filter-btn text-sm text-[#234CA1] hover:underline font-eurostile-medium" data-filter="all">All</button>
                        <button type="button" id="unreadBtn" class="notif-filter-btn text-sm  hover:underline font-eurostile-medium" data-filter="unread">Unread</button>
                    </div>
                    <button type="button" id="markAllReadBtn" class="text-sm text-[#234CA1] hover:underline font-eurostile-medium">Mark all read</button>
                </div>
            </div>

            <div id="notificationList" class="max-h-96 overflow-y-auto">
                <p class="text-gray-400 text-sm text-center py-8">Loading...</p>
            </div>

        </div>

    </div>
</div>

<script>
    let allNotifications = [];
    let activeNotifFilter = "all";
    async function loadNotifications() {

        try {

            const res = await fetch("../php/notifications/get-notifications.php");
            const data = await res.json();

            if (data.status !== "success") return;

            const badge = document.getElementById("notificationBadge");

            if (data.unread_count > 0) {
                badge.textContent = data.unread_count > 9 ? "9+" : data.unread_count;
                badge.classList.remove("hidden");
            } else {
                badge.classList.add("hidden");
            }

            allNotifications = data.notifications;

            renderNotifications();

        } catch (err) {
            console.error(err);
        }

    }

    function renderNotifications() {

        const list = document.getElementById("notificationList");

        const filtered = activeNotifFilter === "unread" ?
            allNotifications.filter(n => n.is_read == 0) :
            allNotifications;

        if (filtered.length === 0) {
            list.innerHTML = `<p class="text-gray-400 text-sm text-center py-8">${activeNotifFilter === "unread" ? "No unread notifications." : "No notifications yet."}</p>`;
            return;
        }

        const typeIcons = {
            "New Course": "fa-book-open",
            "New Schedule": "fa-calendar-check",
            "Pending Approval": "fa-user-clock"
        };

        list.innerHTML = filtered.map(n => `
        <button type="button"
                class="notification-item w-full text-left px-4 py-3 border-b border-gray-50 hover:bg-blue-50/50 transition flex gap-3 ${n.is_read == 0 ? 'bg-blue-50/30' : ''}"
                data-id="${n.notification_id}" data-link="${n.link}">
            <div class="w-9 h-9 rounded-lg bg-[#234CA1]/10 flex items-center justify-center shrink-0">
                <i class="fa-solid ${typeIcons[n.type] ?? 'fa-bell'} text-[#234CA1] text-sm"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-eurostile-medium text-gray-800 truncate">${escapeNotifHtml(n.title)}</p>
                <p class="text-xs font-eurostile text-gray-500 mt-0.5 line-clamp-2">${escapeNotifHtml(n.message)}</p>
                <p class="text-[11px] font-eurostile text-gray-400 mt-1">${timeAgo(n.created_at)}</p>
            </div>
            ${n.is_read == 0 ? '<span class="w-2 h-2 rounded-full bg-[#D02027] shrink-0 mt-1.5"></span>' : ''}
        </button>
    `).join("");

        document.querySelectorAll(".notification-item").forEach(item => {
            item.addEventListener("click", () => {
                markNotificationRead(item.dataset.id);
                window.location.href = item.dataset.link;
            });
        });

    }

    document.querySelectorAll(".notif-filter-btn").forEach(btn => {

        btn.addEventListener("click", (e) => {

            e.stopPropagation();

            activeNotifFilter = btn.dataset.filter;

            document.querySelectorAll(".notif-filter-btn").forEach(b => {
                b.classList.remove("border-[#234CA1]", "text-[#234CA1]");
                b.classList.add("border-transparent", "text-black");
            });

            btn.classList.add("border-[#234CA1]", "text-[#234CA1]");
            btn.classList.remove("border-transparent", "text-black");

            renderNotifications();

        });

    });

    async function markNotificationRead(id) {
        try {
            const formData = new FormData();
            formData.append("notification_id", id);
            await fetch("../php/notifications/mark-read.php", {
                method: "POST",
                body: formData
            });
        } catch (err) {
            console.error(err);
        }
    }

    document.getElementById("markAllReadBtn").addEventListener("click", async (e) => {
        e.stopPropagation();
        try {
            await fetch("../php/notifications/mark-all-read.php", {
                method: "POST"
            });
            loadNotifications();
        } catch (err) {
            console.error(err);
        }
    });

    document.getElementById("notificationBellBtn").addEventListener("click", (e) => {
        e.stopPropagation();
        const dropdown = document.getElementById("notificationDropdown");
        dropdown.classList.toggle("hidden");
        if (!dropdown.classList.contains("hidden")) loadNotifications();
    });

    document.addEventListener("click", (e) => {
        const wrapper = document.getElementById("notificationBellWrapper");
        if (!wrapper.contains(e.target)) {
            document.getElementById("notificationDropdown").classList.add("hidden");
        }
    });

    function escapeNotifHtml(str) {
        const div = document.createElement("div");
        div.textContent = str ?? "";
        return div.innerHTML;
    }

    function timeAgo(dateStr) {
        const seconds = Math.floor((new Date() - new Date(dateStr)) / 1000);
        if (seconds < 60) return "Just now";
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return `${minutes}m ago`;
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours}h ago`;
        const days = Math.floor(hours / 24);
        return `${days}d ago`;
    }

    loadNotifications();
    setInterval(loadNotifications, 45000);
</script>