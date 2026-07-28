<!-- Hamburger button - only visible below lg -->
<button id="sidebar-hamburger" class="hamburger-btn" onclick="toggleSidebar()">
    <span class="hamburger-line"></span>
    <span class="hamburger-line"></span>
    <span class="hamburger-line"></span>
</button>

<!-- Overlay for mobile - closes sidebar when clicked outside -->
<div id="sidebar-overlay" class="hidden fixed inset-0 bg-black/40 z-30 lg:hidden" onclick="toggleSidebar()"></div>
<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const hamburger = document.getElementById('sidebar-hamburger');

    sidebar.classList.toggle('hidden');
    sidebar.classList.toggle('flex');
    overlay.classList.toggle('hidden');
    hamburger.classList.toggle('open');
}
</script>

<style>
#sidebar-hamburger.open .hamburger-line:nth-child(1) {
    transform: translateY(8px) rotate(45deg);
}
#sidebar-hamburger.open .hamburger-line:nth-child(2) {
    opacity: 0;
}
#sidebar-hamburger.open .hamburger-line:nth-child(3) {
    transform: translateY(-8px) rotate(-45deg);
}
</style>