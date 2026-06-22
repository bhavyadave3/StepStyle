document.addEventListener("click", async function (event) {

    const removeButton =
        event.target.closest(".remove-cart-btn");

    if (!removeButton) {
        return;
    }

    event.preventDefault();

    const cartId =
        removeButton.dataset.id;

    if (!cartId) {
        return;
    }

    removeButton.disabled = true;

    const originalText =
        removeButton.innerHTML;

    removeButton.innerHTML =
        '<i class="fa-solid fa-spinner fa-spin"></i>';

    const formData =
        new FormData();

    formData.append(
        "cart_id",
        cartId
    );

    try {

        const response = await fetch(
            `${window.STEPSTYLE_URL}/ajax/remove.php`,
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

        try {

            result =
                JSON.parse(responseText);

        } catch (jsonError) {

            console.error(
                "Invalid server response:",
                responseText
            );

            throw new Error(
                "The server returned an invalid response."
            );
        }

        if(
            !response.ok ||
            result.success !== true
        ){

            throw new Error(
                result.message ||
                "Unable to remove the product."
            );
        }

        const tableRow =
            removeButton.closest("tr");

        if(tableRow){

            tableRow.style.opacity = "0";

            tableRow.style.transition =
                "opacity 0.2s ease";
        }

        window.location.reload();

    } catch (error) {

        console.error(
            "Remove cart error:",
            error
        );

        removeButton.disabled = false;

        removeButton.innerHTML =
            originalText;

        if(window.showStepStyleToast){

            window.showStepStyleToast(
                error.message,
                "error"
            );

        }else{

            console.error(error.message);
        }
    }
});


document.addEventListener("change", async function (event) {

    const quantityInput =
        event.target.closest(".cart-qty");

    if(!quantityInput){
        return;
    }

    const cartId =
        quantityInput.dataset.id;

    let quantity =
        parseInt(quantityInput.value, 10);

    if(
        !Number.isInteger(quantity) ||
        quantity < 1
    ){

        quantity = 1;
        quantityInput.value = 1;
    }

    const formData =
        new FormData();

    formData.append(
        "cart_id",
        cartId
    );

    formData.append(
        "quantity",
        quantity
    );

    quantityInput.disabled = true;

    try{

        const response = await fetch(
            `${window.STEPSTYLE_URL}/ajax/update-cart.php`,
            {
                method: "POST",
                body: formData,
                credentials: "same-origin",
                cache: "no-store"
            }
        );

        const result =
            await response.json();

        if(
            !response.ok ||
            result.success !== true
        ){

            throw new Error(
                result.message ||
                "Unable to update quantity."
            );
        }

        window.location.reload();

    }catch(error){

        console.error(
            "Update cart error:",
            error
        );

        quantityInput.disabled = false;

        if(window.showStepStyleToast){

            window.showStepStyleToast(
                error.message,
                "error"
            );
        }
    }
});