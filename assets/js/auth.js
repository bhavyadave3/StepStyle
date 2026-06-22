"use strict";

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const passwordButtons =
            document.querySelectorAll(
                ".password-toggle"
            );

        passwordButtons.forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        const targetId =
                            button.dataset.passwordTarget;

                        const passwordInput =
                            document.getElementById(
                                targetId
                            );

                        const icon =
                            button.querySelector("i");

                        if(!passwordInput){
                            return;
                        }

                        const passwordIsHidden =
                            passwordInput.type ===
                            "password";

                        passwordInput.type =
                            passwordIsHidden
                                ? "text"
                                : "password";

                        button.setAttribute(
                            "aria-label",
                            passwordIsHidden
                                ? "Hide password"
                                : "Show password"
                        );

                        if(icon){

                            icon.classList.toggle(
                                "fa-eye",
                                !passwordIsHidden
                            );

                            icon.classList.toggle(
                                "fa-eye-slash",
                                passwordIsHidden
                            );
                        }
                    }
                );
            }
        );

        const authForms =
            document.querySelectorAll(
                ".auth-form"
            );

        authForms.forEach(
            function (form) {

                form.addEventListener(
                    "submit",
                    function (event) {

                        const requiredFields =
                            form.querySelectorAll(
                                "[required]"
                            );

                        let firstInvalidField = null;

                        requiredFields.forEach(
                            function (field) {

                                field.classList.remove(
                                    "input-error"
                                );

                                if(
                                    !field.value.trim()
                                    &&
                                    !firstInvalidField
                                ){
                                    firstInvalidField =
                                        field;
                                }
                            }
                        );

                        const emailInput =
                            form.querySelector(
                                'input[type="email"]'
                            );

                        if(
                            !firstInvalidField
                            &&
                            emailInput
                            &&
                            !emailInput.validity.valid
                        ){
                            firstInvalidField =
                                emailInput;
                        }

                        const passwordInput =
                            form.querySelector(
                                "#password"
                            );

                        const confirmPasswordInput =
                            form.querySelector(
                                "#confirmPassword"
                            );

                        if(
                            !firstInvalidField
                            &&
                            confirmPasswordInput
                            &&
                            passwordInput
                            &&
                            passwordInput.value !==
                            confirmPasswordInput.value
                        ){
                            firstInvalidField =
                                confirmPasswordInput;

                            showAuthMessage(
                                "Passwords do not match."
                            );
                        }

                        if(firstInvalidField){

                            event.preventDefault();

                            firstInvalidField.classList.add(
                                "input-error"
                            );

                            firstInvalidField.focus();

                            if(
                                !document.querySelector(
                                    ".client-auth-message"
                                )
                            ){
                                showAuthMessage(
                                    "Please complete all fields correctly."
                                );
                            }

                            return;
                        }

                        const submitButton =
                            form.querySelector(
                                'button[type="submit"]'
                            );

                        if(submitButton){

                            submitButton.disabled =
                                true;

                            submitButton.innerHTML = `
                                <i class="fa-solid fa-spinner fa-spin"></i>
                                <span>Please wait...</span>
                            `;
                        }
                    }
                );
            }
        );

        document.addEventListener(
            "input",
            function (event) {

                if(
                    event.target.matches(
                        ".auth-form input"
                    )
                ){
                    event.target.classList.remove(
                        "input-error"
                    );

                    const clientMessage =
                        document.querySelector(
                            ".client-auth-message"
                        );

                    if(clientMessage){
                        clientMessage.remove();
                    }
                }
            }
        );
    }
);


function showAuthMessage(message){

    const authContainer =
        document.querySelector(
            ".auth-container"
        );

    const authForm =
        document.querySelector(
            ".auth-form"
        );

    if(
        !authContainer
        ||
        !authForm
    ){
        return;
    }

    const existingMessage =
        document.querySelector(
            ".client-auth-message"
        );

    if(existingMessage){
        existingMessage.remove();
    }

    const messageBox =
        document.createElement("div");

    messageBox.className =
        "error-message client-auth-message";

    messageBox.setAttribute(
        "role",
        "alert"
    );

    messageBox.innerHTML = `
        <i class="fa-solid fa-circle-exclamation"></i>
        <span>${escapeAuthHtml(message)}</span>
    `;

    authContainer.insertBefore(
        messageBox,
        authForm
    );
}


function escapeAuthHtml(value){

    const element =
        document.createElement("div");

    element.textContent =
        String(value ?? "");

    return element.innerHTML;
}