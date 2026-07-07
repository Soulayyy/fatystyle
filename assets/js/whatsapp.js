(function () {
  const form = document.querySelector("[data-whatsapp-form]");
  if (!form) return;

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    const data = new FormData(form);
    const message = [
      "Bonjour Faty Style,",
      "Je souhaite vous contacter pour un projet couture.",
      "",
      `Nom : ${data.get("name") || ""}`,
      `Téléphone : ${data.get("phone") || ""}`,
      `Email : ${data.get("email") || ""}`,
      `Type de demande : ${data.get("requestType") || ""}`,
      `Date souhaitée : ${data.get("date") || ""}`,
      `Message : ${data.get("message") || ""}`,
      "",
      "Merci."
    ].join("\n");

    window.location.href = "https://wa.me/33768655643?text=" + encodeURIComponent(message);
  });
})();
