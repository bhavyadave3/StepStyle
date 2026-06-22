"use strict";

const STEPSTYLE_BASE_URL = (
    window.STEPSTYLE_URL || "/StepStyle"
).replace(/\/$/, "");

/*
|--------------------------------------------------------------------------
| Escape HTML
|--------------------------------------------------------------------------
*/

function escapeHtml(value){

    const element =
        document.createElement("div");

    element.textContent =
        String(value ?? "");

    return element.innerHTML;
}

/*
|--------------------------------------------------------------------------
| Toast Notification
|--------------------------------------------------------------------------
*/

window.showStepStyleToast = function (
    message,
    type = "success"
){

    const oldToast =
        document.querySelector(
            ".stepstyle-toast"
        );

    if(oldToast){
        oldToast.remove();
    }

    const toast =
        document.createElement("div");

    const icon =
        type === "error"
            ? "fa-circle-exclamation"
            : "fa-circle-check";

    toast.className =
        `stepstyle-toast ${type}`;

    toast.innerHTML = `
        <i class="fa-solid ${icon}"></i>
        <span>${escapeHtml(message)}</span>
    `;

    document.body.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.add("show");
    });

    setTimeout(() => {

        toast.classList.remove("show");

        setTimeout(() => {
            toast.remove();
        }, 300);

    }, 2600);
};

/*
|--------------------------------------------------------------------------
| Theme Management
|--------------------------------------------------------------------------
*/

function updateThemeButton(isDark){

    const themeButton =
        document.getElementById(
            "darkModeBtn"
        );

    if(!themeButton){
        return;
    }

    /*
     * Dark mode active:
     * Show sun because clicking switches to light mode.
     *
     * Light mode active:
     * Show moon because clicking switches to dark mode.
     */

    themeButton.innerHTML = isDark
        ? '<i class="fa-solid fa-sun theme-action-icon"></i>'
        : '<i class="fa-solid fa-moon theme-action-icon"></i>';

    const themeIcon =
        themeButton.querySelector(
            ".theme-action-icon"
        );

    if(themeIcon){

        themeIcon.style.setProperty(
            "display",
            "inline-block",
            "important"
        );
    }

    themeButton.setAttribute(
        "aria-label",
        isDark
            ? "Switch to light mode"
            : "Switch to dark mode"
    );

    themeButton.setAttribute(
        "title",
        isDark
            ? "Switch to light mode"
            : "Switch to dark mode"
    );
}

function applyTheme(
    theme,
    saveTheme = true
){

    const isDark =
        theme === "dark";

    document.documentElement.classList.toggle(
        "dark-theme",
        isDark
    );

    if(document.body){

        document.body.classList.toggle(
            "dark",
            isDark
        );
    }

    if(saveTheme){

        localStorage.setItem(
            "theme",
            isDark
                ? "dark"
                : "light"
        );
    }

    updateThemeButton(isDark);
}

function initializeTheme(){

    const savedTheme =
        localStorage.getItem("theme");

    const selectedTheme =
        savedTheme === "dark"
            ? "dark"
            : "light";

    applyTheme(
        selectedTheme,
        false
    );

    const themeButton =
        document.getElementById(
            "darkModeBtn"
        );

    if(!themeButton){
        return;
    }

    themeButton.addEventListener(
        "click",
        function () {

            const isDark =
                document.body.classList.contains(
                    "dark"
                );

            applyTheme(
                isDark
                    ? "light"
                    : "dark"
            );
        }
    );
}
/*
|--------------------------------------------------------------------------
| Mobile Navigation
|--------------------------------------------------------------------------
*/

function initializeMobileNavigation(){

    const menuButton =
        document.getElementById("mobileMenuButton");

    const navigation =
        document.getElementById("mainNavigation");

    if(!menuButton || !navigation){
        return;
    }

    const menuIcon =
        menuButton.querySelector("i");

    function closeMenu(){

        navigation.classList.remove("open");
        menuButton.classList.remove("active");

        menuButton.setAttribute(
            "aria-expanded",
            "false"
        );

        if(menuIcon){

            menuIcon.classList.remove("fa-xmark");
            menuIcon.classList.add("fa-bars");
        }
    }

    menuButton.addEventListener(
        "click",
        function () {

            const isOpen =
                navigation.classList.toggle("open");

            menuButton.classList.toggle(
                "active",
                isOpen
            );

            menuButton.setAttribute(
                "aria-expanded",
                String(isOpen)
            );

            if(menuIcon){

                menuIcon.classList.toggle(
                    "fa-bars",
                    !isOpen
                );

                menuIcon.classList.toggle(
                    "fa-xmark",
                    isOpen
                );
            }
        }
    );

    navigation
        .querySelectorAll("a")
        .forEach(function (link) {

            link.addEventListener(
                "click",
                closeMenu
            );
        });

    document.addEventListener(
        "click",
        function (event) {

            if(
                !navigation.contains(event.target)
                &&
                !menuButton.contains(event.target)
            ){
                closeMenu();
            }
        }
    );

    window.addEventListener(
        "resize",
        function () {

            if(window.innerWidth > 992){
                closeMenu();
            }
        }
    );
}

