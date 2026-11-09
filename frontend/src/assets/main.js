
document.addEventListener("DOMContentLoaded", function () {

    const menuToggle = document.getElementById("menuToggle");
    const navLinks = document.getElementById("navLinks");

    // Mobile navigation toggle
    if (menuToggle && navLinks) {

        menuToggle.addEventListener("click", function () {

            navLinks.classList.toggle("show");

            const isExpanded = navLinks.classList.contains("show");

            menuToggle.setAttribute("aria-expanded", isExpanded);

            menuToggle.textContent = isExpanded ? "✕" : "☰";

        });

    }

    // Close mobile navigation after selecting a link
    if (navLinks) {

        const navigationItems = navLinks.querySelectorAll("a");

        navigationItems.forEach(function (link) {

            link.addEventListener("click", function () {

                navLinks.classList.remove("show");

                if (menuToggle) {
                    menuToggle.textContent = "☰";
                    menuToggle.setAttribute("aria-expanded", "false");
                }

            });

        });

    }

});
