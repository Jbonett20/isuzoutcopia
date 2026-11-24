<style>
    .project-preloader {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(1px);
    }

    .project-preloader.is-visible {
        display: flex;
    }

    .project-preloader__box {
        min-width: 160px;
        padding: 14px 18px;
        border-radius: 12px;
        background: #ffffff;
        border: 2px solid #ff0604;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.16);
        text-align: center;
    }

    .project-preloader__spinner {
        width: 28px;
        height: 28px;
        margin: 0 auto 8px;
        border: 3px solid #ffd7d7;
        border-top-color: #ff0604;
        border-radius: 50%;
        animation: project-preloader-spin 0.8s linear infinite;
    }

    .project-preloader__text {
        margin: 0;
        color: #111111;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.3px;
        text-transform: uppercase;
    }

    @keyframes project-preloader-spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>

<div id="project-preloader" class="project-preloader" aria-live="polite" aria-busy="true" role="status">
    <div class="project-preloader__box">
        <div class="project-preloader__spinner"></div>
        <p class="project-preloader__text">Loading...</p>
    </div>
</div>

<script>
    (function () {
        if (window.ProjectPreloader) {
            return;
        }

        const preloader = document.getElementById('project-preloader');

        function show() {
            if (!preloader) return;
            preloader.classList.add('is-visible');
        }

        function hide() {
            if (!preloader) return;
            preloader.classList.remove('is-visible');
        }

        function bindForms() {
            const forms = document.querySelectorAll('form[data-show-preloader="true"]');
            forms.forEach((form) => {
                if (form.dataset.preloaderBound === 'true') {
                    return;
                }
                form.dataset.preloaderBound = 'true';

                form.addEventListener('submit', function () {
                    const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                    submitButtons.forEach((btn) => {
                        btn.disabled = true;
                    });
                    show();
                });
            });
        }

        window.ProjectPreloader = {
            show,
            hide,
            bindForms,
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindForms);
        } else {
            bindForms();
        }

        window.addEventListener('pageshow', function () {
            hide();
            bindForms();
        });
    })();
</script>