/*
|--------------------------------------------------------------------------
| Account Dropdown
|--------------------------------------------------------------------------
*/

function initializeAccountDropdown(){

    const accountMenu =
        document.querySelector(".account-menu");

    if(!accountMenu){
        return;
    }

    const accountButton =
        accountMenu.querySelector(
            ".account-menu-button"
        );

    if(!accountButton){
        return;
    }

    function closeDropdown(){

        accountMenu.classList.remove("open");

        accountButton.setAttribute(
            "aria-expanded",
            "false"
        );
    }

    accountButton.addEventListener(
        "click",
        function (event) {

            event.stopPropagation();

            const isOpen =
                accountMenu.classList.toggle("open");

            accountButton.setAttribute(
                "aria-expanded",
                String(isOpen)
            );
        }
    );

    document.addEventListener(
        "click",
        function (event) {

            if(
                !accountMenu.contains(event.target)
            ){
                closeDropdown();
            }
        }
    );
}

/*
|--------------------------------------------------------------------------
| Header Scroll Effect
|--------------------------------------------------------------------------
*/

function initializeHeaderScroll(){

    const header =
        document.querySelector(".site-header");

    if(!header){
        return;
    }

    function updateHeader(){

        header.classList.toggle(
            "scrolled",
            window.scrollY > 20
        );
    }

    updateHeader();

    window.addEventListener(
        "scroll",
        updateHeader,
        {
            passive: true
        }
    );
}

/*
|--------------------------------------------------------------------------
| Back To Top Button
|--------------------------------------------------------------------------
*/

function initializeBackToTop(){

    const button =
        document.createElement("button");

    button.type = "button";
    button.className = "back-to-top-button";

    button.setAttribute(
        "aria-label",
        "Back to top"
    );

    button.innerHTML =
        '<i class="fa-solid fa-arrow-up"></i>';

    document.body.appendChild(button);

    window.addEventListener(
        "scroll",
        function () {

            button.classList.toggle(
                "show",
                window.scrollY > 450
            );
        },
        {
            passive: true
        }
    );

    button.addEventListener(
        "click",
        function () {

            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        }
    );
}
/*
|--------------------------------------------------------------------------
| Add To Cart
|--------------------------------------------------------------------------
*/

function initializeAddToCart(){

    document.addEventListener(
        "click",
        async function (event) {

            const button =
                event.target.closest(
                    ".add-cart-btn, .add-to-cart-btn"
                );

            if(!button || button.disabled){
                return;
            }

            event.preventDefault();

            const productId =
                button.dataset.productId
                ||
                button.dataset.id;

            if(!productId){

                window.showStepStyleToast(
                    "Invalid product.",
                    "error"
                );

                return;
            }

            const originalContent =
                button.innerHTML;

            button.disabled = true;

            button.innerHTML = `
                <i class="fa-solid fa-spinner fa-spin"></i>
                <span>Adding...</span>
            `;

            const formData =
                new FormData();

            formData.append(
                "product_id",
                productId
            );

            try{

                const response =
                    await fetch(
                        `${STEPSTYLE_BASE_URL}/ajax/add-to-cart.php`,
                        {
                            method: "POST",
                            body: formData,
                            credentials: "same-origin",
                            cache: "no-store"
                        }
                    );

                const responseText =
                    await response.text();

                let result;

                try{

                    result =
                        JSON.parse(responseText);

                }catch(error){

                    throw new Error(
                        "Invalid server response."
                    );
                }

                if(
                    !response.ok
                    ||
                    result.success !== true
                ){
                    throw new Error(
                        result.message
                        ||
                        "Unable to add product to cart."
                    );
                }

                button.innerHTML = `
                    <i class="fa-solid fa-check"></i>
                    <span>Added</span>
                `;

                window.showStepStyleToast(
                    result.message
                    ||
                    "Product added to cart."
                );

                setTimeout(function () {
                    window.location.reload();
                }, 700);

            }catch(error){

                button.disabled = false;
                button.innerHTML =
                    originalContent;

                window.showStepStyleToast(
                    error.message
                    ||
                    "Unable to add product to cart.",
                    "error"
                );
            }
        }
    );
}

