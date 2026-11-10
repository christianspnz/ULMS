<div class="sticky top-16 z-10 bg-white pb-3 w-full">
    <!-- HERO -->
    <section class="relative overflow-hidden rounded-3xl bg-[#234CA1] p-5 md:p-8 text-white shadow-lg">
        <div class="absolute -right-10 -top-10 w-60 h-60 rounded-full bg-blue-600/30 blur-3xl pointer-events-none"></div>
        <div class="absolute right-1/3 -bottom-10 w-60 h-60 rounded-full bg-cyan-500/20 blur-3xl pointer-events-none"></div>
        <div class="hero-grid absolute inset-0 pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div class="w-full flex justify-center items-start">
                <ul class="steps steps-vertical lg:steps-horizontal w-full gap-2">
                    <li class="step <?= $currentStep >= 1 ? 'step-primary' : '' ?>  text-xs sm:text-sm font-semibold <?= $currentStep === 1 ? 'text-amber-400' : 'text-gray-400' ?>">
                        <span class="font-eurostile-bold">
                            Course Information
                        </span>
                    </li>
                    <li class="step <?= $currentStep >= 2 ? 'step-primary' : '' ?>  text-xs sm:text-sm font-semibold <?= $currentStep === 2 ? 'text-amber-400' : 'text-gray-400' ?>">
                        <span class="font-eurostile-bold">
                            Training Modules
                        </span>
                    </li>
                    <li class="step <?= $currentStep >= 3 ? 'step-primary' : '' ?>  text-xs sm:text-sm font-semibold <?= $currentStep === 3 ? 'text-amber-400' : 'text-gray-400' ?>">
                        <span class="font-eurostile-bold">
                            Assessment
                        </span>
                    </li>
                    <li class="step <?= $currentStep >= 4 ? 'step-primary' : '' ?>  text-xs sm:text-sm font-semibold <?= $currentStep === 4 ? 'text-amber-400' : 'text-gray-400' ?>">
                        <span class="font-eurostile-bold">
                            Review & Publish
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</div>