"use strict";

document.addEventListener(
    "click",
    async function (event) {

        const button = event.target.closest(
            ".wishlist-btn, .remove-wishlist-btn"
        );

        if(!button || button.disabled){
            return;
        }

        event.preventDefault();

        const productId =
            button.dataset.productId ||
            button.dataset.id;

        if(!productId){
            return;
        }

        const isRemoveButton =
            button.classList.contains(
                "remove-wishlist-btn"
            );

        const originalContent =
            button.innerHTML;

        button.disabled = true;

        button.innerHTML = isRemoveButton
            ? `
                <i class="fa-solid fa-spinner fa-spin"></i>
                <span>Removing...</span>
            `
            : `
                <i class="fa-solid fa-spinner fa-spin"></i>
            `;

        const formData = new FormData();

        formData.append(
            "product_id",
            productId
        );

        formData.append(
            "action",
            isRemoveButton
                ? "remove"
                : "toggle"
        );

        try{

            const baseUrl = (
                window.STEPSTYLE_URL ||
                "/StepStyle"
            ).replace(/\/$/, "");

            const response = await fetch(
                `${baseUrl}/ajax/wishlist.php`,
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

                result = JSON.parse(
                    responseText
                );

            }catch(error){

                throw new Error(
                    "Invalid server response."
                );
            }

            const requestSucceeded =
                response.ok &&
                (
                    result.success === true ||
                    result.status === "success"
                );

            if(!requestSucceeded){

                throw new Error(
                    result.message ||
                    "Unable to update wishlist."
                );
            }

            if(isRemoveButton){

                const wishlistCard =
                    button.closest(
                        ".wishlist-card"
                    );

                if(wishlistCard){

                    wishlistCard.style.opacity =
                        "0";

                    wishlistCard.style.transform =
                        "scale(0.96)";

                    setTimeout(
                        function () {

                            wishlistCard.remove();

                            const remainingCards =
                                document.querySelectorAll(
                                    ".wishlist-card"
                                );

                            if(
                                remainingCards.length
                                === 0
                            ){
                                window.location.reload();
                            }

                        },
                        250
                    );

                }else{

                    window.location.reload();
                }

            }else{

                const icon =
                    button.querySelector("i");

                if(icon){

                    icon.classList.remove(
                        "fa-regular"
                    );

                    icon.classList.add(
                        "fa-solid"
                    );
                }

                button.classList.add(
                    "active"
                );

                button.disabled = false;
            }

            if(
                window.showStepStyleToast
            ){
                window.showStepStyleToast(
                    result.message ||
                    (
                        isRemoveButton
                            ? "Product removed from wishlist."
                            : "Wishlist updated."
                    )
                );
            }

        }catch(error){

            button.disabled = false;
            button.innerHTML =
                originalContent;

            if(
                window.showStepStyleToast
            ){
                window.showStepStyleToast(
                    error.message ||
                    "Unable to update wishlist.",
                    "error"
                );
            }

            console.error(
                "Wishlist error:",
                error
            );
        }
    }
);