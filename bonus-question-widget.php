<!-- Floating Bonus Question Button -->
<button
    type="button"
    id="openBonusBtn"
    class="fixed bottom-6 right-14 z-20 bg-gradient-to-br from-[#D02027] to-[#a8181d] hover:scale-105 text-white w-14 h-14 rounded-full shadow-lg flex items-center justify-center transition-transform"
>
    <i class="fa-solid fa-star text-xl"></i>
</button>

<script>
    document.getElementById("openBonusBtn").addEventListener("click", () => {
        openBonusModal();
    });
</script>