/*
|--------------------------------------------------------------------------
| Newsletter Form
|--------------------------------------------------------------------------
*/

function initializeNewsletter(){

    const newsletterForm =
        document.getElementById(
            "newsletterForm"
        );

    if(!newsletterForm){
        return;
    }

    newsletterForm.addEventListener(
        "submit",
        function (event) {

            event.preventDefault();

            const emailInput =
                newsletterForm.querySelector(
                    'input[type="email"]'
                );

            if(
                !emailInput
                ||
                !emailInput.value.trim()
                ||
                !emailInput.validity.valid
            ){

                window.showStepStyleToast(
                    "Please enter a valid email address.",
                    "error"
                );

                emailInput?.focus();
                return;
            }

            window.showStepStyleToast(
                "Thank you for subscribing."
            );

            newsletterForm.reset();
        }
    );
}

/*
|--------------------------------------------------------------------------
| Image Upload Preview
|--------------------------------------------------------------------------
*/

function initializeImagePreview(){

    const imageInput =
        document.getElementById(
            "imageInput"
        );

    const fileName =
        document.getElementById(
            "fileName"
        );

    const imagePreview =
        document.getElementById(
            "imagePreview"
        );

    if(!imageInput){
        return;
    }

    imageInput.addEventListener(
        "change",
        function () {

            const selectedFile =
                imageInput.files?.[0];

            if(!selectedFile){

                if(fileName){
                    fileName.textContent =
                        "No file selected";
                }

                if(imagePreview){
                    imagePreview.style.display =
                        "none";
                }

                return;
            }

            if(fileName){
                fileName.textContent =
                    selectedFile.name;
            }

            if(imagePreview){

                const imageUrl =
                    URL.createObjectURL(
                        selectedFile
                    );

                imagePreview.src =
                    imageUrl;

                imagePreview.style.display =
                    "block";

                imagePreview.onload =
                    function () {

                        URL.revokeObjectURL(
                            imageUrl
                        );
                    };
            }
        }
    );
}

/*
|--------------------------------------------------------------------------
| Prevent Double Form Submission
|--------------------------------------------------------------------------
*/

function initializeFormProtection(){

    document
        .querySelectorAll(
            "form[data-prevent-double-submit]"
        )
        .forEach(function (form) {

            form.addEventListener(
                "submit",
                function () {

                    if(!form.checkValidity()){
                        return;
                    }

                    const submitButton =
                        form.querySelector(
                            'button[type="submit"]'
                        );

                    if(!submitButton){
                        return;
                    }

                    submitButton.disabled = true;
                }
            );
        });
}

/*
|--------------------------------------------------------------------------
| Escape Key
|--------------------------------------------------------------------------
*/

function initializeEscapeKey(){

    document.addEventListener(
        "keydown",
        function (event) {

            if(event.key !== "Escape"){
                return;
            }

            const navigation =
                document.getElementById(
                    "mainNavigation"
                );

            const menuButton =
                document.getElementById(
                    "mobileMenuButton"
                );

            navigation?.classList.remove(
                "open"
            );

            menuButton?.classList.remove(
                "active"
            );

            menuButton?.setAttribute(
                "aria-expanded",
                "false"
            );

            const accountMenu =
                document.querySelector(
                    ".account-menu"
                );

            accountMenu?.classList.remove(
                "open"
            );
        }
    );
}

/*
|--------------------------------------------------------------------------
| Initialize Website
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    function () {

        initializeTheme();
        initializeMobileNavigation();
        initializeAccountDropdown();
        initializeHeaderScroll();
        initializeBackToTop();
        initializeAddToCart();
        initializeNewsletter();
        initializeImagePreview();
        initializeFormProtection();
        initializeEscapeKey();
    }
);

/*
|--------------------------------------------------------------------------
| Synchronize Theme Between Browser Tabs
|--------------------------------------------------------------------------
*/

window.addEventListener(
    "storage",
    function (event) {

        if(event.key !== "theme"){
            return;
        }

        applyTheme(
            event.newValue === "dark"
                ? "dark"
                : "light",
            false
        );
    }
);