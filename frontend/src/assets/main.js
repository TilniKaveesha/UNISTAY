
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

    // Show temporary previews for selected boarding photos.
    const photoInput = document.getElementById("listingPhotos");
    const photoGrid = document.getElementById("photoPreviewGrid");
    const photoStatus = document.getElementById("photoUploadStatus");

    if (photoInput && photoGrid && photoStatus) {
        let selectedPhotos = [];
        let previewUrls = [];
        const allowedTypes = ["image/jpeg", "image/png", "image/webp"];
        const maxFileSize = 2 * 1024 * 1024;

        function showPhotoPreviews() {
            previewUrls.forEach(function (url) {
                URL.revokeObjectURL(url);
            });
            previewUrls = [];
            photoGrid.replaceChildren();

            selectedPhotos.forEach(function (photo, index) {
                const card = document.createElement("div");
                card.className = "photo-preview-card";

                const image = document.createElement("img");
                const previewUrl = URL.createObjectURL(photo);
                previewUrls.push(previewUrl);
                image.src = previewUrl;
                image.alt = "Selected boarding photo " + (index + 1);

                const removeButton = document.createElement("button");
                removeButton.type = "button";
                removeButton.className = "photo-remove-button";
                removeButton.textContent = "Remove";
                removeButton.setAttribute("aria-label", "Remove photo " + (index + 1));
                removeButton.addEventListener("click", function () {
                    selectedPhotos.splice(index, 1);
                    showPhotoPreviews();
                });

                card.append(image, removeButton);
                photoGrid.appendChild(card);
            });

            photoStatus.textContent = selectedPhotos.length + " of 3 photos selected. Images are not saved yet.";

            const fileTransfer = new DataTransfer();
            selectedPhotos.forEach(function (photo) {
                fileTransfer.items.add(photo);
            });
            photoInput.files = fileTransfer.files;
        }

        photoInput.addEventListener("change", function () {
            const chosenPhotos = Array.from(photoInput.files);
            const messages = [];

            chosenPhotos.forEach(function (photo) {
                if (!allowedTypes.includes(photo.type)) {
                    messages.push(photo.name + " is not a JPG, PNG or WebP image.");
                } else if (photo.size > maxFileSize) {
                    messages.push(photo.name + " is larger than 2 MB.");
                } else if (selectedPhotos.length < 3) {
                    selectedPhotos.push(photo);
                } else {
                    messages.push("You can select up to 3 photos.");
                }
            });

            showPhotoPreviews();
            if (messages.length) {
                photoStatus.textContent = messages.join(" ") + " " + selectedPhotos.length + " of 3 photos selected.";
            }
        });
    }
});
