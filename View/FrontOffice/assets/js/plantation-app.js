(function () {
  "use strict";

  const menuButton = document.querySelector(".menu-toggle");
  const navigation = document.querySelector(".site-nav");

  if (menuButton && navigation) {
    menuButton.addEventListener("click", function () {
      const isOpen = navigation.classList.toggle("open");
      menuButton.setAttribute("aria-expanded", isOpen ? "true" : "false");
      menuButton.setAttribute("aria-label", isOpen ? "Fermer la navigation" : "Ouvrir la navigation");
    });
  }

  const track = document.querySelector("[data-carousel-track]");
  const previous = document.querySelector(".carousel-previous");
  const next = document.querySelector(".carousel-next");

  if (track && previous && next) {
    function scrollCarousel(direction) {
      const card = track.querySelector(".plantation-card");
      if (!card) return;

      const gap = parseFloat(window.getComputedStyle(track).columnGap) || 0;
      track.scrollBy({
        left: direction * (card.getBoundingClientRect().width + gap),
        behavior: "smooth"
      });
    }

    previous.addEventListener("click", function () {
      scrollCarousel(-1);
    });
    next.addEventListener("click", function () {
      scrollCarousel(1);
    });
    track.addEventListener("keydown", function (event) {
      if (event.key === "ArrowLeft") scrollCarousel(-1);
      if (event.key === "ArrowRight") scrollCarousel(1);
    });
  }

  const plantationForm = document.querySelector("#plantation-form");
  if (plantationForm) {
    const choices = Array.from(plantationForm.querySelectorAll(".variety-choice"));
    const otherChoice = choices.find(function (choice) {
      return choice.value === "Autre";
    });
    const otherInput = plantationForm.querySelector("#variete_autre");

    function validateVarieties() {
      const firstChoice = choices[0];
      if (firstChoice) {
        firstChoice.setCustomValidity(choices.some(function (choice) {
          return choice.checked;
        }) ? "" : "Choisissez au moins une variété.");
      }

      if (otherChoice && otherInput) {
        otherInput.required = otherChoice.checked;
      }
    }

    choices.forEach(function (choice) {
      choice.addEventListener("change", validateVarieties);
    });
    plantationForm.addEventListener("submit", function (event) {
      validateVarieties();
      if (!plantationForm.reportValidity()) {
        event.preventDefault();
      }
    });
    validateVarieties();
  }
})();
