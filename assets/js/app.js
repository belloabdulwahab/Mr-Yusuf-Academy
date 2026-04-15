/* DASHBOARD UI CONTROLLER
    Handles:
    - Smooth scrolling 
    - Offcanvas sidebar close 
    - Active sidebar highlighting */

document.addEventListener("DOMContentLoaded", function () {

    const sidebar = document.getElementById("sidebar");

    /* All sidebar links that scroll */
    const links = document.querySelectorAll(".sidebar-link");

    links.forEach(link => {

        link.addEventListener("click", function (e) {

            const targetId = this.getAttribute("href");

            if (!targetId.startsWith("#")) return;

            const target = document.querySelector(targetId);

            if (!target) return;

            e.preventDefault();

            /* Highlight active link */
            links.forEach(l => l.classList.remove("active"));
            this.classList.add("active");

            /* Close sidebar */
            const offcanvas = bootstrap.Offcanvas.getInstance(sidebar);

            if (offcanvas) {
                offcanvas.hide();
            }

            /* Smooth Scroll after sidebar closes */
            setTimeout(() => {

                target.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });

            }, 300);

        });

    });

});


    window.addEventListener("scroll", function () {
        const navbar = document.querySelector(".dashboard-navbar");
        if (window.scrollY > 30) {
            navbar.classList.add("scrolled");
        } else {
            navbar.classList.remove("scrolled");
        }
    });
