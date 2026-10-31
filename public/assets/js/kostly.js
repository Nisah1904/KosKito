/* =========================================================
   KOSTLY JAVASCRIPT
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    initMobileMenu();

    initFlashMessage();

    initSmoothScroll();

    initImageFallback();

});


/* =========================================================
   MOBILE MENU
========================================================= */

function initMobileMenu() {

    const button =
        document.getElementById("mobileMenuButton");

    const menu =
        document.getElementById("mobileNav");


    if (!button || !menu) {
        return;
    }


    button.addEventListener("click", function () {

        const isOpen =
            menu.classList.toggle("open");

        button.setAttribute(
            "aria-expanded",
            isOpen ? "true" : "false"
        );

    });


    /*
     * Tutup menu ketika link diklik.
     */

    const links =
        menu.querySelectorAll("a");


    links.forEach(function (link) {

        link.addEventListener("click", function () {

            menu.classList.remove("open");

            button.setAttribute(
                "aria-expanded",
                "false"
            );

        });

    });


    /*
     * Tutup menu ketika klik di luar.
     */

    document.addEventListener("click", function (event) {

        if (
            !menu.contains(event.target) &&
            !button.contains(event.target)
        ) {

            menu.classList.remove("open");

            button.setAttribute(
                "aria-expanded",
                "false"
            );

        }

    });

}


/* =========================================================
   FLASH MESSAGE
========================================================= */

function initFlashMessage() {

    const flash =
        document.getElementById("flashMessage");


    if (!flash) {
        return;
    }


    /*
     * Otomatis hilang setelah beberapa detik.
     */

    setTimeout(function () {

        closeFlash();

    }, 5000);

}


function closeFlash() {

    const flash =
        document.getElementById("flashMessage");


    if (!flash) {
        return;
    }


    flash.style.opacity = "0";

    flash.style.transform =
        "translateY(-10px)";


    setTimeout(function () {

        flash.remove();

    }, 250);

}


/* =========================================================
   SMOOTH SCROLL
========================================================= */

function initSmoothScroll() {

    const links =
        document.querySelectorAll(
            'a[href^="#"]'
        );


    links.forEach(function (link) {

        link.addEventListener(
            "click",
            function (event) {

                const targetId =
                    this.getAttribute("href");


                if (
                    !targetId ||
                    targetId === "#"
                ) {
                    return;
                }


                const target =
                    document.querySelector(targetId);


                if (!target) {
                    return;
                }


                event.preventDefault();


                const header =
                    document.querySelector(
                        ".site-header"
                    );


                const headerHeight =
                    header
                        ? header.offsetHeight
                        : 0;


                const position =
                    target.getBoundingClientRect().top +
                    window.scrollY -
                    headerHeight -
                    15;


                window.scrollTo({

                    top: position,

                    behavior: "smooth"

                });

            }
        );

    });

}


/* =========================================================
   IMAGE FALLBACK
========================================================= */

function initImageFallback() {

    const images =
        document.querySelectorAll("img");


    images.forEach(function (image) {

        image.addEventListener(
            "error",
            function () {

                /*
                 * Jika gambar lokal belum tersedia,
                 * sembunyikan gambar supaya layout
                 * tidak rusak.
                 */

                this.style.display = "none";

            }
        );

    });

}


/* =========================================================
   COST PLANNER
========================================================= */

function calculateCost() {

    const rent =
        Number(
            document.getElementById("rent")?.value
        ) || 0;


    const utilities =
        Number(
            document.getElementById("utilities")?.value
        ) || 0;


    const laundry =
        Number(
            document.getElementById("laundry")?.value
        ) || 0;


    const transport =
        Number(
            document.getElementById("transport")?.value
        ) || 0;


    const food =
        Number(
            document.getElementById("food")?.value
        ) || 0;


    const other =
        Number(
            document.getElementById("other")?.value
        ) || 0;


    const total =
        rent +
        utilities +
        laundry +
        transport +
        food +
        other;


    const result =
        document.getElementById("totalCost");


    if (!result) {
        return;
    }


    result.textContent =
        "Rp " +
        total.toLocaleString(
            "id-ID"
        );

}


/* =========================================================
   FAVORITE BUTTON
========================================================= */

function toggleFavorite(button) {

    if (!button) {
        return;
    }


    const isFavorite =
        button.classList.toggle(
            "is-favorite"
        );


    button.setAttribute(
        "aria-pressed",
        isFavorite ? "true" : "false"
    );


    button.innerHTML =
        isFavorite
            ? "♥"
            : "♡";

}


/* =========================================================
   CONFIRM ACTION
========================================================= */

function confirmAction(message) {

    return window.confirm(
        message ||
        "Apakah kamu yakin ingin melanjutkan?"
    );

}


/* =========================================================
   FILTER FORM
========================================================= */

function resetFilters() {

    const forms =
        document.querySelectorAll(
            ".filter-bar"
        );


    forms.forEach(function (form) {

        const inputs =
            form.querySelectorAll(
                "input, select"
            );


        inputs.forEach(function (input) {

            if (input.tagName === "SELECT") {

                input.selectedIndex = 0;

            } else {

                input.value = "";

            }

        });

    });

}


/* =========================================================
   GLOBAL NUMBER FORMAT
========================================================= */

function formatRupiah(number) {

    return "Rp " +
        Number(number || 0)
            .toLocaleString("id-ID");

}


/* =========================================================
   GLOBAL API HELPER
========================================================= */

async function postData(
    url,
    data = {}
) {

    const token =
        document.querySelector(
            'meta[name="csrf-token"]'
        )?.getAttribute("content");


    const response =
        await fetch(
            url,
            {

                method: "POST",

                headers: {

                    "Content-Type":
                        "application/json",

                    "Accept":
                        "application/json",

                    "X-CSRF-TOKEN":
                        token || ""

                },

                body:
                    JSON.stringify(data)

            }
        );


    if (!response.ok) {

        throw new Error(
            "Terjadi kesalahan pada server."
        );

    }


    return response.json();

}