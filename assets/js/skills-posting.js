/* ==========================================
   SkillExpert - Post Skill Page
   Page-specific JavaScript
   ========================================== */

document.addEventListener('DOMContentLoaded', function () {


    /* ==========================================
       FORM ELEMENTS
       ========================================== */

    const titleInput =
        document.getElementById('title');

    const categoryInput =
        document.getElementById('category');

    const descriptionInput =
        document.getElementById('description');

    const form =
        document.getElementById('skill-form');

    const publishButton =
        document.getElementById('publish-button');



    /* ==========================================
       PREVIEW ELEMENTS
       ========================================== */

    const previewCard =
        document.getElementById('preview-card');

    const previewTitle =
        document.getElementById('preview-title');

    const previewCategory =
        document.getElementById('preview-category');

    const previewDescription =
        document.getElementById('preview-description');

    const characterCounter =
        document.getElementById('character-counter');



    /* ==========================================
       CATEGORY BUTTONS
       ========================================== */

    const categoryButtons =
        document.querySelectorAll('.category-option');



    /* ==========================================
       HERO START CREATING
       ========================================== */

    const startCreating =
        document.getElementById('start-creating');

    const createSkillSection =
        document.getElementById('create-skill');


    if (
        startCreating &&
        startCreating.tagName === 'BUTTON' &&
        createSkillSection
    ) {

        startCreating.addEventListener(
            'click',
            function () {

                createSkillSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

            }
        );

    }



    /* ==========================================
       LIVE TITLE PREVIEW
       ========================================== */

    if (titleInput) {

        titleInput.addEventListener(
            'input',
            function () {

                const title =
                    titleInput.value.trim();


                if (title === '') {

                    previewTitle.textContent =
                        'Your skill title';

                    previewTitle.classList.add(
                        'preview-empty'
                    );

                } else {

                    previewTitle.textContent =
                        title;

                    previewTitle.classList.remove(
                        'preview-empty'
                    );

                }


                previewPulse();

            }
        );

    }



    /* ==========================================
       LIVE DESCRIPTION PREVIEW
       ========================================== */

    if (descriptionInput) {

        descriptionInput.addEventListener(
            'input',
            function () {

                const description =
                    descriptionInput.value.trim();


                if (description === '') {

                    previewDescription.textContent =
                        'Your skill description will appear here.';

                    previewDescription.classList.add(
                        'preview-empty'
                    );

                } else {

                    previewDescription.textContent =
                        description;

                    previewDescription.classList.remove(
                        'preview-empty'
                    );

                }


                if (characterCounter) {

                    characterCounter.textContent =
                        descriptionInput.value.length +
                        ' / 1000';

                }


                previewPulse();

            }
        );

    }



    /* ==========================================
       CATEGORY SELECTION
       ========================================== */

    categoryButtons.forEach(
        function (button) {

            button.addEventListener(
                'click',
                function () {

                    selectCategory(
                        button.dataset.category
                    );

                }
            );

        }
    );



    /* ==========================================
       CATEGORY SELECTION FUNCTION
       ========================================== */

    function selectCategory(category) {

        categoryButtons.forEach(
            function (button) {

                button.classList.remove(
                    'active'
                );

            }
        );


        const selectedButton =
            document.querySelector(
                '.category-option[data-category="' +
                category +
                '"]'
            );


        if (selectedButton) {

            selectedButton.classList.add(
                'active'
            );

        }


        if (categoryInput) {

            categoryInput.value =
                category;

        }


        if (previewCategory) {

            previewCategory.textContent =
                category;

        }


        previewPulse();

    }



    /* ==========================================
       CATEGORY SHOWCASE CARDS
       ========================================== */

    const showcaseCards =
        document.querySelectorAll(
            '.skill-category-card'
        );


    showcaseCards.forEach(
        function (card) {


            /* Mouse glow */

            card.addEventListener(
                'mousemove',
                function (event) {

                    const rect =
                        card.getBoundingClientRect();

                    const x =
                        event.clientX -
                        rect.left;

                    const y =
                        event.clientY -
                        rect.top;


                    card.style.setProperty(
                        '--mouse-x',
                        x + 'px'
                    );

                    card.style.setProperty(
                        '--mouse-y',
                        y + 'px'
                    );

                }
            );


            /* Click category */

            card.addEventListener(
                'click',
                function () {

                    const category =
                        card.dataset.category;


                    if (
                        category &&
                        createSkillSection
                    ) {

                        createSkillSection.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });


                        setTimeout(
                            function () {

                                selectCategory(
                                    category
                                );

                            },
                            500
                        );

                    }

                }
            );

        }
    );



    /* ==========================================
       EXPLORE MORE BUTTON
       ========================================== */

    const exploreButton =
        document.getElementById(
            'category-explore'
        );

    const categoryTrack =
        document.getElementById(
            'category-track'
        );


    if (
        exploreButton &&
        categoryTrack
    ) {

        exploreButton.addEventListener(
            'click',
            function () {

                categoryTrack.scrollBy({

                    left: 450,

                    behavior: 'smooth'

                });

            }
        );

    }



    /* ==========================================
       HERO CARD 3D MOUSE MOVEMENT
       ========================================== */

    const heroCard =
        document.getElementById('hero-card');


    if (heroCard) {

        heroCard.addEventListener(
            'mousemove',
            function (event) {

                const rect =
                    heroCard.getBoundingClientRect();


                const x =
                    event.clientX -
                    rect.left;

                const y =
                    event.clientY -
                    rect.top;


                const centerX =
                    rect.width / 2;

                const centerY =
                    rect.height / 2;


                const rotateX =
                    ((y - centerY) / centerY) *
                    -4;

                const rotateY =
                    ((x - centerX) / centerX) *
                    4;


                heroCard.style.transform =
                    'perspective(1000px) ' +
                    'rotateX(' +
                    rotateX +
                    'deg) ' +
                    'rotateY(' +
                    rotateY +
                    'deg) ' +
                    'scale(1.02)';

            }
        );


        heroCard.addEventListener(
            'mouseleave',
            function () {

                heroCard.style.transform =
                    'perspective(1000px) ' +
                    'rotateX(0deg) ' +
                    'rotateY(0deg) ' +
                    'scale(1)';

            }
        );

    }



    /* ==========================================
       PREVIEW PULSE
       ========================================== */

    function previewPulse() {

        if (!previewCard) {
            return;
        }


        previewCard.classList.remove(
            'preview-updated'
        );


        /*
         * Force browser to restart animation.
         */

        void previewCard.offsetWidth;


        previewCard.classList.add(
            'preview-updated'
        );

    }



    /* ==========================================
       FORM VALIDATION
       ========================================== */

    if (form) {

        form.addEventListener(
            'submit',
            function (event) {

                const title =
                    titleInput.value.trim();

                const category =
                    categoryInput.value.trim();

                const description =
                    descriptionInput.value.trim();


                if (
                    title === '' ||
                    category === '' ||
                    description === ''
                ) {

                    event.preventDefault();


                    alert(
                        'Please complete all required fields before posting your skill.'
                    );


                    return;

                }


                /*
                 * Prevent accidental double submission.
                 */

                if (publishButton) {

                    publishButton.disabled =
                        true;

                    publishButton.innerHTML =
                        'Publishing... <span>✓</span>';

                }

            }
        );

    }



    /* ==========================================
       SUCCESS MESSAGE
       ========================================== */

    const successMessage =
        document.getElementById(
            'publish-success'
        );

    const closeSuccess =
        document.getElementById(
            'close-success'
        );


    if (
        closeSuccess &&
        successMessage
    ) {

        closeSuccess.addEventListener(
            'click',
            function () {

                successMessage.remove();

            }
        );

    }


});