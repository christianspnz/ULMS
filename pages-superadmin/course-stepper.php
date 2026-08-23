<div data-aos="fade-down" data-aos-delay="200" data-aos-easing="ease-in-out" class="w-full flex justify-center items-start">
    <ul class="steps steps-vertical lg:steps-horizontal w-full">
        <li class="step <?= $currentStep >= 1 ? 'step-primary' : '' ?>">
            Course Information
        </li>
        <li class="step <?= $currentStep >= 2 ? 'step-primary' : '' ?>">
            Training Modules
        </li>
        <li class="step <?= $currentStep >= 3 ? 'step-primary' : '' ?>">
            Assessment
        </li>
        <li class="step <?= $currentStep >= 4 ? 'step-primary' : '' ?>">
            Review & Publish
        </li>
    </ul>
</div>