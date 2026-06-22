(function () {
    'use strict';

    const STORAGE_KEY = 'theme';

    function getSavedTheme() {
        const savedTheme = localStorage.getItem(STORAGE_KEY);

        return savedTheme === 'dark'
            ? 'dark'
            : 'light';
    }

    function updateThemeButton(theme) {
        const themeButton =
            document.getElementById('darkModeBtn');

        if (!themeButton) {
            return;
        }

        const icon = themeButton.querySelector('i');
        const isDark = theme === 'dark';

        if (icon) {
            icon.className = isDark
                ? 'fa-solid fa-sun'
                : 'fa-solid fa-moon';
        }

        const buttonLabel = isDark
            ? 'Switch to light mode'
            : 'Switch to dark mode';

        themeButton.setAttribute(
            'title',
            buttonLabel
        );

        themeButton.setAttribute(
            'aria-label',
            buttonLabel
        );
    }

    function applyTheme(theme, saveTheme = true) {
        const isDark = theme === 'dark';

        document.documentElement.setAttribute(
            'data-theme',
            theme
        );

        document.body.classList.toggle(
            'dark',
            isDark
        );

        if (saveTheme) {
            localStorage.setItem(
                STORAGE_KEY,
                theme
            );
        }

        updateThemeButton(theme);
    }

    function initialiseTheme() {
        const themeButton =
            document.getElementById('darkModeBtn');

        applyTheme(
            getSavedTheme(),
            false
        );

        if (!themeButton) {
            return;
        }

        themeButton.addEventListener(
            'click',
            function () {
                const isDark =
                    document.body.classList.contains('dark');

                applyTheme(
                    isDark ? 'light' : 'dark'
                );
            }
        );
    }

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            initialiseTheme
        );
    } else {
        initialiseTheme();
    }

    window.addEventListener(
        'storage',
        function (event) {
            if (
                event.key === STORAGE_KEY &&
                event.newValue
            ) {
                applyTheme(
                    event.newValue,
                    false
                );
            }
        }
    );
})();