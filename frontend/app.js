"use strict";

const form = document.querySelector("#lead-form");
const nameInput = document.querySelector("#name");
const emailInput = document.querySelector("#email");
const phoneInput = document.querySelector("#phone");
const phoneCounter = document.querySelector("#phone-counter");
const phoneError = document.querySelector("#phone-error");
const priceInput = document.querySelector("#price");
const submitButton = form.querySelector("button[type='submit']");
const submitButtonLabel = submitButton.querySelector("span:first-child");
const formStatus = document.querySelector("#form-status");
const pageOpenedAt = performance.now();

let timeOnSiteOver30 = false;

window.setTimeout(() => {
    timeOnSiteOver30 = performance.now() - pageOpenedAt > 30_000;
}, 30_001);

const keepDigitsOnly = (input) => {
    const digits = input.value.replace(/\D/g, "");

    if (input.value !== digits) {
        input.value = digits;
    }
};

const setPhoneError = (message) => {
    phoneError.textContent = message;

    if (message) {
        phoneInput.setAttribute("aria-invalid", "true");
        return;
    }

    phoneInput.removeAttribute("aria-invalid");
};

const validatePhone = () => {
    const phoneLength = phoneInput.value.length;

    if (phoneLength === 0) {
        setPhoneError("Поле Телефон обязательное для заполнения");
        return false;
    }

    if (phoneLength < 5) {
        setPhoneError("Введите не менее 5 цифр.");
        return false;
    }

    setPhoneError("");
    return true;
};

const setFormStatus = (message, type = "") => {
    formStatus.textContent = message;
    formStatus.classList.remove("is-error", "is-success");

    if (type) {
        formStatus.classList.add(`is-${type}`);
    }
};

const setSubmitting = (isSubmitting) => {
    submitButton.disabled = isSubmitting;
    submitButton.classList.toggle("is-loading", isSubmitting);
    submitButton.setAttribute("aria-busy", String(isSubmitting));
    submitButtonLabel.textContent = isSubmitting ? "Отправляем…" : "Отправить заявку";
};

const buildPayload = () => ({
    name: nameInput.value,
    email: emailInput.value,
    phone: phoneInput.value,
    price: priceInput.value,
    timeOnSiteOver30: timeOnSiteOver30 || performance.now() - pageOpenedAt > 30_000,
});

phoneInput.addEventListener("input", () => {
    keepDigitsOnly(phoneInput);
    phoneCounter.textContent = `${phoneInput.value.length} / 15`;

    if (phoneInput.hasAttribute("aria-invalid")) {
        validatePhone();
    }
});

phoneInput.addEventListener("blur", validatePhone);

priceInput.addEventListener("input", () => {
    keepDigitsOnly(priceInput);
});

form.addEventListener("submit", async (event) => {
    event.preventDefault();

    if (!validatePhone()) {
        phoneInput.focus();
        return;
    }

    setFormStatus("");
    setSubmitting(true);

    try {
        const response = await fetch(window.APP_CONFIG.apiUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify(buildPayload()),
        });

        if (!response.ok) {
            throw new Error(`Request failed with status ${response.status}`);
        }

        form.reset();
        phoneCounter.textContent = "0 / 15";
        setPhoneError("");
        setFormStatus("Спасибо! Заявка отправлена — скоро мы свяжемся с вами.", "success");
    } catch (error) {
        console.error("Lead submission failed", error);
        setFormStatus("Не удалось отправить заявку. Попробуйте ещё раз.", "error");
    } finally {
        setSubmitting(false);
    }
});
